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
@php
    $user = auth()->user();
    $isAuthenticated = auth()->check();
    $isAdmin = $user?->isAdmin();
    $isDistributor = $user?->isDistributor();
@endphp

<div class="relative min-h-screen">
    <x-ui.flash-stack />

    @if($isAuthenticated)
        <div data-sidebar-overlay class="fixed inset-0 z-40 hidden bg-slate-950/45 lg:hidden"></div>

        <aside data-sidebar class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-slate-200 bg-white shadow-panel transition-transform duration-200 lg:translate-x-0">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3 focus-ring rounded-lg">
                <img src="{{ asset('images/import-corporal-logo.png') }}" alt="Import Corporal Medical SAS" class="h-9 w-auto">
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold leading-tight text-slate-900">Portal Distribuidores</p>
                    <p class="truncate text-xs text-slate-500">Import Corporal Medical SAS</p>
                </div>
            </a>

            <button type="button" data-sidebar-close class="btn btn-ghost !px-2 lg:hidden" aria-label="Cerrar menú">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" /></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-4">
            @if($isAdmin)
                <p class="px-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Operación</p>
                <div class="mt-2 space-y-1">
                    <x-ui.sidebar-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">Dashboard</x-ui.sidebar-link>
                    <x-ui.sidebar-link :href="route('admin.orders.index')" :active="request()->routeIs('admin.orders.*')">Pedidos</x-ui.sidebar-link>
                    <x-ui.sidebar-link :href="route('admin.products.index')" :active="request()->routeIs('admin.products.*')">Productos</x-ui.sidebar-link>
                    <x-ui.sidebar-link :href="route('admin.categories.index')" :active="request()->routeIs('admin.categories.*')">Categorías</x-ui.sidebar-link>
                </div>

                <p class="mt-5 px-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Gestión</p>
                <div class="mt-2 space-y-1">
                    <x-ui.sidebar-link :href="route('admin.distributors.index')" :active="request()->routeIs('admin.distributors.*')">Distribuidores</x-ui.sidebar-link>
                    <x-ui.sidebar-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">Usuarios</x-ui.sidebar-link>
                </div>
            @endif

            @if($isDistributor)
                <p class="px-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Comercial</p>
                <div class="mt-2 space-y-1">
                    <x-ui.sidebar-link :href="route('catalog.index')" :active="request()->routeIs('catalog.*', 'products.show')">Catálogo</x-ui.sidebar-link>
                    <x-ui.sidebar-link :href="route('cart.index')" :active="request()->routeIs('cart.*')">
                        Carrito
                        @if(($navCartCount ?? 0) > 0)
                            <x-ui.badge variant="brand" class="ml-auto">{{ $navCartCount }}</x-ui.badge>
                        @endif
                    </x-ui.sidebar-link>
                    <x-ui.sidebar-link :href="route('checkout.show')" :active="request()->routeIs('checkout.*')">Nuevo Pedido</x-ui.sidebar-link>
                </div>
            @endif

            <p class="mt-5 px-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Cuenta</p>
            <div class="mt-2 space-y-1">
                <x-ui.sidebar-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')">Mi Perfil</x-ui.sidebar-link>
            </div>
        </div>

        <div class="border-t border-slate-200 px-4 py-4">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <x-ui.button type="submit" variant="secondary" class="w-full justify-center">Cerrar sesión</x-ui.button>
            </form>
        </div>
        </aside>
    @endif

    <div class="flex min-h-screen flex-col {{ $isAuthenticated ? 'lg:pl-72' : '' }}">
        <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur">
            <div class="flex items-center gap-3 px-4 py-3 sm:px-6 lg:px-8">
                @if($isAuthenticated)
                    <button type="button" data-sidebar-toggle class="btn btn-secondary !px-2 lg:hidden" aria-label="Abrir menú">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M3 5a1 1 0 0 1 1-1h12a1 1 0 1 1 0 2H4A1 1 0 0 1 3 5Zm0 5a1 1 0 0 1 1-1h12a1 1 0 1 1 0 2H4a1 1 0 0 1-1-1Zm1 4a1 1 0 1 0 0 2h12a1 1 0 1 0 0-2H4Z" /></svg>
                    </button>
                @else
                    <a href="{{ route('catalog.index') }}" class="flex items-center gap-2 rounded-lg focus-ring">
                        <img src="{{ asset('images/import-corporal-logo.png') }}" alt="Import Corporal Medical SAS" class="h-8 w-auto">
                        <span class="hidden text-sm font-semibold text-slate-900 sm:inline">Portal Distribuidores</span>
                    </a>
                @endif

                <div class="hidden min-w-0 flex-1 md:block">
                    @if($isAuthenticated)
                        <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                        <p class="truncate text-xs text-slate-500">
                            @if($isDistributor)
                                {{ $user->distributor?->name ?? 'Distribuidor' }}
                            @else
                                Equipo administrativo
                            @endif
                        </p>
                    @else
                        <p class="truncate text-sm font-semibold text-slate-900">Compra como invitado</p>
                        <p class="truncate text-xs text-slate-500">Agrega productos al carrito y confirma tu pedido sin login.</p>
                    @endif
                </div>

                <div class="flex-1">
                    @if($isAdmin)
                        <form method="GET" action="{{ route('admin.orders.index') }}" class="relative max-w-xl">
                            <label class="sr-only" for="top-search-admin">Buscar pedido</label>
                            <input id="top-search-admin" type="text" name="q" value="{{ request('q') }}" placeholder="Buscar CTC, cliente o contacto" class="form-input py-2 pl-3 pr-10">
                            <button type="submit" class="absolute right-1 top-1/2 -translate-y-1/2 rounded-lg px-2 py-1 text-xs text-slate-500 hover:bg-slate-100">Buscar</button>
                        </form>
                    @else
                        <form method="GET" action="{{ route('catalog.index') }}" class="relative max-w-xl">
                            <label class="sr-only" for="top-search-catalog">Buscar producto</label>
                            <input id="top-search-catalog" type="text" name="term" value="{{ request('term') }}" placeholder="Buscar producto o categoría" class="form-input py-2 pl-3 pr-10">
                            <button type="submit" class="absolute right-1 top-1/2 -translate-y-1/2 rounded-lg px-2 py-1 text-xs text-slate-500 hover:bg-slate-100">Buscar</button>
                        </form>
                    @endif
                </div>

                @if($isAuthenticated)
                    <details data-action-menu class="relative">
                        <summary class="btn btn-ghost !px-2">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-sm font-semibold text-slate-700">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </span>
                        </summary>
                        <div class="absolute right-0 z-40 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white p-1 shadow-panel">
                            <a href="{{ route('profile.edit') }}" class="block rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">Perfil</a>
                            <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-slate-100 pt-1">
                                @csrf
                                <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">Cerrar sesión</button>
                            </form>
                        </div>
                    </details>
                @else
                    <a href="{{ route('cart.index') }}" class="btn btn-secondary">
                        Carrito
                        @if(($navCartCount ?? 0) > 0)
                            <x-ui.badge variant="brand" class="ml-1">{{ $navCartCount }}</x-ui.badge>
                        @else
                            <x-ui.badge variant="neutral" class="ml-1">0</x-ui.badge>
                        @endif
                    </a>
                    <a href="{{ route('login') }}" class="btn btn-primary">Iniciar sesión</a>
                @endif
            </div>
        </header>

        <main class="flex-1 px-4 py-5 sm:px-6 lg:px-8">
            @if (isset($breadcrumbs))
                <x-ui.breadcrumbs :items="$breadcrumbs" />
            @endif

            @isset($header)
                <div class="mb-5">
                    {{ $header }}
                </div>
            @endisset

            {{ $slot }}
        </main>
    </div>
</div>

<x-ui.modal id="confirm-action-modal" data-confirm-modal title="Confirmar acción" description="Esta acción puede impactar la operación diaria.">
    <p data-confirm-text class="text-sm text-slate-700">¿Deseas continuar?</p>

    <div class="mt-5 flex justify-end gap-2">
        <x-ui.button type="button" variant="secondary" data-confirm-cancel>Cancelar</x-ui.button>
        <x-ui.button type="button" variant="danger" data-confirm-approve>Confirmar</x-ui.button>
    </div>
</x-ui.modal>
</body>
</html>
