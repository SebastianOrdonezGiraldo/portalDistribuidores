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
<body @class(['admin-dashboard-body' => auth()->check() && auth()->user()?->isAdmin() && request()->routeIs('admin.dashboard')])>
{{--
View contract:
- Source: App\View\Components\AppLayout plus App\Providers\AppServiceProvider view composers.
- Expects: optional cartCount, navCartCount, footerTopCategories, headerQuickCategories, pendingApprovalCount.
- Owns: authenticated shell, sidebar/header/footer placement, and global flash rendering.
- Notes: role checks come from the authenticated User model; route authorization stays outside the layout.
--}}
@php
    $user = auth()->user();
    $isAuthenticated = auth()->check();
    $isAdmin = $user?->isAdmin();
    $isDistributor = $user?->isDistributor();
    $isAdminDashboard = $isAdmin && request()->routeIs('admin.dashboard');
@endphp

@if($isDistributor)
    <script>
        try {
            const storedSidebarState = window.localStorage.getItem('distributor_sidebar_collapsed_v1');
            const catalogDefaultsToCollapsed = {{ request()->routeIs('catalog.*', 'products.show') ? 'true' : 'false' }};

            if (storedSidebarState === '1' || (storedSidebarState === null && catalogDefaultsToCollapsed)) {
                document.documentElement.classList.add('distributor-sidebar-collapsed');
            }
        } catch {
            // The expanded sidebar remains the safe fallback when storage is unavailable.
        }
    </script>
@endif

