<?php

namespace App\Http\Controllers;

use App\Models\PageView;
use App\Support\Analytics\PageViewRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;

/**
 * Receives the reports of resources/js/analytics.js: one when a page opens, then the time spent on it and the
 * downloads and links followed from it.
 */
class AnalyticsController extends Controller
{
    /**
     * A page opened. Answers with a signed address for the later reports of that same page, so nobody can change
     * the figures of a page view that is not theirs.
     */
    public function store(Request $request): JsonResponse|Response
    {
        if (! PageViewRecorder::shouldTrack($request)) {
            return response()->noContent();
        }

        $data = $request->validate([
            'path' => ['required', 'string', 'max:500', 'starts_with:/'],
            'title' => ['nullable', 'string', 'max:500'],
            'referrer' => ['nullable', 'string', 'max:1000'],
            'utm_source' => ['nullable', 'string', 'max:200'],
            'utm_campaign' => ['nullable', 'string', 'max:200'],
            'width' => ['nullable', 'integer', 'min:0', 'max:20000'],
            'search_term' => ['nullable', 'string', 'max:100'],
            'search_results' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'search_scope' => ['nullable', Rule::in(['portal', 'documentos', 'noticias'])],
            'not_found' => ['nullable', 'boolean'],
        ]);

        $pageView = PageViewRecorder::record($request, $data);

        return response()->json([
            'url' => URL::temporarySignedRoute('analytics.update', now()->addHours(12), ['pageView' => $pageView], absolute: false),
        ]);
    }

    /**
     * Later report of a page: time on it when the visitor leaves, or a download or external link followed.
     */
    public function update(Request $request, PageView $pageView): Response
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['leave', 'download', 'outbound'])],
            'seconds' => ['required_if:action,leave', 'nullable', 'integer', 'min:0', 'max:86400'],
            'target' => ['required_unless:action,leave', 'nullable', 'string', 'max:1000'],
        ]);

        match ($data['action']) {
            'leave' => PageViewRecorder::updateDuration($pageView, (int) $data['seconds']),
            'download' => PageViewRecorder::recordDownload($pageView, (string) $data['target']),
            'outbound' => PageViewRecorder::recordOutbound($pageView, (string) $data['target']),
        };

        return response()->noContent();
    }
}
