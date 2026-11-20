<?php

namespace App\Http\Controllers;

use App\Enums\ContactMessageType;
use App\Http\Requests\StoreContactMessageRequest;
use App\Models\Agency;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function create(): View
    {
        return view('contact.create', [
            'types' => ContactMessageType::cases(),
            'agencies' => Agency::query()->active()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        ContactMessage::query()->create([
            ...$request->safe()->except(['privacy', 'website']),
            'ip_address' => $request->ip(),
        ]);

        return to_route('contact.create')
            ->with('status', 'Gracias. Recibimos su mensaje y le responderemos al correo indicado.');
    }
}
