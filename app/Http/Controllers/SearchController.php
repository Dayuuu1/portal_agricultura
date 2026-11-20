<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\JobPosting;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $term = $request->string('q')->trim()->limit(100, '')->toString();
        $results = null;

        if (mb_strlen($term) >= 3) {
            $like = "%{$term}%";

            $results = [
                'posts' => Post::query()->published()->with('category')
                    ->where(fn (Builder $query) => $query->where('title', 'like', $like)->orWhere('excerpt', 'like', $like))
                    ->latest('published_at')->limit(6)->get(),
                'documents' => Document::query()->published()->with('documentType')
                    ->where(fn (Builder $query) => $query->where('title', 'like', $like)->orWhere('code', 'like', $like))
                    ->latest('issued_at')->limit(10)->get(),
                'jobPostings' => JobPosting::query()->published()
                    ->where(fn (Builder $query) => $query->where('code', 'like', $like)->orWhere('title', 'like', $like))
                    ->latest('starts_at')->limit(6)->get(),
                'pages' => Page::query()->published()
                    ->where(fn (Builder $query) => $query->where('title', 'like', $like)->orWhere('body', 'like', $like))
                    ->limit(4)->get(),
            ];
        }

        return view('search.index', [
            'term' => $term,
            'results' => $results,
            'total' => $results ? collect($results)->sum(fn ($items): int => $items->count()) : 0,
        ]);
    }
}
