<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use Illuminate\View\View;

class AgencyController extends Controller
{
    public function index(): View
    {
        return view('agencies.index', [
            'agencies' => Agency::query()->active()->orderBy('sort_order')->get(),
        ]);
    }

    public function show(Agency $agency): View
    {
        abort_unless($agency->is_active, 404);

        return view('agencies.show', [
            'agency' => $agency,
            'otherAgencies' => Agency::query()->active()->whereKeyNot($agency->getKey())->orderBy('sort_order')->get(),
        ]);
    }
}