<div class="app-shell relative min-h-dvh">
    <x-ui.flash-stack />

    @if($isAuthenticated)
        <div data-sidebar-overlay class="fixed inset-0 z-40 hidden bg-slate-950/45 lg:hidden" aria-hidden="true"></div>

        <aside
            id="app-sidebar"
            data-sidebar
            @if($isDistributor)
                data-distributor-sidebar
                data-sidebar-default-collapsed="{{ request()->routeIs('catalog.*', 'products.show') ? 'true' : 'false' }}"
            @endif
            class="fixed inset-y-0 left-0 z-50 flex w-60 max-w-[calc(100vw-2rem)] -translate-x-full pointer-events-none flex-col border-r border-slate-200 bg-white shadow-panel transition-[width,transform] duration-200 ease-out lg:translate-x-0 lg:pointer-events-auto"
        >
        <div data-sidebar-brand class="relative flex items-center justify-between border-b border-slate-200 px-5 py-4">
            <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3 focus-ring rounded-lg" data-sidebar-brand-link>
                @if($isAdmin)
                    <span class="admin-sidebar-brand-mark" data-sidebar-logo-full aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 7V5a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v2"/><rect x="3" y="7" width="18" height="14" rx="3"/><path d="M9 14h6M12 11v6"/></svg>
                    </span>
                @else
                    <img src="{{ asset('images/import-corporal-logo.png') }}" alt="Import Corporal Medical SAS" class="h-9 w-auto" data-sidebar-logo-full>
                @endif
                @if($isDistributor)
                    <img src="{{ asset('favicon.png') }}" alt="" class="hidden h-8 w-8 object-contain" data-sidebar-logo-compact aria-hidden="true">
                @endif
                <div class="min-w-0" data-sidebar-brand-copy>
                    <p class="truncate text-sm font-semibold leading-tight text-slate-900">Portal Distribuidores</p>
                    <p class="truncate text-xs text-slate-500">Import Corporal Medical</p>
                </div>
            </a>

            @if($isDistributor)
                <button
                    type="button"
                    data-sidebar-collapse
                    class="btn btn-secondary absolute -right-3 top-1/2 z-10 hidden !h-7 !min-h-7 !w-7 -translate-y-1/2 rounded-full !p-0 shadow-sm lg:inline-flex"
                    aria-label="Contraer menú lateral"
                    aria-controls="app-sidebar"
                    aria-expanded="true"
                    title="Contraer menú"
                >
                    <svg data-sidebar-collapse-icon xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                    <svg data-sidebar-expand-icon xmlns="http://www.w3.org/2000/svg" class="hidden h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                </button>
            @endif

            <button type="button" data-sidebar-close class="btn btn-ghost !min-h-10 !px-2 lg:hidden" aria-label="Cerrar menú" aria-controls="app-sidebar">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 0 1 1.414 0L10 8.586l4.293-4.293a1 1 0 1 1 1.414 1.414L11.414 10l4.293 4.293a1 1 0 0 1-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 0 1-1.414-1.414L8.586 10 4.293 5.707a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" /></svg>
            </button>
        </div>

        <div data-sidebar-navigation class="flex-1 overflow-y-auto px-4 py-4">
            @if($isAdmin)
                <div class="space-y-0.5">
                    <x-ui.sidebar-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m3 10 9-7 9 7"/><path d="M5 9v11h14V9"/><path d="M9 20v-6h6v6"/></svg>
                        Inicio
                    </x-ui.sidebar-link>
                </div>

                <p class="sidebar-section-label mt-5">Operación</p>
                <div class="mt-2 space-y-0.5">
                    <x-ui.sidebar-link :href="route('admin.orders.index')" :active="request()->routeIs('admin.orders.*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
                        Pedidos
                    </x-ui.sidebar-link>
                    <x-ui.sidebar-link :href="route('admin.products.index')" :active="request()->routeIs('admin.products.*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                        Productos
                    </x-ui.sidebar-link>
                    <x-ui.sidebar-link :href="route('admin.categories.index')" :active="request()->routeIs('admin.categories.*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                        Categorías
                    </x-ui.sidebar-link>
                </div>

                <p class="sidebar-section-label mt-5">Gestión comercial</p>
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

                <p class="sidebar-section-label mt-5">Marketing</p>
                <div class="mt-2 space-y-0.5">
                    <x-ui.sidebar-link :href="route('admin.catalog-banners.index')" :active="request()->routeIs('admin.catalog-banners.*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8" cy="9" r="1.4"/><path d="m5 17 4.5-4.5 3.2 3.2 2.3-2.3L19 17"/></svg>
                        Banners de login
                    </x-ui.sidebar-link>
                </div>

                <p class="sidebar-section-label mt-5">Configuración</p>
                <div class="mt-2 space-y-0.5">
                    <x-ui.sidebar-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21H9.6v-.09A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.2 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H2.4V9.6h.09A1.7 1.7 0 0 0 4.2 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 8.6 4.2a1.7 1.7 0 0 0 1-.6A1.7 1.7 0 0 0 10 2.5v-.1h4v.09a1.7 1.7 0 0 0 1 1.71 1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 8.6a1.7 1.7 0 0 0 .6 1 1.7 1.7 0 0 0 1.1.4h.1v4h-.09a1.7 1.7 0 0 0-1.71 1Z"/></svg>
                        Ajustes
                    </x-ui.sidebar-link>
                </div>
            @endif

            @if($isDistributor)
                <p class="sidebar-section-label">Mi Empresa</p>
                <div class="mt-2 space-y-0.5" data-portal-tour-target="company">
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

            @if(!$isAdmin)
                <p class="sidebar-section-label mt-5">Cuenta</p>
                <div class="mt-2 space-y-0.5">
                    <x-ui.sidebar-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        Mi Perfil
                    </x-ui.sidebar-link>
                </div>
            @endif
        </div>

        <div data-sidebar-footer class="border-t border-slate-200 px-4 py-4">
            <div data-sidebar-user class="mb-3 flex items-center gap-3 px-1">
                <span class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-brand-primary/15 text-sm font-semibold text-brand-dark">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </span>
                <div class="min-w-0" data-sidebar-user-copy>
                    <p class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                    <p class="truncate text-xs text-slate-500">{{ $isDistributor ? ($user->distributor?->name ?? 'Distribuidor') : 'Administrador global' }}</p>
                </div>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-ghost w-full justify-center text-slate-600" data-sidebar-logout title="Cerrar sesión">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Cerrar sesión
                </button>
            </form>
        </div>
        </aside>
    @endif

    <div data-app-content class="flex min-h-dvh min-w-0 flex-col transition-[padding] duration-200 {{ $isAuthenticated ? 'lg:pl-60' : '' }}">
        <header class="sticky top-0 z-30 border-b border-slate-200/95 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/85">
            <div class="flex flex-col gap-2 px-3 py-3 sm:px-6 lg:px-8">
                <div class="relative flex min-w-0 items-center gap-2 sm:gap-3 {{ isset($catalogToolbar) || $isAdminDashboard ? 'flex-wrap md:flex-nowrap' : '' }}">
                    @if($isAuthenticated)
                        <button
                            type="button"
                            data-sidebar-toggle
                            class="btn btn-secondary !min-h-10 !px-2.5 lg:hidden {{ isset($catalogToolbar) ? 'hidden' : '' }}"
                            aria-label="Abrir menú"
                            aria-controls="app-sidebar"
                            aria-expanded="false"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M3 5a1 1 0 0 1 1-1h12a1 1 0 1 1 0 2H4A1 1 0 0 1 3 5Zm0 5a1 1 0 0 1 1-1h12a1 1 0 1 1 0 2H4a1 1 0 0 1-1-1Zm1 4a1 1 0 1 0 0 2h12a1 1 0 1 0 0-2H4Z" /></svg>
                        </button>
                        @if($isAdmin && ! $isAdminDashboard)
                            <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-2 rounded-lg focus-ring">
                                <img src="{{ asset('images/import-corporal-logo.png') }}" alt="Import Corporal Medical SAS" class="h-8 w-auto">
                                <span class="truncate text-sm font-semibold text-slate-900">Portal Distribuidores</span>
                            </a>
                        @endif
                    @else
                        <a href="{{ route('catalog.index') }}" class="flex min-w-0 items-center gap-2 rounded-lg focus-ring">
                            <img src="{{ asset('images/import-corporal-logo.png') }}" alt="Import Corporal Medical SAS" class="h-8 w-auto">
                            <span class="hidden text-sm font-semibold text-slate-900 sm:inline">Portal Distribuidores</span>
                        </a>
                    @endif

                    @isset($catalogToolbar)
                        <div class="catalog-desktop-header-search hidden lg:block" data-portal-tour-target="search-desktop">
                            <div class="catalog-header-search">
                                {{ $catalogToolbar }}
                            </div>
                        </div>
                    @endisset

                    @if($isAdminDashboard)
                        <form method="GET" action="{{ route('admin.orders.index') }}" class="admin-dashboard-global-search">
                            <label class="sr-only" for="admin-dashboard-search">Buscar pedido</label>
                            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                            <input id="admin-dashboard-search" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar CTC, cliente o contacto..." autocomplete="off">
                        </form>
                    @elseif($isDistributor && !isset($catalogToolbar))
                        <form method="GET" action="{{ route('catalog.index') }}" class="global-header-catalog-search" data-global-catalog-search>
                            <label class="sr-only" for="global-header-catalog-search">Buscar productos en el catálogo</label>
                            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="11" cy="11" r="8" />
                                <path d="m21 21-4.35-4.35" />
                            </svg>
                            <input
                                id="global-header-catalog-search"
                                type="search"
                                name="term"
                                value="{{ request()->routeIs('catalog.*') ? request('term') : '' }}"
                                placeholder="Buscar producto, SKU, marca o categoría"
                                class="catalog-search-input"
                                autocomplete="off"
                            >
                        </form>
                    @elseif($isAdmin && !isset($catalogToolbar))
                        <form method="GET" action="{{ route('admin.orders.index') }}" class="global-header-admin-search" data-global-admin-search>
                            <label class="sr-only" for="global-header-admin-search">Buscar pedidos</label>
                            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="11" cy="11" r="8" />
                                <path d="m21 21-4.35-4.35" />
                            </svg>
                            <input
                                id="global-header-admin-search"
                                type="search"
                                name="q"
                                value="{{ request()->routeIs('admin.orders.index') ? request('q') : '' }}"
                                placeholder="Buscar CTC, cliente, contacto o correo"
                                class="catalog-search-input"
                                autocomplete="off"
                            >
                        </form>
                    @endif

                    <div
                        class="ml-auto flex shrink-0 items-center gap-2 {{ $isAuthenticated ? '' : 'relative' }}"
                        @if(! $isAuthenticated)
                            x-data="{ guestMenuOpen: false }"
                            @keydown.escape.window="guestMenuOpen = false"
                        @endif
                    >
                        @if($isAuthenticated)
                            @if($isAdminDashboard)
                                <div class="relative" x-data="{ createMenuOpen: false }" @keydown.escape.window="createMenuOpen = false">
                                    <button type="button" class="admin-dashboard-icon-button admin-dashboard-create-button" @click="createMenuOpen = !createMenuOpen" x-bind:aria-expanded="createMenuOpen" aria-haspopup="menu" aria-controls="admin-create-menu" aria-label="Crear registro">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                                    </button>
                                    <div id="admin-create-menu" x-cloak x-show="createMenuOpen" x-transition @click.outside="createMenuOpen = false" class="admin-dashboard-create-menu" role="menu">
                                        <p class="px-3 pb-1.5 pt-2 text-[0.68rem] font-semibold uppercase tracking-[0.08em] text-slate-400">Crear nuevo</p>
                                        <a href="{{ route('admin.products.create') }}" role="menuitem">Producto</a>
                                        <a href="{{ route('admin.distributors.create') }}" role="menuitem">Distribuidor</a>
                                        <a href="{{ route('admin.users.create') }}" role="menuitem">Usuario</a>
                                    </div>
                                </div>
                                <a href="{{ route('admin.orders.index', ['status' => 'pending_approval']) }}" class="admin-dashboard-icon-button relative" aria-label="Pedidos en revisión" title="Pedidos en revisión">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg>
                                    @if(($pendingApprovalCount ?? 0) > 0)
                                        <span class="admin-dashboard-notification-count">{{ min(99, $pendingApprovalCount) }}</span>
                                    @endif
                                </a>
                            @endif
                            @if($isDistributor)
                                @if(request()->routeIs('catalog.index'))
                                    <button type="button" class="portal-tour-help" data-portal-tour-restart aria-label="Ver tutorial del portal" title="Ver tutorial">?</button>
                                @else
                                    <a href="{{ route('catalog.index', ['tutorial' => 1]) }}" class="portal-tour-help" aria-label="Ver tutorial del portal" title="Ver tutorial">?</a>
                                @endif
                            @endif
                            @isset($catalogToolbar)
                                <div class="relative" x-data="{ catalogMenuOpen: false }" @keydown.escape.window="catalogMenuOpen = false">
                                    <a href="{{ route('cart.index') }}" class="btn btn-secondary relative !min-h-10 !min-w-10 !px-2.5 lg:hidden" aria-label="Ver carrito" data-cart-target>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
                                        <x-ui.badge :variant="($navCartCount ?? 0) > 0 ? 'brand' : 'neutral'" class="absolute -right-1.5 -top-1.5 !min-w-5 !px-1" data-cart-badge>{{ $navCartCount ?? 0 }}</x-ui.badge>
                                    </a>
                                    <button type="button" class="btn btn-secondary !min-h-10 !px-2.5 lg:hidden" @click="$dispatch('catalog-filters')" aria-label="Abrir filtros" data-portal-tour-target="filters-mobile">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                                    </button>
                                    <button type="button" class="btn btn-secondary !min-h-10 !px-2.5 lg:hidden" @click="catalogMenuOpen = !catalogMenuOpen" x-bind:aria-expanded="catalogMenuOpen.toString()" aria-label="Abrir menú del catálogo" aria-controls="catalog-header-menu" data-portal-tour-catalog-menu-toggle>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                                    </button>
                                    <div id="catalog-header-menu" x-cloak x-show="catalogMenuOpen" x-transition class="absolute right-0 top-[calc(100%+0.65rem)] z-[80] w-[min(32rem,calc(100vw-2rem))] max-h-[calc(100dvh-6rem)] overflow-y-auto rounded-2xl border border-slate-200 bg-white p-4 shadow-panel sm:p-6">
                                        <div class="mx-auto grid w-full max-w-2xl gap-6">
                                            <div class="catalog-menu-panel-tools" data-portal-tour-target="search-mobile"><p class="catalog-menu-panel-title">Encuentra lo que necesitas</p><div class="catalog-header-search">{{ $catalogToolbar }}</div></div>
                                            <a href="{{ route('profile.edit') }}" class="btn btn-primary min-h-12 w-full justify-center !px-4">Mi perfil</a>
                                        </div>
                                    </div>
                                </div>
                            @endisset
                            @if($isDistributor)
                                <a href="{{ route('cart.index') }}" class="btn btn-secondary relative {{ isset($catalogToolbar) ? 'hidden lg:inline-flex' : 'hidden sm:inline-flex' }} !min-h-10 !min-w-10 !px-2.5" aria-label="Ver carrito" title="Carrito" data-cart-target>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
                                    @if(($navCartCount ?? 0) > 0)
                                        <x-ui.badge variant="brand" class="absolute -right-1.5 -top-1.5 !min-w-5 !px-1" data-cart-badge>{{ $navCartCount }}</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="neutral" class="absolute -right-1.5 -top-1.5 !min-w-5 !px-1" data-cart-badge>0</x-ui.badge>
                                    @endif
                                </a>
                            @endif
                            <div class="{{ isset($catalogToolbar) ? 'hidden lg:block' : '' }} relative" x-data="{ profileMenuOpen: false }" @keydown.escape.window="profileMenuOpen = false">
                                <button
                                    type="button"
                                    class="{{ $isAdminDashboard ? 'admin-dashboard-profile-trigger' : 'btn btn-ghost !min-h-10 !px-2' }}"
                                    @click="profileMenuOpen = !profileMenuOpen"
                                    x-bind:aria-expanded="profileMenuOpen"
                                    aria-haspopup="menu"
                                    aria-controls="profile-menu-panel"
                                >
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-brand-primary/15 text-sm font-semibold text-brand-dark">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </span>
                                    @if($isAdminDashboard)
                                        <span class="hidden max-w-32 truncate text-sm font-medium text-slate-700 xl:block">{{ $user->name }}</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="hidden h-4 w-4 text-slate-400 xl:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                                    @endif
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
                            @isset($catalogToolbar)
                                <a href="{{ route('cart.index') }}" class="btn btn-secondary relative !min-h-10 !min-w-10 !px-2.5 lg:hidden" aria-label="Ver carrito" data-cart-target>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
                                    <x-ui.badge :variant="($navCartCount ?? 0) > 0 ? 'brand' : 'neutral'" class="absolute -right-1.5 -top-1.5 !min-w-5 !px-1" data-cart-badge>{{ $navCartCount ?? 0 }}</x-ui.badge>
                                </a>
                                <button type="button" class="btn btn-secondary !min-h-10 !px-2.5 lg:hidden" @click="$dispatch('catalog-filters')" aria-label="Abrir filtros">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M7 12h10M10 18h4"/></svg>
                                </button>
                            @endisset
                            <button
                                type="button"
                                class="btn btn-secondary !min-h-10 !px-2.5 {{ isset($catalogToolbar) ? 'lg:hidden' : 'sm:hidden' }}"
                                @click="guestMenuOpen = !guestMenuOpen"
                                x-bind:aria-expanded="guestMenuOpen.toString()"
                                aria-label="Abrir menú"
                                aria-controls="guest-header-menu"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                            </button>
                            <div
                                id="guest-header-menu"
                                x-cloak
                                x-show="guestMenuOpen"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                                class="{{ isset($catalogToolbar) ? 'absolute right-0 top-[calc(100%+0.65rem)] z-[80] w-[min(32rem,calc(100vw-2rem))] max-h-[calc(100dvh-6rem)] overflow-y-auto rounded-2xl border border-slate-200 bg-white p-4 shadow-panel sm:p-6' : 'absolute right-0 top-[calc(100%+0.5rem)] z-40 grid w-52 gap-2 rounded-xl border border-slate-200 bg-white p-2 shadow-panel sm:hidden' }}"
                            >
                                @isset($catalogToolbar)
                                    <div class="mx-auto grid w-full max-w-2xl gap-6">
                                        <div class="catalog-menu-panel-tools">
                                            <p class="catalog-menu-panel-title">Encuentra lo que necesitas</p>
                                            <div class="catalog-header-search">
                                                {{ $catalogToolbar }}
                                            </div>
                                        </div>
                                        <a href="{{ route('register') }}" class="btn btn-secondary min-h-12 w-full justify-center !px-4">Ser distribuidor</a>
                                        <a href="{{ route('login') }}" class="btn btn-primary min-h-12 w-full justify-center !px-4">Iniciar sesión</a>
                                    </div>
                                @else
                                    <a href="{{ route('cart.index') }}" class="btn btn-secondary w-full justify-between !px-3">
                                        Carrito
                                        <x-ui.badge :variant="($navCartCount ?? 0) > 0 ? 'brand' : 'neutral'" data-cart-badge>{{ $navCartCount ?? 0 }}</x-ui.badge>
                                    </a>
                                    <a href="{{ route('register') }}" class="btn btn-secondary w-full justify-center !px-3">Ser distribuidor</a>
                                    <a href="{{ route('login') }}" class="btn btn-primary w-full justify-center !px-3">Iniciar sesión</a>
                                @endisset
                            </div>
                            <a href="{{ route('cart.index') }}" class="btn btn-secondary relative {{ isset($catalogToolbar) ? 'hidden lg:inline-flex' : 'hidden sm:inline-flex' }} !min-h-10 !min-w-10 !px-2.5" aria-label="Ver carrito" title="Carrito" data-cart-target>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
                                @if(($navCartCount ?? 0) > 0)
                                    <x-ui.badge variant="brand" class="absolute -right-1.5 -top-1.5 !min-w-5 !px-1" data-cart-badge>{{ $navCartCount }}</x-ui.badge>
                                @else
                                    <x-ui.badge variant="neutral" class="absolute -right-1.5 -top-1.5 !min-w-5 !px-1" data-cart-badge>0</x-ui.badge>
                                @endif
                            </a>
                            <a href="{{ route('register') }}" class="btn btn-secondary {{ isset($catalogToolbar) ? 'hidden lg:inline-flex' : 'hidden sm:inline-flex' }} !min-h-10 !px-3 sm:!px-4">Ser distribuidor</a>
                            <a href="{{ route('login') }}" class="btn btn-primary {{ isset($catalogToolbar) ? 'hidden lg:inline-flex' : 'hidden sm:inline-flex' }} !min-h-10 !px-3 sm:!px-4">Iniciar sesión</a>
                        @endif
                    </div>
                </div>

            </div>
        </header>

        <main class="min-w-0 flex-1 px-3 py-4 sm:px-6 sm:py-5 lg:px-8 {{ $isAdminDashboard ? 'admin-dashboard-main' : '' }}">
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

        @if (!$isAdminDashboard && view()->exists('layouts.partials.footer'))
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

