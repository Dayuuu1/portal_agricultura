<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $categorySlug = $request->string('categoria')->trim()->toString();
        $term = $request->string('q')->trim()->limit(100, '')->toString();

        $posts = Post::query()->published()->with('category')
            ->when($categorySlug, fn (Builder $query) => $query->whereRelation('category', 'slug', $categorySlug))
            ->when($term, fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('title', 'like', "%{$term}%")
                ->orWhere('excerpt', 'like', "%{$term}%")))
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        return view('posts.index', [
            'posts' => $posts,
            'categories' => Category::query()->whereHas('posts', fn (Builder $query) => $query->published())->orderBy('name')->get(),
            'activeCategory' => $categorySlug,
            'term' => $term,
        ]);
    }

    public function show(Post $post): View
    {
        abort_if(! $post->is_published || ! $post->published_at?->isPast(), 404);

        Post::query()->whereKey($post->getKey())->toBase()->increment('views');

        return view('posts.show', [
            'post' => $post->load('category'),
            'related' => Post::query()->published()->with('category')
                ->whereKeyNot($post->getKey())
                ->when($post->category_id, fn (Builder $query) => $query->where('category_id', $post->category_id))
                ->latest('published_at')->limit(3)->get(),
        ]);
    }
}
