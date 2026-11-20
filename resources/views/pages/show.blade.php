<x-layouts.app :title="$page->title" :description="$page->excerpt">
    <x-page-header :title="$page->title" :subtitle="$page->excerpt" :breadcrumbs="[$page->title => null]" />

    <div class="container-site mt-10">
        <article class="card mx-auto max-w-4xl p-6 sm:p-10">
            <div class="prose prose-slate max-w-none prose-headings:font-display prose-a:text-brand-700">
                {!! str($page->body ?? '')->sanitizeHtml() !!}
            </div>
            <p class="mt-10 border-t border-slate-100 pt-4 text-xs text-slate-500">Última actualización: {{ $page->updated_at->translatedFormat('j \d\e F \d\e Y') }}</p>
        </article>
    </div>
</x-layouts.app>