@guest
    <div id="login-required-modal" data-modal data-login-required-modal class="fixed inset-0 z-[90] hidden items-end justify-center bg-slate-950/55 p-3 backdrop-blur-sm sm:items-center sm:p-4" role="dialog" aria-modal="true" aria-labelledby="login-required-modal-title">
        <div class="login-required-panel">
            <button type="button" class="login-required-close" data-login-required-close aria-label="Cerrar">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                    <path d="M6 6l12 12"></path>
                    <path d="M18 6L6 18"></path>
                </svg>
            </button>

            <div class="login-required-icon">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                    <circle cx="9" cy="20.5" r="1.25"></circle>
                    <circle cx="17.5" cy="20.5" r="1.25"></circle>
                    <path d="M3 3h2l2.3 10.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 1.9-1.4L21 7H7.2"></path>
                </svg>
            </div>

            <p class="login-required-eyebrow">Carrito distribuidor</p>
            <h2 id="login-required-modal-title" class="login-required-title">Inicia sesión para continuar</h2>
            <p class="login-required-copy">
                Te llevamos al login y, al entrar, vuelves al producto para completar el agregado al carrito.
            </p>

            <div class="login-required-actions">
                <a href="{{ route('login') }}" class="btn btn-primary w-full justify-center" data-login-required-link>Iniciar sesión</a>
                <button type="button" class="btn btn-secondary w-full justify-center" data-login-required-close>Seguir viendo catálogo</button>
            </div>
        </div>
    </div>
@endguest

@if(!$isAdmin && !request()->routeIs('catalog.*', 'products.show') && view()->exists('layouts.partials.whatsapp-float'))
    @include('layouts.partials.whatsapp-float')
@endif

@if($isDistributor && request()->routeIs('catalog.index'))
    @include('layouts.partials.portal-tour')
@endif

@include('layouts.partials.cookie-banner')
</body>
</html>
