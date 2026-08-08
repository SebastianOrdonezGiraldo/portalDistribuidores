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
<body class="bg-white">
{{--
View contract:
- Source: App\View\Components\GuestLayout.
- Expects: default slot content from auth and public entry views.
- Owns: unauthenticated shell, brand panel, and guest page framing.
- Notes: authentication validation and redirects stay in auth controllers and middleware.
--}}
@php
    $isRegistration = request()->routeIs('register*');
    $isCompact = (bool) ($compact ?? false);
@endphp
<div class="min-h-dvh bg-[radial-gradient(circle_at_6%_48%,rgba(54,177,187,0.12),transparent_30%),linear-gradient(135deg,#f8fcfc_0%,#ffffff_60%,#f5fbfb_100%)]">
    <header class="border-b border-slate-200/90 bg-white/90 backdrop-blur">
        <div class="flex h-14 items-center gap-2 px-3 sm:h-16 sm:gap-3 sm:px-6 lg:px-8">
            <a href="{{ route('catalog.index') }}" class="flex min-w-0 items-center gap-2 rounded-lg focus-ring" aria-label="Ir al catálogo">
                <img src="{{ asset('images/import-corporal-logo.png') }}" alt="Import Corporal Medical SAS" class="h-7 w-auto sm:h-8">
                <span class="truncate text-sm font-semibold text-slate-900">Portal Distribuidores</span>
            </a>
            <a href="{{ route('catalog.index') }}" class="btn btn-secondary ml-auto !min-h-10 !px-3 sm:!px-4" title="Volver al catálogo">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22v-8h6v8"/></svg>
                <span class="hidden sm:inline">Volver al catálogo</span>
                <span class="sr-only sm:hidden">Volver al catálogo</span>
            </a>
        </div>
    </header>

    <main @class([
        'mx-auto px-4 py-5 sm:px-8',
        'max-w-lg lg:min-h-[calc(100dvh-4rem)] lg:flex lg:items-center lg:py-10' => $isCompact,
        'grid max-w-[1440px] gap-8 sm:px-8 lg:min-h-[calc(100dvh-4rem)] lg:grid-cols-[minmax(0,1.1fr)_minmax(32rem,0.9fr)] lg:items-center lg:px-12 lg:py-10 xl:gap-14' => ! $isCompact,
    ])>
        @unless($isCompact)
        <section class="hidden min-w-0 flex-col justify-center lg:flex">
            <div class="max-w-3xl">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-primary">Import Corporal Medical</p>
                @if($isRegistration)
                    <h1 class="mt-3 max-w-xl text-4xl font-bold leading-[1.08] tracking-tight text-slate-950 xl:text-5xl">
                        Haz crecer tu operación<br>
                        <span class="text-brand-primary">con nosotros</span>
                    </h1>
                    <p class="mt-5 max-w-2xl text-base leading-relaxed text-slate-600 xl:text-lg">
                        Solicita el acceso de tu empresa al portal mayorista. Al validar tu información, podrás cotizar, consultar disponibilidad y centralizar tus pedidos.
                    </p>

                    <div class="mt-8 grid grid-cols-3 gap-3">
                        <div class="rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-soft"><span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-primary text-sm font-bold text-white">1</span><p class="mt-3 text-sm font-semibold text-slate-900">Registra tu empresa</p><p class="mt-1 text-xs leading-relaxed text-slate-500">Completa los datos de contacto y operación.</p></div>
                        <div class="rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-soft"><span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-primary text-sm font-bold text-white">2</span><p class="mt-3 text-sm font-semibold text-slate-900">Validamos la solicitud</p><p class="mt-1 text-xs leading-relaxed text-slate-500">Nuestro equipo comercial revisa la información.</p></div>
                        <div class="rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-soft"><span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-primary text-sm font-bold text-white">3</span><p class="mt-3 text-sm font-semibold text-slate-900">Empieza a operar</p><p class="mt-1 text-xs leading-relaxed text-slate-500">Accede a catálogo, documentos y cotizaciones.</p></div>
                    </div>

                    <div class="mt-4 rounded-2xl border border-brand-primary/20 bg-brand-primary/5 p-5">
                        <p class="text-sm font-semibold text-brand-dark">Una plataforma pensada para distribuidores.</p>
                        <p class="mt-1 text-sm leading-relaxed text-slate-600">Organiza compras recurrentes, consulta fichas técnicas y mantiene el seguimiento de cada solicitud en un solo lugar.</p>
                    </div>
                @else
                    <h1 class="mt-3 max-w-xl text-4xl font-bold leading-[1.08] tracking-tight text-slate-950 xl:text-5xl">
                        Acceso al<br>
                        <span class="text-brand-primary">portal mayorista</span>
                    </h1>
                    <p class="mt-5 max-w-2xl text-base leading-relaxed text-slate-600 xl:text-lg">
                        Gestiona tu negocio de forma ágil y segura. Explora el catálogo y administra pedidos, cotizaciones y documentos técnicos desde un mismo lugar.
                    </p>

                    <div class="mt-8 grid grid-cols-2 gap-3 xl:grid-cols-4">
                    <div class="rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-soft">
                        <div class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-xl bg-brand-primary/10 text-brand-primary"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg></div>
                        <p class="text-sm font-semibold text-slate-900">Catálogo y búsqueda</p>
                        <p class="mt-1 text-xs leading-relaxed text-slate-500">Encuentra productos fácilmente.</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-soft">
                        <div class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-xl bg-brand-primary/10 text-brand-primary"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="20.5" r="1.25"/><circle cx="17.5" cy="20.5" r="1.25"/><path d="M3 3h2l2.3 10.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 1.9-1.4L21 7H7.2"/></svg></div>
                        <p class="text-sm font-semibold text-slate-900">Pedidos y cotizaciones</p>
                        <p class="mt-1 text-xs leading-relaxed text-slate-500">Gestiona solicitudes en minutos.</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-soft">
                        <div class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-xl bg-brand-primary/10 text-brand-primary"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M8 14h8M8 18h5"/></svg></div>
                        <p class="text-sm font-semibold text-slate-900">Fichas técnicas</p>
                        <p class="mt-1 text-xs leading-relaxed text-slate-500">Documentación siempre disponible.</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-soft">
                        <div class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-xl bg-brand-primary/10 text-brand-primary"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/><path d="M8 11h.01M12 11h.01M16 11h.01"/></svg></div>
                        <p class="text-sm font-semibold text-slate-900">Atención comercial</p>
                        <p class="mt-1 text-xs leading-relaxed text-slate-500">Acompañamiento especializado.</p>
                    </div>
                    </div>
                @endif

                @if($authBanners->isNotEmpty())
                    @php
                        $authBannerCount = $authBanners->count();
                    @endphp
                    <section
                        class="auth-banner-carousel mt-7"
                        aria-label="Banners informativos"
                        @if($authBannerCount > 1)
                            x-data="{ active: 0, total: {{ $authBannerCount }}, next() { this.active = (this.active + 1) % this.total }, previous() { this.active = (this.active - 1 + this.total) % this.total } }"
                            x-init="setInterval(() => next(), 5500)"
                        @endif
                    >
                        @foreach($authBanners as $banner)
                            <figure class="auth-banner-slide" @if($authBannerCount > 1) x-show="active === {{ $loop->index }}" x-transition @endif>
                                <img src="{{ \App\Modules\Shared\Support\PublicMediaUrl::fromPublicDisk($banner->path) }}" alt="{{ $banner->title }}" title="{{ $banner->title }}" class="h-full w-full object-cover" loading="{{ $loop->first ? 'eager' : 'lazy' }}">
                            </figure>
                        @endforeach
                        @if($authBannerCount > 1)
                            <button type="button" class="auth-banner-control auth-banner-control--previous" @click="previous()" aria-label="Banner anterior">‹</button>
                            <button type="button" class="auth-banner-control auth-banner-control--next" @click="next()" aria-label="Banner siguiente">›</button>
                        @endif
                    </section>
                @endif
            </div>
        </section>
        @endunless

        <section @class([
            'flex min-w-0 w-full items-center justify-center',
            'lg:py-4' => ! $isCompact,
        ])>
            <div @class([
                'w-full',
                'max-w-md' => $isCompact,
                'max-w-2xl' => ! $isCompact && $isRegistration,
                'max-w-xl' => ! $isCompact && ! $isRegistration,
            ])>
                <div @class([
                    'rounded-2xl border border-slate-200 bg-white shadow-panel',
                    'p-5 sm:p-7' => $isCompact,
                    'p-6 sm:p-10' => ! $isCompact,
                ])>
                    {{ $slot }}
                </div>

                @unless($isCompact)
                <div class="mt-2 grid grid-cols-2 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft">
                    @if($supportWhatsappUrl)
                    <a href="{{ $supportWhatsappUrl }}" target="_blank" rel="noopener noreferrer" class="flex gap-3 p-4 transition hover:bg-emerald-50/60 focus-ring">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-6 w-6 shrink-0 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.5 11.7a8.5 8.5 0 0 1-12.56 7.5L3.5 20.5l1.3-4.2A8.5 8.5 0 1 1 20.5 11.7Z"/><path d="M8.1 7.8c.2-.5.5-.5.8-.5h.5c.2 0 .4.1.5.4l.7 1.7c.1.2.1.4 0 .6l-.5.7c-.1.2-.1.3 0 .5.6 1.1 1.5 2 2.6 2.6.2.1.3.1.5 0l.7-.5c.2-.1.4-.1.6 0l1.7.7c.3.1.4.3.4.5v.5c0 .3 0 .6-.5.8-.5.2-1.6.5-3-.1-1-.4-2.2-1.1-3.4-2.3-1-1-1.8-2.1-2.2-3.1-.6-1.4-.3-2.5-.1-3Z"/></svg>
                        <span><span class="block text-xs font-semibold text-slate-900">WhatsApp</span><span class="mt-0.5 block text-xs text-slate-500">Atención comercial inmediata</span></span>
                    </a>
                    @else
                    <span class="flex gap-3 p-4 text-slate-400">WhatsApp no configurado</span>
                    @endif
                    <a href="mailto:comercial@importcorporal.com" class="flex gap-3 border-l border-slate-200 p-4 transition hover:bg-brand-primary/5 focus-ring">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-6 w-6 shrink-0 text-brand-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                        <span><span class="block text-xs font-semibold text-slate-900">Correo comercial</span><span class="mt-0.5 block text-xs text-slate-500">Resolvemos tus consultas</span></span>
                    </a>
                </div>
                @else
                <p class="mt-4 text-center text-xs text-slate-500">
                    ¿Necesitas ayuda?
                    @if($supportWhatsappUrl)
                        <a href="{{ $supportWhatsappUrl }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-primary hover:underline">WhatsApp</a>
                    @else
                        WhatsApp no configurado
                    @endif
                </p>
                @endunless
            </div>
        </section>
    </main>
</div>
@include('layouts.partials.cookie-banner')
</body>
</html>
