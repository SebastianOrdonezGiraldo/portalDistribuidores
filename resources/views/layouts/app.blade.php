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

    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $user = auth()->user();
    $isAuthenticated = auth()->check();
    $isAdmin = $user?->isAdmin();
    $isDistributor = $user?->isDistributor();
@endphp

<div class="app-shell relative min-h-dvh">
    <x-ui.flash-stack />

    @if($isAuthenticated)
        <div data-sidebar-overlay class="fixed inset-0 z-40 hidden bg-slate-950/45 lg:hidden" aria-hidden="true"></div>

        <aside id="app-sidebar" data-sidebar class="fixed inset-y-0 left-0 z-50 flex w-60 max-w-[calc(100vw-2rem)] -translate-x-full pointer-events-none flex-col border-r border-slate-200 bg-white shadow-panel transition-transform duration-200 ease-out lg:translate-x-0 lg:pointer-events-auto">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3 focus-ring rounded-lg">
                <img src="{{ asset('images/import-corporal-logo.png') }}" alt="Import Corporal Medical SAS" class="h-9 w-auto">
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold leading-tight text-slate-900">Portal Distribuidores</p>
                    <p class="truncate text-xs text-slate-500">Import Corporal Medical SAS</p>
                </div>
            </a>

            <button type="button" data-sidebar-close class="btn btn-ghost !min-h-10 !px-2 lg:hidden" aria-label="Cerrar menú" aria-controls="app-sidebar">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" /></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-4">
            @if($isAdmin)
                <p class="sidebar-section-label">Operación</p>
                <div class="mt-2 space-y-0.5">
                    <x-ui.sidebar-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                        Inicio
                    </x-ui.sidebar-link>
                    <x-ui.sidebar-link :href="route('admin.orders.index')" :active="request()->routeIs('admin.orders.*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
                        Pedidos
                    </x-ui.sidebar-link>
                    <x-ui.sidebar-link :href="route('admin.products.index')" :active="request()->routeIs('admin.products.*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                        Productos
                    </x-ui.sidebar-link>
                    <x-ui.sidebar-link :href="route('admin.inventory.export')" :active="request()->routeIs('admin.inventory.*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v12"/><path d="m8 11 4 4 4-4"/><path d="M4 21h16"/></svg>
                        Descargar CSV InvenTree
                    </x-ui.sidebar-link>
                    <x-ui.sidebar-link :href="route('admin.categories.index')" :active="request()->routeIs('admin.categories.*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                        Categorías
                    </x-ui.sidebar-link>
                </div>

                <p class="sidebar-section-label mt-5">Gestión</p>
                <div class="mt-2 space-y-0.5">
                    <x-ui.sidebar-link :href="route('admin.distributors.index')" :active="request()->routeIs('admin.distributors.*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Distribuidores
                    </x-ui.sidebar-link>
                    <x-ui.sidebar-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Usuarios
                    </x-ui.sidebar-link>
                </div>
            @endif

            @if($isDistributor)
                <p class="sidebar-section-label">Mi Empresa</p>
                <div class="mt-2 space-y-0.5">
                    <x-ui.sidebar-link :href="route('empresa.dashboard')" :active="request()->routeIs('empresa.dashboard')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
                        Inicio
                    </x-ui.sidebar-link>
                    <x-ui.sidebar-link :href="route('empresa.orders.index')" :active="request()->routeIs('empresa.orders.*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
                        Mis Pedidos
                    </x-ui.sidebar-link>
                    @can('manageBranches')
                        <x-ui.sidebar-link :href="route('empresa.branches.index')" :active="request()->routeIs('empresa.branches.*')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            Sucursales
                        </x-ui.sidebar-link>
                    @endcan
                    @can('editCompany', \App\Modules\AuthAccess\Models\Distributor::class)
                        <x-ui.sidebar-link :href="route('empresa.profile.edit')" :active="request()->routeIs('empresa.profile.*')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 7H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                            Datos de Empresa
                        </x-ui.sidebar-link>
                    @endcan
                </div>

                <p class="sidebar-section-label mt-5">Comercial</p>
                <div class="mt-2 space-y-0.5">
                    <x-ui.sidebar-link :href="route('catalog.index')" :active="request()->routeIs('catalog.*', 'products.show')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        Catálogo
                    </x-ui.sidebar-link>
                    <x-ui.sidebar-link :href="route('cart.index')" :active="request()->routeIs('cart.*')" data-cart-target>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                        Carrito
                        <x-ui.badge :variant="($navCartCount ?? 0) > 0 ? 'brand' : 'neutral'" class="ml-auto" data-cart-badge>{{ $navCartCount ?? 0 }}</x-ui.badge>
                    </x-ui.sidebar-link>
                    <x-ui.sidebar-link :href="route('checkout.show')" :active="request()->routeIs('checkout.*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                        Nuevo Pedido
                    </x-ui.sidebar-link>
                </div>
            @endif

            <p class="sidebar-section-label mt-5">Cuenta</p>
            <div class="mt-2 space-y-0.5">
                <x-ui.sidebar-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Mi Perfil
                </x-ui.sidebar-link>
            </div>
        </div>

        <div class="border-t border-slate-200 px-4 py-4">
            <div class="mb-3 flex items-center gap-3 px-1">
                <span class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-brand-primary/15 text-sm font-semibold text-brand-dark">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ $isDistributor ? ($user->distributor?->name ?? 'Distribuidor') : 'Administrador' }}</p>
                </div>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-ghost w-full justify-center text-slate-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Cerrar sesión
                </button>
            </form>
        </div>
        </aside>
    @endif

    <div class="flex min-h-dvh min-w-0 flex-col {{ $isAuthenticated ? 'lg:pl-60' : '' }}">
        <header class="sticky top-0 z-30 border-b border-slate-200/95 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/85">
            <div class="flex flex-col gap-2 px-3 py-3 sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                    @if($isAuthenticated)
                        <button
                            type="button"
                            data-sidebar-toggle
                            class="btn btn-secondary !min-h-10 !px-2.5 lg:hidden"
                            aria-label="Abrir menú"
                            aria-controls="app-sidebar"
                            aria-expanded="false"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M3 5a1 1 0 0 1 1-1h12a1 1 0 1 1 0 2H4A1 1 0 0 1 3 5Zm0 5a1 1 0 0 1 1-1h12a1 1 0 1 1 0 2H4a1 1 0 0 1-1-1Zm1 4a1 1 0 1 0 0 2h12a1 1 0 1 0 0-2H4Z" /></svg>
                        </button>
                        <a href="{{ route('dashboard') }}" class="hidden min-w-0 items-center gap-2 rounded-lg focus-ring lg:flex">
                            <img src="{{ asset('images/import-corporal-logo.png') }}" alt="Import Corporal Medical SAS" class="h-8 w-auto">
                            <span class="truncate text-sm font-semibold text-slate-900">Portal Distribuidores</span>
                        </a>
                    @else
                        <a href="{{ route('catalog.index') }}" class="flex min-w-0 items-center gap-2 rounded-lg focus-ring">
                            <img src="{{ asset('images/import-corporal-logo.png') }}" alt="Import Corporal Medical SAS" class="h-8 w-auto">
                            <span class="hidden text-sm font-semibold text-slate-900 sm:inline">Portal Distribuidores</span>
                        </a>
                    @endif

                    <div class="ml-auto flex shrink-0 items-center gap-2">
                        @if($isAuthenticated)
                            <div class="relative" x-data="{ profileMenuOpen: false }" @keydown.escape.window="profileMenuOpen = false">
                                <button
                                    type="button"
                                    class="btn btn-ghost !min-h-10 !px-2"
                                    @click="profileMenuOpen = !profileMenuOpen"
                                    x-bind:aria-expanded="profileMenuOpen"
                                    aria-haspopup="menu"
                                    aria-controls="profile-menu-panel"
                                >
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-brand-primary/15 text-sm font-semibold text-brand-dark">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </span>
                                </button>
                                <div
                                    id="profile-menu-panel"
                                    x-cloak
                                    x-show="profileMenuOpen"
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-100"
                                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                                    @click.outside="profileMenuOpen = false"
                                    role="menu"
                                    class="absolute right-0 z-40 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white p-1 shadow-panel"
                                >
                                    <a href="{{ route('profile.edit') }}" role="menuitem" class="block rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 focus-ring">Perfil</a>
                                    <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-slate-100 pt-1">
                                        @csrf
                                        <button type="submit" role="menuitem" class="block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50 focus-ring">Cerrar sesión</button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <a href="{{ route('cart.index') }}" class="btn btn-secondary !min-h-10 !px-3 sm:!px-4">
                                Carrito
                                @if(($navCartCount ?? 0) > 0)
                                    <x-ui.badge variant="brand" class="ml-1" data-cart-badge>{{ $navCartCount }}</x-ui.badge>
                                @else
                                    <x-ui.badge variant="neutral" class="ml-1" data-cart-badge>0</x-ui.badge>
                                @endif
                            </a>
                            <a href="{{ route('login') }}" class="btn btn-primary !min-h-10 !px-3 sm:!px-4">Iniciar sesión</a>
                        @endif
                    </div>
                </div>

                @isset($catalogToolbar)
                    <div class="border-t border-slate-200/80 pt-3">
                        {{ $catalogToolbar }}
                    </div>
                @elseif($isAdmin && request()->routeIs('admin.dashboard'))
                    <div class="admin-top-toolbar">
                        <div class="admin-top-toolbar-meta">
                            <p class="admin-top-toolbar-eyebrow">Vista ejecutiva</p>
                            <p class="admin-top-toolbar-caption">Resumen comercial y señales de seguimiento</p>
                        </div>
                        <form method="GET" action="{{ route('admin.orders.index') }}" class="relative w-full md:max-w-sm lg:max-w-md">
                            <label class="sr-only" for="top-search-admin-dashboard">Buscar pedido</label>
                            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                            <input id="top-search-admin-dashboard" type="text" name="q" value="{{ request('q') }}" placeholder="Buscar pedido puntual" class="form-input !min-h-10 py-2 pl-9 pr-4">
                        </form>
                        <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary !min-h-10 !px-3">Ver pedidos</a>
                    </div>
                @elseif($isAdmin)
                    <form method="GET" action="{{ route('admin.orders.index') }}" class="relative w-full lg:mx-auto lg:max-w-2xl">
                        <label class="sr-only" for="top-search-admin">Buscar pedido</label>
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        <input id="top-search-admin" type="text" name="q" value="{{ request('q') }}" placeholder="Buscar CTC, cliente o contacto" class="form-input py-2.5 pl-9 pr-4">
                    </form>
                @elseif(!request()->routeIs('catalog.*', 'products.show'))
                    <form method="GET" action="{{ route('catalog.index') }}" class="relative w-full lg:mx-auto lg:max-w-2xl">
                        <label class="sr-only" for="top-search-catalog">Buscar producto</label>
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        <input id="top-search-catalog" type="text" name="term" value="{{ request('term') }}" placeholder="Buscar producto o categoría" class="form-input py-2.5 pl-9 pr-4">
                    </form>
                @endif
            </div>
        </header>

        <main class="min-w-0 flex-1 px-3 py-4 sm:px-6 sm:py-5 lg:px-8">
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

        @if (view()->exists('layouts.partials.footer'))
            @include('layouts.partials.footer')
        @endif
    </div>
</div>

<x-ui.modal id="confirm-action-modal" data-confirm-modal title="Confirmar acción" description="Esta acción puede impactar la operación diaria.">
    <p data-confirm-text class="text-sm text-slate-700">¿Deseas continuar?</p>

    <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <x-ui.button type="button" variant="secondary" class="w-full justify-center sm:w-auto" data-confirm-cancel>Cancelar</x-ui.button>
        <x-ui.button type="button" variant="danger" class="w-full justify-center sm:w-auto" data-confirm-approve>Confirmar</x-ui.button>
    </div>
</x-ui.modal>

@if(!$isAdmin && view()->exists('layouts.partials.whatsapp-float'))
    @include('layouts.partials.whatsapp-float')
@endif

@include('layouts.partials.cookie-banner')
</body>
</html>
