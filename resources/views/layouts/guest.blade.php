<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
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
<div class="relative flex min-h-dvh flex-col overflow-x-clip lg:flex-row">
    <div class="hidden flex-col justify-between border-r border-slate-200 bg-brand-ink px-10 py-8 text-white lg:flex lg:w-[42%] xl:w-[40%]">
        <div>
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/import-corporal-logo.png') }}" alt="Import Corporal Medical SAS" class="h-10 w-auto">
                <div>
                    <p class="text-sm font-semibold">Portal Distribuidores</p>
                    <p class="text-xs text-slate-300">Import Corporal Medical SAS</p>
                </div>
            </div>

            <h1 class="mt-10 text-3xl font-semibold leading-tight">Operación comercial con claridad, velocidad y control.</h1>
            <p class="mt-4 max-w-md text-sm text-slate-300">Gestiona catálogo, pedidos, stock documental y seguimiento diario desde una interfaz diseñada para trabajo B2B intensivo.</p>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div class="rounded-xl border border-slate-700 bg-slate-900/40 p-3">
                <div class="mb-2 inline-flex h-7 w-7 items-center justify-center rounded-lg bg-brand-primary/20 text-brand-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                </div>
                <p class="text-xs font-semibold text-white">Catálogo y búsqueda</p>
                <p class="mt-0.5 text-xs leading-snug text-slate-400">Filtros, categorías y sinónimos para encontrar rápido.</p>
            </div>
            <div class="rounded-xl border border-slate-700 bg-slate-900/40 p-3">
                <div class="mb-2 inline-flex h-7 w-7 items-center justify-center rounded-lg bg-brand-primary/20 text-brand-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
                </div>
                <p class="text-xs font-semibold text-white">Pedidos con trazabilidad</p>
                <p class="mt-0.5 text-xs leading-snug text-slate-400">Seguimiento de estados y CTC en un solo lugar.</p>
            </div>
            <div class="rounded-xl border border-slate-700 bg-slate-900/40 p-3">
                <div class="mb-2 inline-flex h-7 w-7 items-center justify-center rounded-lg bg-brand-primary/20 text-brand-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                </div>
                <p class="text-xs font-semibold text-white">Documentos PDF</p>
                <p class="mt-0.5 text-xs leading-snug text-slate-400">Fichas técnicas y descargas controladas.</p>
            </div>
            <div class="rounded-xl border border-slate-700 bg-slate-900/40 p-3">
                <div class="mb-2 inline-flex h-7 w-7 items-center justify-center rounded-lg bg-brand-primary/20 text-brand-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                <p class="text-xs font-semibold text-white">Alertas operativas</p>
                <p class="mt-0.5 text-xs leading-snug text-slate-400">Incidencias visibles para actuar a tiempo.</p>
            </div>
        </div>
    </div>

    <div class="flex flex-1 items-center justify-center px-3 py-6 sm:px-6 lg:px-10">
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-panel sm:p-8">
            <div class="mb-6 lg:hidden">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/import-corporal-logo.png') }}" alt="Import Corporal Medical SAS" class="h-10 w-auto">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Portal Distribuidores</p>
                        <p class="text-xs text-slate-500">Import Corporal Medical SAS</p>
                    </div>
                </div>
            </div>

            {{ $slot }}
        </div>
    </div>
</div>
@include('layouts.partials.cookie-banner')
</body>
</html>
