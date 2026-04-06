<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Página no encontrada - Portal de Distribuidores</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=Sora:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="relative flex min-h-dvh flex-col items-center justify-center overflow-hidden bg-gradient-to-br from-slate-50 to-slate-100 px-4 sm:px-6 lg:px-8">
    <!-- Decorative elements -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1/4 h-96 w-96 bg-brand-primary/5 rounded-full blur-3xl"></div>
    <div class="absolute bottom-0 right-0 -translate-y-1/4 h-96 w-96 bg-slate-400/5 rounded-full blur-3xl"></div>

    <div class="relative z-10 w-full max-w-2xl">
        <!-- Logo -->
        <div class="mb-12 flex justify-center">
            <a href="{{ route('catalog.index') }}" class="inline-flex items-center gap-3 rounded-lg transition-transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2">
                <img src="{{ asset('images/import-corporal-logo.png') }}" alt="Import Corporal Medical SAS" class="h-10 w-auto">
            </a>
        </div>

        <!-- Error content -->
        <div class="text-center">
            <!-- Large 404 number with animation -->
            <div class="mb-8">
                <div class="relative inline-block">
                    <h1 class="text-9xl sm:text-[150px] font-bold text-slate-300 select-none leading-none">404</h1>
                    <div class="absolute inset-0 text-9xl sm:text-[150px] font-bold bg-gradient-to-br from-brand-primary to-brand-dark bg-clip-text text-transparent leading-none animate-pulse">404</div>
                </div>
            </div>

            <!-- Error message -->
            <h2 class="mb-3 text-3xl sm:text-4xl font-bold text-slate-900">Página no encontrada</h2>
            <p class="mb-8 text-lg text-slate-600 leading-relaxed">
                Parece que la página que buscas no existe o ha sido movida.
                No te preocupes, podemos ayudarte a encontrar lo que necesitas.
            </p>

            <!-- Action buttons -->
            <div class="flex flex-col gap-3 sm:flex-row sm:justify-center sm:gap-4">
                <a
                    href="{{ route('catalog.index') }}"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-brand-primary hover:bg-brand-dark text-white font-semibold rounded-lg transition-all duration-200 active:scale-95 focus:outline-none focus:ring-2 focus:ring-brand-primary focus:ring-offset-2"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    Ir al catálogo
                </a>
                <a
                    href="javascript:history.back()"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-slate-200 hover:bg-slate-300 text-slate-900 font-semibold rounded-lg transition-all duration-200 active:scale-95 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Volver atrás
                </a>
            </div>

            <!-- Helpful links -->
            <div class="mt-12 space-y-2 border-t border-slate-200 pt-8">
                <p class="text-sm font-semibold text-slate-700 mb-4">Sitios útiles:</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <a href="{{ route('catalog.index') }}" class="text-sm text-brand-primary hover:text-brand-dark hover:underline focus:outline-none focus:ring-2 focus:ring-brand-primary/50 rounded px-2 py-1 transition-colors">
                        Catálogo
                    </a>
                    @if(auth()->check())
                        <a href="{{ route('cart.index') }}" class="text-sm text-brand-primary hover:text-brand-dark hover:underline focus:outline-none focus:ring-2 focus:ring-brand-primary/50 rounded px-2 py-1 transition-colors">
                            Mi carrito
                        </a>
                        <a href="{{ route('dashboard') }}" class="text-sm text-brand-primary hover:text-brand-dark hover:underline focus:outline-none focus:ring-2 focus:ring-brand-primary/50 rounded px-2 py-1 transition-colors">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm text-brand-primary hover:text-brand-dark hover:underline focus:outline-none focus:ring-2 focus:ring-brand-primary/50 rounded px-2 py-1 transition-colors">
                            Iniciar sesión
                        </a>
                        <a href="{{ route('register') }}" class="text-sm text-brand-primary hover:text-brand-dark hover:underline focus:outline-none focus:ring-2 focus:ring-brand-primary/50 rounded px-2 py-1 transition-colors">
                            Registrarse
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
