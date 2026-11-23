<?php

namespace App\Support\OrganizationalUnits;

use App\Enums\UnitType;
use App\Models\Agency;
use App\Models\OrganizationalUnit;
use Illuminate\Support\Collection;

/**
 * Places the bodies under a head the way public-sector organization charts in Peru are drawn: the control and
 * advisory bodies hang to the left of the main line, the support bodies to the right, and the line bodies and
 * the deconcentrated ones (the agrarian agencies) sit on the bottom row. Each body keeps the units under it.
 */
final readonly class OrganizationChart
{
    /**
     * @param  list<array{left: ?OrganizationalUnit, right: ?OrganizationalUnit}>  $staffRows
     * @param  Collection<int, OrganizationalUnit>  $bottomRow
     * @param  Collection<int, Agency>  $agencies
     */
    public function __construct(
        public OrganizationalUnit $head,
        public array $staffRows,
        public Collection $bottomRow,
        public Collection $agencies,
    ) {}

    /**
     * Expects the head with its active children (and theirs) loaded, each level in its «Orden».
     *
     * @param  Collection<int, Agency>|null  $agencies
     */
    public static function for(OrganizationalUnit $head, ?Collection $agencies = null): self
    {
        $ofType = fn (UnitType ...$types): Collection => $head->children
            ->filter(fn (OrganizationalUnit $unit): bool => in_array($unit->type, $types, true))
            ->values();

        // The control body answers directly to the head, so it takes a row of its own before the advisory ones.
        $staffRows = $ofType(UnitType::Control)
            ->map(fn (OrganizationalUnit $unit): array => ['left' => $unit, 'right' => null])
            ->all();

        $advisory = $ofType(UnitType::AltaDireccion, UnitType::Asesoramiento);
        $support = $ofType(UnitType::Apoyo);

        for ($row = 0; $row < max($advisory->count(), $support->count()); $row++) {
            $staffRows[] = ['left' => $advisory->get($row), 'right' => $support->get($row)];
        }

        return new self(
            $head,
            $staffRows,
            $ofType(UnitType::Linea, UnitType::Desconcentrado, UnitType::Unidad),
            $agencies ?? collect(),
        );
    }

    /**
     * The bodies under the head in reading order: control and advisory, then support, then the bottom row.
     * The narrow layout lists them this way, one under the other.
     *
     * @return Collection<int, OrganizationalUnit>
     */
    public function bodies(): Collection
    {
        $rows = collect($this->staffRows);

        return $rows->pluck('left')->filter()
            ->concat($rows->pluck('right')->filter())
            ->concat($this->bottomRow)
            ->values();
    }

    public function hasBottomRow(): bool
    {
        return $this->bottomRow->isNotEmpty() || $this->agencies->isNotEmpty();
    }

    /**
     * Types drawn in this chart, in the order of the legend. The agencies are deconcentrated bodies.
     *
     * @return list<UnitType>
     */
    public function typesShown(): array
    {
        $bodies = collect($this->staffRows)
            ->flatMap(fn (array $row): array => [$row['left'], $row['right']])
            ->filter()
            ->merge($this->bottomRow);

        $types = $bodies
            ->flatMap(fn (OrganizationalUnit $body): Collection => $body->children->concat([$body]))
            ->map(fn (OrganizationalUnit $unit): UnitType => $unit->type)
            ->push($this->head->type)
            ->when($this->agencies->isNotEmpty(), fn (Collection $types): Collection => $types->push(UnitType::Desconcentrado));

        return array_values(array_filter(UnitType::cases(), fn (UnitType $type): bool => $types->contains($type)));
    }
}
