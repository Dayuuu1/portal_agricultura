<?php

namespace App\Http\Controllers;

use App\Enums\JobPostingStatus;
use App\Models\JobPosting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobPostingController extends Controller
{
    public function index(Request $request): View
    {
        $status = JobPostingStatus::tryFrom($request->string('estado')->toString());
        $year = $request->integer('anio') ?: null;

        $jobPostings = JobPosting::query()->published()
            ->withCount('files')
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($year, fn (Builder $query) => $query->whereYear('starts_at', $year))
            ->latest('starts_at')
            ->paginate(10)
            ->withQueryString();

        return view('job-postings.index', [
            'jobPostings' => $jobPostings,
            'statuses' => JobPostingStatus::cases(),
            'years' => JobPosting::query()->published()->whereNotNull('starts_at')->pluck('starts_at')
                ->map(fn ($date): int => $date->year)->unique()->sortDesc()->values(),
            'filters' => ['status' => $status, 'year' => $year],
        ]);
    }

    public function show(JobPosting $jobPosting): View
    {
        abort_unless($jobPosting->is_published, 404);

        return view('job-postings.show', [
            'jobPosting' => $jobPosting->load(['files', 'organizationalUnit']),
        ]);
    }
}
