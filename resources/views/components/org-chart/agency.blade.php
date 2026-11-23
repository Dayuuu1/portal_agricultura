{{-- Una agencia agraria como órgano desconcentrado del organigrama. --}}
@props(['agency', 'showType' => false])

<a href="{{ route('agencies.show', $agency) }}" data-type="desconcentrado" {{ $attributes->merge(['class' => 'oc-box']) }}>
    <span class="oc-name block">{{ $agency->name }}</span>
    <span class="oc-meta mt-1 block">
        @if ($showType)
            {{ \App\Enums\UnitType::Desconcentrado->getLabel() }}
        @else
            <span class="sr-only">({{ \App\Enums\UnitType::Desconcentrado->getLabel() }})</span>
        @endif
        @if ($agency->province && ! $showType)
            Provincia de {{ $agency->province }}
        @endif
    </span>
    @if ($agency->head_name)
        <span class="oc-meta mt-1.5 flex items-center gap-1.5">
            <x-heroicon-o-user class="size-3.5 shrink-0" aria-hidden="true" />
            <span><span class="sr-only">Responsable: </span>{{ $agency->head_name }}</span>
        </span>
    @endif
</a>
