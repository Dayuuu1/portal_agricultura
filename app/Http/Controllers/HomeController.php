<?php

namespace App\Http\Controllers;

use App\Enums\JobPostingStatus;
use App\Models\Agency;
use App\Models\Document;
use App\Models\JobPosting;
use App\Models\Link;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\Slide;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($request->filled('ddownload') && $redirect = Redirect::findForRequest($request)) {
            $redirect->registerHit();

            return redirect($redirect->new_path, 301);
        }

        $posts = Post::query()->published()->with('category')
            ->orderByDesc('is_featured')->latest('published_at')
            ->limit(5)->get();

        return view('home', [
            'slides' => Slide::query()->active()->orderBy('sort_order')->get(),
            'leadPost' => $posts->first(),
            'otherPosts' => $posts->slice(1)->sortByDesc('published_at')->values(),
            'jobPostings' => JobPosting::query()->published()
                ->orderByRaw('case when status in (?, ?) then 0 else 1 end', [JobPostingStatus::EnCurso->value, JobPostingStatus::EnEvaluacion->value])
                ->latest('starts_at')->limit(4)->get(),
            'documents' => Document::query()->published()->with('documentType')
                ->latest('issued_at')->limit(5)->get(),
            'agencies' => Agency::query()->active()->orderBy('sort_order')->get(),
            'interestLinks' => Link::query()->inCarousel()->get(),
            'stats' => [
                'documents' => Document::query()->published()->count(),
                'jobPostings' => JobPosting::query()->published()->count(),
                'posts' => Post::query()->published()->count(),
            ],
        ]);
    }
}
