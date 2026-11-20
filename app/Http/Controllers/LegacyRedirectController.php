<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\OrganizationalUnit;
use App\Models\Page;
use App\Models\Redirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Resolves URLs from the former WordPress site (e.g. /oficina-de-control-institucional/)
 * to their new location so existing links and search results keep working.
 */
class LegacyRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless($request->isMethod('GET'), 404);

        if ($redirect = Redirect::findForRequest($request)) {
            $redirect->registerHit();

            return redirect($redirect->new_path, 301);
        }

        $slug = trim($request->path(), '/');

        abort_unless(preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug), 404);

        $unit = OrganizationalUnit::query()->active()->where('slug', $slug)->first()
            ?? OrganizationalUnit::query()->active()->where('slug', 'like', "%{$slug}%")->orderByRaw('length(slug)')->first();

        if ($unit) {
            return redirect()->route('institutional.unit', $unit, 301);
        }

        if ($agency = Agency::query()->active()->where('slug', $slug)->first()) {
            return redirect()->route('agencies.show', $agency, 301);
        }

        if ($page = Page::query()->published()->where('slug', $slug)->first()) {
            return redirect()->route('pages.show', $page, 301);
        }

        abort(404);
    }
}
