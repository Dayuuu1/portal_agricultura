{{-- Caja de un órgano o unidad en el organigrama; su color sale del tipo (ver .oc-box en app.css). --}}
@props(['unit', 'showType' => false])

<a href="{{ route('institutional.unit', $unit) }}" data-type="{{ $unit->type->value }}" {{ $attributes->merge(['class' => 'oc-box']) }}>
    <span class="flex items-start justify-between gap-3">
        <span class="oc-name">{{ $unit->name }}</span>
        @if ($unit->acronym)
            <abbr title="{{ $unit->name }}" class="oc-acronym no-underline">{{ $unit->acronym }}</abbr>
        @endif
    </span>
    @if ($showType)
        <span class="oc-meta mt-1 block">{{ $unit->type->getLabel() }}</span>
    @elseif ($unit->type !== \App\Enums\UnitType::Unidad)
        <span class="sr-only">({{ $unit->type->getLabel() }})</span>
    @endif
    @if ($unit->head_name)
        <span class="oc-meta mt-1.5 flex items-center gap-1.5">
            <x-heroicon-o-user class="size-3.5 shrink-0" aria-hidden="true" />
            <span><span class="sr-only">Responsable: </span>{{ $unit->head_name }}</span>
        </span>
    @endif
</a>
