{{-- Errores graves (500, 503): página independiente, sin consultas a la base de datos ni componentes del portal. --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') | {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon-32.png') }}" type="image/png" sizes="32x32">
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 1.5rem; background: #0e3b25; color: #d8f3df; font: 400 1.0625rem/1.6 system-ui, -apple-system, 'Segoe UI', sans-serif; }
        main { max-width: 34rem; text-align: center; }
        img { height: 3.5rem; width: auto; }
        .code { margin: 1.5rem 0 0; font-size: 5.5rem; font-weight: 800; line-height: 1; color: #4db676; }
        h1 { margin: 1rem 0 0; font-size: 1.875rem; line-height: 1.2; color: #fff; }
        p { margin: 1rem 0 0; }
        a { display: inline-flex; align-items: center; min-height: 2.75rem; margin-top: 2rem; padding: 0.625rem 1.25rem; border-radius: 0.75rem; background: #ebb43a; color: #082417; font-weight: 700; text-decoration: none; }
        a:hover { background: #f1c95f; }
        a:focus-visible { outline: 2px solid #fff; outline-offset: 3px; }
    </style>
</head>
<body>
    <main>
        <img src="{{ asset('images/logo-emblema.png') }}" alt="" width="62" height="44">
        <p class="code" aria-hidden="true">@yield('code')</p>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <a href="{{ url('/') }}">Volver al inicio</a>
    </main>
</body>
</html>
