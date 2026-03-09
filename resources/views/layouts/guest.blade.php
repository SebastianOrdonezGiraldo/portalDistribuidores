<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Portal de Distribuidores') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=Sora:wght@500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="relative flex min-h-screen items-stretch">
    <div class="hidden w-[44%] flex-col justify-between border-r border-slate-200 bg-[#050200] px-10 py-8 text-white lg:flex">
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

        <div class="grid grid-cols-2 gap-3 text-xs text-slate-300">
            <div class="rounded-xl border border-slate-700 bg-slate-900/40 p-3">Catálogo y búsqueda avanzada</div>
            <div class="rounded-xl border border-slate-700 bg-slate-900/40 p-3">Pedidos con trazabilidad</div>
            <div class="rounded-xl border border-slate-700 bg-slate-900/40 p-3">Documentos PDF centralizados</div>
            <div class="rounded-xl border border-slate-700 bg-slate-900/40 p-3">Alertas operativas claras</div>
        </div>
    </div>

    <div class="flex flex-1 items-center justify-center px-4 py-8 sm:px-6 lg:px-10">
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-panel sm:p-8">
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
</body>
</html>
