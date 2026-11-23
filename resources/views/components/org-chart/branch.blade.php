{{-- Un órgano con las unidades que dependen de él. --}}
@props(['unit', 'showType' => false])

<div {{ $attributes->merge(['class' => 'oc-branch']) }}>
    <x-org-chart.box :unit="$unit" :show-type="$showType" />
    @if ($unit->children->isNotEmpty())
        <ul class="oc-units" aria-label="Unidades de {{ $unit->name }}">
            @foreach ($unit->children as $child)
                <li><x-org-chart.box :unit="$child" /></li>
            @endforeach
        </ul>
    @endif
</div>
