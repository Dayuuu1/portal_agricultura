<?php

namespace App\Models;

use App\Enums\UnitType;
use App\Models\Concerns\HasUniqueSlug;
use App\Models\Concerns\LogsActivity;
use App\Support\OrganizationalUnits\UnitFunctions;
use Database\Factories\OrganizationalUnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'parent_id', 'name', 'slug', 'acronym', 'type', 'description', 'functions',
    'head_name', 'head_title', 'head_photo', 'email', 'phone', 'sort_order', 'is_active',
])]
class OrganizationalUnit extends Model
{
    /** @use HasFactory<OrganizationalUnitFactory> */
    use HasFactory, HasUniqueSlug, LogsActivity;

    /** Folder of the public disk where the panel stores the photos of the people in charge. */
    public const PHOTO_DIRECTORY = 'unidades';

    /** Levels the organization chart shows: the direction, its offices and the units of each office. */
    public const MAX_DEPTH = 2;

    protected static function slugSource(): string
    {
        return 'name';
    }

    protected static function booted(): void
    {
        // The photo of someone who left the post would stay public at its address, so a replaced or
        // orphaned photo is removed with the change that stops using it.
        static::updated(function (OrganizationalUnit $unit): void {
            if ($unit->wasChanged('head_photo')) {
                self::deletePhoto($unit->getRawOriginal('head_photo'));
            }
        });

        static::deleted(fn (OrganizationalUnit $unit) => self::deletePhoto($unit->head_photo));
    }

    protected function casts(): array
    {
        return [
            'type' => UnitType::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<OrganizationalUnit, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<OrganizationalUnit, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Lists the structure the way the organization chart reads it: each body followed by the units
     * under it, siblings by their «Orden». Covers the three levels the chart shows.
     */
    #[Scope]
    protected function orderedAsTree(Builder $query): void
    {
        $units = $this->getTable();
        $parent = fn (string $column): string => "(select parent.{$column} from {$units} parent where parent.id = {$units}.parent_id)";
        $grandparent = fn (string $column): string => "(select grandparent.{$column} from {$units} parent join {$units} grandparent on grandparent.id = parent.parent_id where parent.id = {$units}.parent_id)";
        $depth = "(case when {$units}.parent_id is null then 0 when {$parent('parent_id')} is null then 1 else 2 end)";

        $query
            ->orderByRaw("case {$depth} when 0 then {$units}.sort_order when 1 then {$parent('sort_order')} else {$grandparent('sort_order')} end")
            ->orderByRaw("case {$depth} when 0 then {$units}.id when 1 then {$units}.parent_id else {$parent('parent_id')} end")
            ->orderByRaw("case {$depth} when 0 then -1 when 1 then {$units}.sort_order else {$parent('sort_order')} end")
            ->orderByRaw("case {$depth} when 0 then -1 when 1 then {$units}.id else {$units}.parent_id end")
            ->orderByRaw("case {$depth} when 2 then {$units}.sort_order else -1 end")
            ->orderBy("{$units}.id");
    }

    /**
     * Units whose public page lacks something a visitor looks for (see missingProfileItems()).
     */
    #[Scope]
    protected function withIncompleteProfile(Builder $query): void
    {
        $blank = fn (Builder $query, string $column): Builder => $query->where(fn (Builder $query) => $query->whereNull($column)->orWhere($column, ''));

        $query->where(fn (Builder $query) => $query
            ->where(fn (Builder $query) => $blank($query, 'head_name'))
            ->orWhere(fn (Builder $query) => $blank($query, 'head_photo'))
            ->orWhere(fn (Builder $query) => $blank($blank($query, 'email'), 'phone'))
            ->orWhere(fn (Builder $query) => $blank($query, 'description'))
            ->orWhere(fn (Builder $query) => $blank($query, 'functions')));
    }

    /**
     * What the public page of the unit is still missing, in the words the panel shows.
     *
     * @return list<string>
     */
    public function missingProfileItems(): array
    {
        return array_keys(array_filter([
            'responsable' => blank($this->head_name),
            'foto' => blank($this->head_photo),
            'correo o teléfono' => blank($this->email) && blank($this->phone),
            'presentación' => blank($this->description),
            'funciones' => blank($this->functions),
        ]));
    }

    /**
     * Level in the structure: 0 for the direction, 1 for its offices, 2 for their units.
     * Reads the parent relation, so load it (with its own parent_id) when listing many units.
     */
    public function depth(): int
    {
        return match (true) {
            $this->parent_id === null => 0,
            $this->parent?->parent_id === null => 1,
            default => 2,
        };
    }

    /**
     * How many levels hang below this unit: 0 without units under it, 1 with units, 2 when those have their own.
     */
    public function levelsBelow(): int
    {
        if (! $this->exists) {
            return 0;
        }

        return once(function (): int {
            $childIds = self::query()->where('parent_id', $this->getKey())->select('id');

            if (! (clone $childIds)->exists()) {
                return 0;
            }

            return self::query()->whereIn('parent_id', $childIds)->exists() ? 2 : 1;
        });
    }

    /**
     * Records that point to this unit and would lose their office if it were deleted, counted by kind.
     * Only kinds with at least one record are returned.
     *
     * @return array{units?: int, documents?: int, job_postings?: int, complaints?: int, procedure_types?: int, submissions?: int}
     */
    public function linkedRecordCounts(): array
    {
        return once(function (): array {
            $id = $this->getKey();
            $count = fn (string $table, string $column): QueryBuilder => DB::table($table)->where($column, $id)->selectRaw('count(*)');

            $row = DB::query()
                ->selectSub($count($this->getTable(), 'parent_id'), 'units')
                ->selectSub($count('documents', 'organizational_unit_id'), 'documents')
                ->selectSub($count('job_postings', 'organizational_unit_id'), 'job_postings')
                ->selectSub($count('complaints', 'organizational_unit_id'), 'complaints')
                ->selectSub($count('procedure_types', 'default_unit_id'), 'procedure_types')
                ->selectSub(DB::table('submissions')->selectRaw('count(*)')->where(fn (QueryBuilder $query) => $query
                    ->where('target_unit_id', $id)
                    ->orWhere('current_unit_id', $id)
                    ->orWhereIn('id', DB::table('submission_movements')->select('submission_id')->where('unit_id', $id))), 'submissions')
                ->first();

            return array_filter(array_map(intval(...), (array) $row));
        });
    }

    public function hasLinkedRecords(): bool
    {
        return $this->linkedRecordCounts() !== [];
    }

    /**
     * The «Funciones» text as the public page prints it (see UnitFunctions).
     */
    protected function functionsHtml(): Attribute
    {
        return Attribute::get(fn (): string => UnitFunctions::toHtml($this->functions));
    }

    protected function headPhotoUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->head_photo
            ? Storage::disk('public')->url($this->head_photo)
            : null);
    }

    /**
     * Only a file directly inside the photo folder qualifies, so a tampered value can never point the
     * deletion somewhere else.
     */
    private static function deletePhoto(mixed $path): void
    {
        if (is_string($path) && preg_match('#^'.preg_quote(self::PHOTO_DIRECTORY, '#').'/[A-Za-z0-9_-]+\.(png|webp|jpe?g)$#i', $path) === 1) {
            Storage::disk('public')->delete($path);
        }
    }
}
