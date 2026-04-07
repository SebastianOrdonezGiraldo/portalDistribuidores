<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.google-analytics')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Página no encontrada - Portal de Distribuidores</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=Sora:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { font-family: 'IBM Plex Sans', sans-serif; }
        body { background: linear-gradient(to bottom right, #f8fafc, #f1f5f9); min-height: 100vh; }
        .container { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; }
        .content { max-width: 40rem; width: 100%; padding: 1rem; text-align: center; z-index: 10; position: relative; }
        .logo { margin-bottom: 3rem; display: flex; justify-content: center; }
        .logo img { height: 2.5rem; width: auto; transition: transform 0.3s; }
        .logo img:hover { transform: scale(1.05); }
        .error-code { font-size: 6rem; font-weight: 700; color: #cbd5e1; margin-bottom: 2rem; line-height: 1; }
        h1 { font-size: 2rem; font-weight: 700; color: #0f172a; margin-bottom: 0.75rem; }
        .description { font-size: 1.125rem; color: #475569; margin-bottom: 2rem; line-height: 1.5; }
        .buttons { display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 3rem; }
        @media (min-width: 640px) { .buttons { flex-direction: row; justify-content: center; gap: 1rem; } }
        .btn { padding: 0.75rem 1.5rem; border-radius: 0.5rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.2s; cursor: pointer; border: none; white-space: nowrap; }
        .btn-primary { background-color: #1a56db; color: white; }
        .btn-primary:hover { background-color: #1e40af; }
        .btn-secondary { background-color: #e2e8f0; color: #0f172a; }
        .btn-secondary:hover { background-color: #cbd5e1; }
        .links-section { border-top: 1px solid #e2e8f0; padding-top: 2rem; margin-top: 3rem; }
        .links-title { font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 1rem; }
        .links-grid { display: grid; grid-template-columns: 1fr; gap: 0.75rem; }
        @media (min-width: 640px) { .links-grid { grid-template-columns: repeat(3, 1fr); } }
        a { color: #1a56db; text-decoration: none; font-size: 0.875rem; transitions: all 0.2s; }
        a:hover { color: #1e40af; text-decoration: underline; }
        svg { display: inline; width: 1.25rem; height: 1.25rem; }
    </style>
</head>
<body>
<div class="container">
    <div class="content">
        <!-- Logo -->
        <div class="logo">
            <a href="{{ route('catalog.index') }}" title="Ir al catálogo">
                <img src="{{ asset('images/import-corporal-logo.png') }}" alt="Import Corporal Medical SAS">
            </a>
        </div>

        <!-- Error content -->
        <div class="error-code">404</div>

        <h1>Página no encontrada</h1>
        <p class="description">
            Parece que la página que buscas no existe o ha sido movida.
            No te preocupes, podemos ayudarte a encontrar lo que necesitas.
        </p>

        <!-- Action buttons -->
        <div class="buttons">
            <a href="{{ route('catalog.index') }}" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                Ir al catálogo
            </a>
            <a href="javascript:history.back()" class="btn btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M15 19l-7-7 7-7"></path>
                </svg>
                Volver atrás
            </a>
        </div>

        <!-- Helpful links -->
        <div class="links-section">
            <p class="links-title">Sitios útiles:</p>
            <div class="links-grid">
                <a href="{{ route('catalog.index') }}">Catálogo</a>
                @if(auth()->check())
                    <a href="{{ route('cart.index') }}">Mi carrito</a>
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                @else
                    <a href="{{ route('login') }}">Iniciar sesión</a>
                    @if(app('router')->has('register'))
                        <a href="{{ route('register') }}">Registrarse</a>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
</body>
</html>
