{{-- Tells the portal statistics (resources/js/analytics.js) what was searched and how many results it gave. --}}
@props(['term' => null, 'results' => 0, 'scope'])

@if (filled($term))
    <span hidden data-analytics-search data-term="{{ $term }}" data-results="{{ (int) $results }}" data-scope="{{ $scope }}"></span>
@endif
