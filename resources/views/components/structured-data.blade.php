{{-- Schema.org data for search engines (App\Support\Seo\StructuredData). The JSON escapes «<», so no text can close the tag. --}}
@props(['data'])

@if (! empty($data))
    <script type="application/ld+json">{!! \App\Support\Seo\StructuredData::toJson($data) !!}</script>
@endif
