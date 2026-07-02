<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.google-analytics')
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Portal de Distribuidores') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=Sora:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="motion-page relative grid min-h-dvh overflow-x-clip lg:grid-cols-[minmax(28rem,42%)_1fr]">
    <aside class="hidden min-h-dvh flex-col justify-between overflow-hidden bg-brand-ink px-10 py-8 text-white lg:flex" data-guest-brand-shell>
        <div class="relative z-10">
            <div class="flex items-center gap-4">
                <span class="sidebar-brand-mark h-16 w-16">
                    <img src="{{ asset('images/import-corporal-logo.png') }}" alt="Import Corporal Medical SAS" class="h-12 w-auto object-contain">
                </span>
                <div>
                    <p class="font-display text-base font-semibold">Portal Distribuidores</p>
                    <p class="text-xs text-white/70">Import Corporal Medical SAS</p>
                </div>
            </div>

            <p class="mt-12 text-xs font-bold uppercase tracking-[0.16em] text-brand-mist/75">Precisión botánica B2B</p>
            <h1 class="mt-3 max-w-xl font-display text-4xl font-semibold leading-tight">Catálogo médico, documentos y CTC en una sola operación comercial.</h1>
            <p class="mt-4 max-w-md text-sm leading-6 text-white/70">Una entrada de trabajo sobria para distribuidores: menos fricción, más trazabilidad y una marca médica reconocible desde el primer acceso.</p>
        </div>

        <div class="relative z-10 space-y-5">
            <div class="rounded-2xl border border-white/10 bg-white/10 p-4 shadow-panel backdrop-blur">
                <div class="grid grid-cols-[auto_1fr_auto] items-center gap-3">
                    <x-ui.medical-icon name="catalog" class="border-white/20 bg-white/10 text-brand-mist" />
                    <div>
                        <p class="text-sm font-semibold">Buscar producto</p>
                        <p class="text-xs text-white/60">SKU, categoría o marca</p>
                    </div>
                    <span class="h-px w-8 bg-brand-accent"></span>
                    <x-ui.medical-icon name="cart" class="border-white/20 bg-white/10 text-brand-mist" />
                    <div>
                        <p class="text-sm font-semibold">Armar carrito</p>
                        <p class="text-xs text-white/60">Cantidades y variantes</p>
                    </div>
                    <span class="h-px w-8 bg-brand-accent"></span>
                    <x-ui.medical-icon name="order" class="border-white/20 bg-white/10 text-brand-mist" />
                    <div>
                        <p class="text-sm font-semibold">Generar CTC</p>
                        <p class="text-xs text-white/60">Seguimiento comercial</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 motion-stagger">
                <div class="rounded-2xl border border-white/10 bg-white/10 p-4 transition hover:-translate-y-0.5 hover:bg-white/10">
                    <x-ui.medical-icon name="document" class="mb-3 border-white/20 bg-white/10 text-brand-mist" />
                    <p class="text-xs font-semibold text-white">Documentos controlados</p>
                    <p class="mt-1 text-xs leading-snug text-white/60">Fichas técnicas, manuales y soportes protegidos.</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/10 p-4 transition hover:-translate-y-0.5 hover:bg-white/10">
                    <x-ui.medical-icon name="alert" class="mb-3 border-white/20 bg-white/10 text-brand-mist" />
                    <p class="text-xs font-semibold text-white">Trazabilidad diaria</p>
                    <p class="mt-1 text-xs leading-snug text-white/60">Estados, prioridades y señales de seguimiento.</p>
                </div>
            </div>
        </div>
    </aside>

    <main class="flex flex-1 items-center justify-center px-3 py-6 sm:px-6 lg:px-10">
        <div class="w-full max-w-md rounded-2xl border border-brand-primary/20 bg-white/95 p-5 shadow-panel backdrop-blur sm:p-8">
            <div class="mb-6 lg:hidden">
                <div class="flex items-center gap-3">
                    <span class="sidebar-brand-mark h-14 w-14">
                        <img src="{{ asset('images/import-corporal-logo.png') }}" alt="Import Corporal Medical SAS" class="h-10 w-auto object-contain">
                    </span>
                    <div>
                        <p class="font-display text-sm font-semibold text-brand-ink">Portal Distribuidores</p>
                        <p class="text-xs text-slate-500">Import Corporal Medical SAS</p>
                    </div>
                </div>
            </div>

            {{ $slot }}
        </div>
    </main>
</div>
@if(view()->exists('layouts.partials.whatsapp-float'))
    @include('layouts.partials.whatsapp-float')
@endif
@include('layouts.partials.cookie-banner')
</body>
</html>
