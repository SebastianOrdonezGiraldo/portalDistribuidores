<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Banners del catálogo" subtitle="Administra las imágenes que aparecen en la parte superior del catálogo." />
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-5">
        <section class="card p-5 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-950">Agregar banner</h2>
                    <p class="mt-1 text-sm text-slate-600">Tamaño recomendado: <strong>{{ $recommendedDimensions }}</strong>. El área del catálogo mantiene siempre esta proporción, sin importar el tamaño original de la imagen.</p>
                </div>
                <span class="rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-bold text-brand-dark">{{ $banners->count() }} / {{ $maxBanners }}</span>
            </div>

            <form method="POST" action="{{ route('admin.catalog-banners.store') }}" enctype="multipart/form-data" class="mt-5 grid gap-4 md:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)_auto] md:items-end">
                @csrf
                <div>
                    <label class="form-label" for="catalog-banner-title">Nombre del banner</label>
                    <x-ui.input id="catalog-banner-title" name="title" :value="old('title')" maxlength="120" placeholder="Ej.: Promoción de electroterapia" required />
                    <p class="form-help">Se usa como texto alternativo y descripción accesible.</p>
                    <x-input-error :messages="$errors->get('title')" />
                </div>
                <div>
                    <label class="form-label" for="catalog-banner-image">Imagen</label>
                    <x-ui.input id="catalog-banner-image" type="file" name="image" accept="image/jpeg,image/png,image/webp,image/avif" required />
                    <p class="form-help">JPG, PNG, WEBP o AVIF · máximo {{ number_format($maxFileSizeKb / 1024, 0) }} MB · recomendado {{ $recommendedDimensions }}.</p>
                    <x-input-error :messages="$errors->get('image')" />
                </div>
                <x-ui.button type="submit" variant="primary" class="justify-center">Agregar banner</x-ui.button>
            </form>
        </section>

        <section class="card p-5 sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <div><h2 class="text-lg font-bold text-slate-950">Banners cargados</h2><p class="mt-1 text-sm text-slate-600">Con una imagen se muestra estática; con dos o más se activa el carrusel.</p></div>
            </div>

            @if($banners->isEmpty())
                <div class="mt-5 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center text-sm text-slate-500">Todavía no hay banners cargados.</div>
            @else
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    @foreach($banners as $banner)
                        <article class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                            <img src="{{ \App\Modules\Shared\Support\PublicMediaUrl::fromPublicDisk($banner->path) }}" alt="{{ $banner->title }}" class="aspect-[10/3] w-full object-cover" loading="lazy">
                            <div class="flex items-center justify-between gap-3 p-3">
                                <div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-900">{{ $banner->title }}</p><p class="mt-0.5 text-xs text-slate-500">{{ $banner->image_width ?: '—' }} × {{ $banner->image_height ?: '—' }} px</p></div>
                                <form method="POST" action="{{ route('admin.catalog-banners.destroy', $banner) }}" data-confirm="¿Seguro que quieres eliminar el banner &laquo;{{ $banner->title }}&raquo;? Esta acción no se puede deshacer.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-red-700 transition hover:text-red-800 focus-ring">Eliminar</button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
