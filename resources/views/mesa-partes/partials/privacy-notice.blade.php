{{-- Aviso de privacidad (Ley N° 29733, art. 18): who processes the data, why, for how long and how to exercise the rights. --}}
@php
    $siteName = \App\Models\Setting::valueOf('site_name', config('app.name'));
    $address = \App\Models\Setting::valueOf('address');
    $email = \App\Models\Setting::valueOf('email');
@endphp

<details class="rounded-xl bg-slate-50 text-sm text-slate-700 ring-1 ring-slate-200">
    <summary class="cursor-pointer px-4 py-3 font-semibold text-brand-800">Leer el aviso de privacidad</summary>
    <div class="space-y-2 border-t border-slate-200 px-4 py-4">
        <p><strong>Responsable:</strong> {{ $siteName }}{{ $address ? ', '.$address : '' }}.</p>
        <p><strong>Finalidad:</strong> identificarlo, recibir y tramitar los documentos que presente y comunicarle lo que corresponda sobre ellos.</p>
        <p><strong>Conservación:</strong> sus datos se conservan mientras dure el trámite y, después, según las normas de archivo del sector público.</p>
        <p><strong>Transferencia:</strong> no se comparten con terceros, salvo mandato legal o cuando su trámite deba remitirse a otra entidad competente.</p>
        <p>
            <strong>Sus derechos:</strong> puede ejercer los derechos de acceso, rectificación, cancelación y oposición
            @if ($email)
                escribiendo a <a href="mailto:{{ $email }}" class="font-semibold text-brand-700 hover:underline">{{ $email }}</a>.
            @else
                ante la entidad.
            @endif
        </p>
        <p>Si no proporciona los datos obligatorios, no podremos crear su cuenta. Más información en la <a href="{{ route('pages.show', 'politica-de-privacidad') }}" target="_blank" class="font-semibold text-brand-700 hover:underline">política de privacidad</a> (Ley N° 29733).</p>
    </div>
</details>
