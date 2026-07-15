<x-app-layout>
    <x-slot name="header">
        <section class="mx-auto max-w-6xl overflow-hidden rounded-2xl border border-brand-primary/15 bg-gradient-to-r from-white via-brand-primary/5 to-cyan-50 px-5 py-5 shadow-soft sm:px-7 sm:py-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex min-w-0 items-center gap-4">
                    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-primary text-white shadow-soft"><svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8" cy="9" r="1.4"/><path d="m5 17 4.5-4.5 3.2 3.2 2.3-2.3L19 17"/></svg></span>
                    <div><p class="text-xs font-bold uppercase tracking-[0.16em] text-brand-dark">Publicidad</p><h1 class="mt-0.5 text-2xl font-bold tracking-tight text-slate-950">Banners publicitarios</h1><p class="mt-1 text-sm text-slate-600">Controla las imágenes del catálogo, login y registro desde un solo lugar.</p></div>
                </div>
                <span class="rounded-full border border-brand-primary/15 bg-white px-3 py-1.5 text-xs font-bold text-brand-dark shadow-sm">{{ $bannersByPlacement->flatten()->count() }} cargados</span>
            </div>
        </section>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-6">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft">
            <div class="border-b border-slate-100 bg-slate-50/80 px-5 py-4 sm:px-6">
                <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-lg font-bold text-slate-950">Agregar banner</h2><p class="mt-1 text-sm text-slate-600">Cada ubicación muestra una imagen fija o un carrusel según la cantidad de banners.</p></div><span class="rounded-lg bg-brand-primary/10 px-3 py-1.5 text-xs font-bold text-brand-dark">Proporción recomendada · {{ $recommendedDimensions }}</span></div>
            </div>
            <form method="POST" action="{{ route('admin.catalog-banners.store') }}" enctype="multipart/form-data" class="grid gap-5 p-5 lg:grid-cols-2 lg:p-6">
                @csrf
                <div class="grid gap-5 sm:grid-cols-2">
                    <div><label class="form-label" for="catalog-banner-title">Nombre del banner</label><x-ui.input id="catalog-banner-title" name="title" :value="old('title')" maxlength="120" placeholder="Ej.: Promoción de electroterapia" required /><p class="form-help">Texto alternativo y descripción accesible.</p><x-input-error :messages="$errors->get('title')" /></div>
                    <div><label class="form-label" for="catalog-banner-placement">Mostrar en</label><x-ui.select id="catalog-banner-placement" name="placement">@foreach($placements as $placement)<option value="{{ $placement->value }}" @selected(old('placement', \App\Modules\Catalog\Enums\CatalogBannerPlacement::Catalog->value) === $placement->value)>{{ $placement->label() }}</option>@endforeach</x-ui.select><p class="form-help">Cada ubicación tiene su propio carrusel.</p><x-input-error :messages="$errors->get('placement')" /></div>
                </div>
                <div class="rounded-xl border border-dashed border-brand-primary/35 bg-brand-primary/5 p-4"><label class="form-label" for="catalog-banner-image">Archivo de imagen</label><x-ui.input id="catalog-banner-image" type="file" name="image" accept="image/jpeg,image/png,image/webp,image/avif" required /><p class="form-help">JPG, PNG, WEBP o AVIF · máximo {{ number_format($maxFileSizeKb / 1024, 0) }} MB · recomendado {{ $recommendedDimensions }}.</p><x-input-error :messages="$errors->get('image')" /><div class="mt-4 flex justify-end"><x-ui.button type="submit" variant="primary" class="justify-center">Agregar banner</x-ui.button></div></div>
            </form>
        </section>

        <div class="grid gap-6 lg:grid-cols-[14rem_minmax(0,1fr)] lg:items-start">
            <aside class="rounded-2xl border border-slate-200 bg-white p-3 shadow-soft lg:sticky lg:top-24">
                <p class="px-3 pb-2 text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Índice de ubicaciones</p>
                <nav class="grid gap-1" aria-label="Ubicaciones de banners">@foreach($placements as $placement)<a href="#banners-{{ $placement->value }}" class="group flex items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-brand-primary/10 hover:text-brand-dark"><span>Banners del {{ \Illuminate\Support\Str::lower($placement->label()) }}</span><span class="text-slate-400 transition group-hover:text-brand-primary">→</span></a>@endforeach</nav>
            </aside>

            <div class="space-y-6">
                @foreach($placements as $placement)
                    @php
                        $placementBanners = $bannersByPlacement->get($placement->value, collect());
                    @endphp
                    <section id="banners-{{ $placement->value }}" class="scroll-mt-24 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6"><div><p class="text-xs font-bold uppercase tracking-[0.15em] text-brand-primary">{{ $placement->label() }}</p><h2 class="mt-1 text-lg font-bold text-slate-950">Banners del {{ \Illuminate\Support\Str::lower($placement->label()) }}</h2><p class="mt-1 text-sm text-slate-600">Una imagen se ve fija; dos o más activan el carrusel.</p></div><span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600">{{ $placementBanners->count() }} / {{ $maxBanners }}</span></div>
                        @if($placementBanners->isEmpty())
                            <div class="m-5 flex min-h-36 flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 text-center sm:m-6"><span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white text-slate-400 shadow-sm"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="m7 16 3.5-3.5 2.5 2.5 2-2L18 16"/></svg></span><p class="mt-3 text-sm font-semibold text-slate-600">Aún no hay banners aquí</p><p class="mt-1 text-xs text-slate-500">Carga una imagen y selecciona esta ubicación.</p></div>
                        @else
                            <div class="grid gap-4 p-5 sm:grid-cols-2 sm:p-6">@foreach($placementBanners as $banner)<article class="group overflow-hidden rounded-xl border border-slate-200 bg-slate-50 transition hover:-translate-y-0.5 hover:shadow-soft"><img src="{{ \App\Modules\Shared\Support\PublicMediaUrl::fromPublicDisk($banner->path) }}" alt="{{ $banner->title }}" class="aspect-[10/3] w-full object-cover" loading="lazy"><div class="flex items-center justify-between gap-3 p-3.5"><div class="min-w-0"><p class="truncate text-sm font-bold text-slate-900">{{ $banner->title }}</p><p class="mt-0.5 text-xs text-slate-500">{{ $banner->image_width ?: '—' }} × {{ $banner->image_height ?: '—' }} px</p></div><form method="POST" action="{{ route('admin.catalog-banners.destroy', $banner) }}" data-confirm="¿Seguro que quieres eliminar el banner &laquo;{{ $banner->title }}&raquo;? Esta acción no se puede deshacer.">@csrf @method('DELETE')<button type="submit" class="inline-flex h-9 items-center rounded-lg border border-red-200 bg-white px-3 text-xs font-bold text-red-700 transition hover:border-red-300 hover:bg-red-50 focus-ring">Eliminar</button></form></div></article>@endforeach</div>
                        @endif
                    </section>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
