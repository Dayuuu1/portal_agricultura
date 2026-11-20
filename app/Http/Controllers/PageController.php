<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Support\Pages\PageOutline;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __invoke(Page $page): View|RedirectResponse
    {
        abort_unless($page->is_published, 404);

        // «Nosotros» has its own page, which adds the mission, vision and areas to this text.
        if ($page->slug === 'nosotros') {
            return redirect()->route('institutional.about', status: 301);
        }

        return view('pages.show', [
            'page' => $page,
            'outline' => PageOutline::fromHtml($page->body),
            'documents' => $page->documents()->published()->with('documentType')->orderByPivot('sort_order')->orderByDesc('issued_at')->get(),
            'otherPages' => Page::query()->inMenu()->whereKeyNot($page->getKey())->orderBy('id')->get(['id', 'title', 'slug']),
        ]);
    }
}
