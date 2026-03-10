<x-app-layout>
    @php
        $mainPhoto = $product->primaryPhoto ?? $product->photos->first();
        $galleryPhotos = $product->photos->take(10);

        $commercial = $commercialSnapshot ?? [];
        $availability = $commercial['availability'] ?? [
            'key' => 'check',
            'label' => 'Disponibilidad a confirmar',
            'badge' => 'warning',
            'helper' => 'Consulta disponibilidad en tiempo real.',
        ];

        $price = (float) $product->price;
        $formattedPrice = '$'.number_format($price, 0, ',', '.');
        $categoryName = $product->category?->name ?? 'Sin categoria';
        $brand = $commercial['brand'] ?? 'Marca no especificada';
        $unitLabel = $commercial['unit'] ?? 'unidad';
        $minMultiple = max(1, (int) ceil((float) ($commercial['minMultiple'] ?? 1)));
        $stepValue = (string) $minMultiple;
        $defaultQty = $stepValue;
        $stock = $commercial['stock'] ?? null;
        $canBuy = ! in_array($availability['key'], ['out', 'inactive'], true);
        $discountPercent = $commercial['discountPercent'] ?? null;
        $promoLabel = $commercial['promoLabel'] ?? null;
        $isLowStock = in_array($availability['key'], ['low', 'out'], true);
        $formatQty = static function (float|int $value): string {
            return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
        };

        $stockLabel = is_null($stock)
            ? 'A confirmar'
            : $formatQty($stock).' '.$unitLabel;

        $documents = $product->documents;
        $relatedProducts = $relatedProducts ?? collect();
        $alternativeProducts = $alternativeProducts ?? collect();
        $secondaryDocuments = $documents->filter(
            fn ($document) => ! $techSheet || $document->id !== $techSheet->id
        );

        $documentTypeLabels = [
            'tech_sheet' => 'Ficha tecnica',
            'catalog' => 'Catalogo',
            'certificate' => 'Certificado',
            'manual' => 'Manual',
        ];

        $specRows = [
            ['label' => 'SKU', 'value' => $product->sku],
            ['label' => 'Marca', 'value' => $brand],
            ['label' => 'Categoria', 'value' => $categoryName],
            ['label' => 'Ultima actualizacion', 'value' => $product->updated_at?->format('d/m/Y H:i') ?: 'Sin registro'],
        ];

        $sections = [
            ['id' => 'descripcion', 'label' => 'Descripcion'],
            ['id' => 'especificaciones', 'label' => 'Especificaciones'],
            ['id' => 'documentos', 'label' => 'Documentos'],
            ['id' => 'relacionados', 'label' => 'Relacionados'],
            ['id' => 'alternativas', 'label' => 'Alternativas'],
        ];
    @endphp

    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('catalog.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 transition hover:text-slate-900 focus-ring rounded-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
                Volver al catalogo
            </a>

            <div class="flex items-center gap-2">
                <x-ui.badge variant="neutral" class="!normal-case !tracking-normal">{{ $categoryName }}</x-ui.badge>
                <a href="{{ route('cart.index') }}" class="btn btn-secondary">Ver carrito</a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6 pb-20 lg:pb-0">
        <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22.5rem]">
            <div class="space-y-6">
                <article class="card overflow-hidden">
                    <div class="grid md:grid-cols-[0.85fr_1fr]">A
                        <div class="border-b border-slate-200 bg-slate-100 md:border-b-0 md:border-r">
                            <div class="relative aspect-[16/10]">
                                @if($mainPhoto)
                                    <img
                                        data-product-main-image
                                        src="{{ asset('storage/'.$mainPhoto->path) }}"
                                        alt="{{ $product->name }}"
                                        class="h-full w-full object-cover"
                                    >
                                @else
                                    <div class="flex h-full items-center justify-center">
                                        <div class="rounded-full border border-slate-300 bg-white p-5 text-slate-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                                <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                                                <path d="m8 13 2.5-2.5 2.5 2.5 3-3 2 2"></path>
                                                <circle cx="9" cy="9" r="1.2"></circle>
                                            </svg>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            @if($galleryPhotos->count() > 1)
                                <div class="grid grid-cols-5 gap-2 border-t border-slate-200 bg-white p-3" data-product-gallery>
                                    @foreach($galleryPhotos as $photo)
                                        <button
                                            type="button"
                                            data-product-thumb
                                            data-src="{{ asset('storage/'.$photo->path) }}"
                                            data-alt="{{ $product->name }}"
                                            class="overflow-hidden rounded-lg border {{ $loop->first ? 'border-brand-primary ring-2 ring-brand-primary/25' : 'border-slate-200 hover:border-slate-300' }} focus-ring"
                                            aria-label="Ver imagen {{ $loop->iteration }}"
                                        >
                                            <img src="{{ asset('storage/'.$photo->path) }}" alt="{{ $product->name }}" class="h-14 w-full object-cover">
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="p-5 sm:p-6">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.badge :variant="$availability['badge']" class="!normal-case !tracking-normal">{{ $availability['label'] }}</x-ui.badge>

                                @if($isLowStock)
                                    <x-ui.badge variant="warning" class="!normal-case !tracking-normal">Alta rotacion</x-ui.badge>
                                @endif

                                @if($discountPercent)
                                    <x-ui.badge variant="brand" class="!normal-case !tracking-normal">-{{ $formatQty($discountPercent) }}% dto.</x-ui.badge>
                                @endif
                            </div>

                            <h1 class="mt-4 text-3xl font-semibold leading-tight text-slate-950">{{ $product->name }}</h1>

                            <div class="mt-5 grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm sm:grid-cols-2">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Marca</p>
                                    <p class="mt-1 font-semibold text-slate-900">{{ $brand }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">SKU / Codigo</p>
                                    <p class="mt-1 font-semibold text-slate-900">{{ $product->sku }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Unidad de venta</p>
                                    <p class="mt-1 font-semibold text-slate-900">{{ ucfirst($unitLabel) }}</p>
                                </div>
                            </div>

                            <p class="mt-4 text-sm leading-6 text-slate-600">
                                {{ $availability['helper'] }}
                            </p>
                        </div>
                    </div>
                </article>
            </div>

            <aside class="xl:sticky xl:top-24 xl:h-fit">
                <article id="panel-compra" class="card p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Compra rapida</p>
                    <p class="mt-3 text-4xl font-semibold tracking-tight text-slate-950">{{ $formattedPrice }}</p>
                    <p class="text-sm text-slate-500">Precio por {{ $unitLabel }} INVA incluido</p>

                    <div class="mt-4 space-y-2 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500">Disponibilidad</span>
                            <span class="font-semibold text-slate-900">{{ $availability['label'] }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500">Stock</span>
                            <span class="font-semibold text-slate-900">{{ $stockLabel }}</span>
                        </div>
                    </div>

                    @if($promoLabel)
                        <p class="mt-3 rounded-xl border border-brand-primary/30 bg-brand-primary/10 px-3 py-2 text-sm font-medium text-[#15565c]">
                            {{ $promoLabel }}
                        </p>
                    @endif

                    <form
                        id="product-purchase-form"
                        action="{{ route('cart.store') }}"
                        method="POST"
                        data-loading-form
                        data-qty-control
                        data-min-multiple="{{ $stepValue }}"
                        class="mt-4 space-y-3"
                    >
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="hidden" name="unit_label" value="{{ $unitLabel }}">

                        <label for="purchase-qty" class="block text-sm font-semibold text-slate-700">Cantidad a agregar</label>
                        <div class="flex items-center gap-2">
                            <div class="inline-flex items-center rounded-xl border border-slate-300 bg-white p-1">
                                <button type="button" data-qty-step="-1" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-700 transition hover:bg-slate-100 focus-ring" aria-label="Disminuir cantidad" @disabled(! $canBuy)>-</button>
                                <input
                                    id="purchase-qty"
                                    type="number"
                                    name="qty"
                                    value="{{ $defaultQty }}"
                                    min="{{ $stepValue }}"
                                    step="{{ $stepValue }}"
                                    inputmode="numeric"
                                    data-qty-input
                                    data-primary-qty
                                    data-shared-qty
                                    class="w-20 border-0 bg-transparent text-center text-base font-semibold text-slate-900 focus:ring-0"
                                    @disabled(! $canBuy)
                                >
                                <button type="button" data-qty-step="1" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-700 transition hover:bg-slate-100 focus-ring" aria-label="Aumentar cantidad" @disabled(! $canBuy)>+</button>
                            </div>

                            <button type="submit" class="btn btn-primary h-11 flex-1 justify-center" data-loading-label="Agregando..." @disabled(! $canBuy)>
                                {{ $canBuy ? 'Agregar al pedido' : 'No disponible' }}
                            </button>
                        </div>

                        @if(! $canBuy)
                            <p class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800">
                                Este producto no tiene inventario inmediato. Revisa alternativas sugeridas abajo.
                            </p>
                        @endif
                    </form>

                </article>
            </aside>
        </section>

        <nav class="overflow-x-auto rounded-2xl border border-slate-200 bg-white px-3 py-2">
            <ul class="flex min-w-max items-center gap-2">
                @foreach($sections as $section)
                    <li>
                        <a href="#{{ $section['id'] }}" data-scroll-link class="inline-flex rounded-xl px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 focus-ring">
                            {{ $section['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <section id="descripcion" class="card p-5 scroll-mt-24">
            <h2 class="text-xl font-semibold text-slate-950">Descripcion</h2>
            @if(blank($product->description))
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Este producto no tiene descripcion comercial cargada todavia. Usa la informacion tecnica y comercial para validar la compra.
                </p>
            @else
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $product->description }}</p>
            @endif
        </section>

        <section id="especificaciones" class="card p-5 scroll-mt-24">
            <h2 class="text-xl font-semibold text-slate-950">Especificaciones</h2>
            <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <tbody class="divide-y divide-slate-200">
                        @foreach($specRows as $row)
                            <tr>
                                <th class="w-64 bg-slate-50 px-4 py-3 text-left font-semibold text-slate-700">{{ $row['label'] }}</th>
                                <td class="px-4 py-3 text-slate-700">{{ $row['value'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section id="documentos" class="card p-5 scroll-mt-24">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-slate-950">Documentos</h2>
                @if(!is_null($remainingDownloads))
                    <p class="text-sm text-slate-500">{{ $remainingDownloads }}/3 descargas restantes este mes</p>
                @endif
            </div>

            <div class="mt-4 space-y-3">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-900">Ficha tecnica</p>
                    <p class="mt-1 text-xs text-slate-500">Documento principal para validacion tecnica y comercial.</p>

                    <div class="mt-3">
                        @if($techSheet)
                            <a href="{{ route('documents.tech-sheet.download', $techSheet) }}" class="btn btn-secondary">
                                Descargar PDF
                            </a>
                        @else
                            <x-ui.badge variant="neutral" class="!normal-case !tracking-normal">No disponible</x-ui.badge>
                        @endif
                    </div>
                </div>

                @if($secondaryDocuments->isNotEmpty())
                    <div class="overflow-hidden rounded-2xl border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Tipo</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">Archivo</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                @foreach($secondaryDocuments as $document)
                                    <tr>
                                        <td class="px-4 py-3 text-slate-700">{{ $documentTypeLabels[$document->type] ?? ucfirst($document->type) }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $document->filename }}</td>
                                        <td class="px-4 py-3 text-right text-slate-500">Solicitar a soporte comercial</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>

        <section id="relacionados" class="scroll-mt-24">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-slate-950">Productos relacionados</h2>
                <p class="text-sm text-slate-500">Misma categoria para compra de reposicion.</p>
            </div>

            @if($relatedProducts->isNotEmpty())
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach($relatedProducts as $related)
                        <x-catalog.product-card :product="$related" />
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-600">
                    No hay productos relacionados disponibles por ahora.
                </div>
            @endif
        </section>

        <section id="alternativas" class="scroll-mt-24">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-slate-950">Alternativas similares</h2>
                <p class="text-sm text-slate-500">Opciones de sustitucion y continuidad operativa.</p>
            </div>

            @if($alternativeProducts->isNotEmpty())
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach($alternativeProducts as $alternative)
                        <x-catalog.product-card :product="$alternative" />
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-600">
                    No hay alternativas cargadas para este producto.
                </div>
            @endif
        </section>
    </div>

    <div class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 px-4 py-3 shadow-panel backdrop-blur lg:hidden">
        <div class="mx-auto flex max-w-5xl items-center gap-2">
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-slate-900">{{ $formattedPrice }}</p>
                <p class="truncate text-xs text-slate-500">por {{ $unitLabel }}</p>
            </div>

            @if($canBuy)
                <div
                    class="inline-flex items-center rounded-xl border border-slate-300 bg-white p-1"
                    data-qty-control
                    data-min-multiple="{{ $stepValue }}"
                >
                    <button type="button" data-qty-step="-1" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-700 hover:bg-slate-100 focus-ring" aria-label="Disminuir cantidad">-</button>
                    <input
                        type="number"
                        value="{{ $defaultQty }}"
                        min="{{ $stepValue }}"
                        step="{{ $stepValue }}"
                        inputmode="numeric"
                        data-qty-input
                        data-shared-qty
                        class="w-14 border-0 bg-transparent text-center text-sm font-semibold text-slate-900 focus:ring-0"
                    >
                    <button type="button" data-qty-step="1" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-700 hover:bg-slate-100 focus-ring" aria-label="Aumentar cantidad">+</button>
                </div>

                <button type="submit" form="product-purchase-form" class="btn btn-primary h-10 px-4">Agregar</button>
            @else
                <a href="#alternativas" data-scroll-link class="btn btn-secondary h-10 px-4">Ver alternativas</a>
            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const galleryThumbs = Array.from(document.querySelectorAll('[data-product-thumb]'));
            const mainImage = document.querySelector('[data-product-main-image]');

            galleryThumbs.forEach((thumb) => {
                thumb.addEventListener('click', () => {
                    if (mainImage) {
                        mainImage.src = thumb.dataset.src || mainImage.src;
                        mainImage.alt = thumb.dataset.alt || mainImage.alt;
                    }

                    galleryThumbs.forEach((item) => {
                        item.classList.remove('border-brand-primary', 'ring-2', 'ring-brand-primary/25');
                        item.classList.add('border-slate-200');
                    });

                    thumb.classList.remove('border-slate-200');
                    thumb.classList.add('border-brand-primary', 'ring-2', 'ring-brand-primary/25');
                });
            });

            const qtyRoots = Array.from(document.querySelectorAll('[data-qty-control]'));
            const sharedInputs = Array.from(document.querySelectorAll('[data-shared-qty]'));

            const parseValue = (rawValue, fallback = 1) => {
                const normalized = String(rawValue ?? '').replace(',', '.');
                const value = Number(normalized);
                return Number.isFinite(value) ? value : fallback;
            };

            const formatQty = (value) => String(Math.max(1, Math.round(value)));

            const normalizeQty = (value, minValue, multiple) => {
                if (!Number.isFinite(value) || value <= 0) {
                    return minValue;
                }

                const base = Math.max(minValue, value);
                return Math.ceil(base / multiple) * multiple;
            };

            const syncQtyInputs = (value) => {
                sharedInputs.forEach((input) => {
                    input.value = formatQty(value);
                });
            };

            const applyQty = (requestedValue, root) => {
                const input = root.querySelector('[data-qty-input]');

                if (!input || input.disabled) {
                    return;
                }

                const multiple = Math.max(1, Math.round(parseValue(root.dataset.minMultiple, 1)));
                const minValue = Math.max(multiple, parseValue(input.min || multiple, multiple));
                const normalizedValue = normalizeQty(requestedValue, minValue, multiple);

                syncQtyInputs(normalizedValue);
            };

            qtyRoots.forEach((root) => {
                const input = root.querySelector('[data-qty-input]');

                if (!input || input.disabled) {
                    return;
                }

                root.querySelectorAll('[data-qty-step]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const currentValue = parseValue(input.value, parseValue(input.min, 1));
                        const direction = Number(button.dataset.qtyStep || 0);
                        const multiple = Math.max(1, Math.round(parseValue(root.dataset.minMultiple, 1)));
                        const nextValue = currentValue + (direction * multiple);

                        applyQty(nextValue, root);
                    });
                });

                input.addEventListener('change', () => {
                    applyQty(parseValue(input.value, parseValue(input.min, 1)), root);
                });

                input.addEventListener('blur', () => {
                    applyQty(parseValue(input.value, parseValue(input.min, 1)), root);
                });
            });

            const primaryQtyInput = document.querySelector('[data-primary-qty]');

            if (primaryQtyInput) {
                const primaryRoot = primaryQtyInput.closest('[data-qty-control]');

                if (primaryRoot) {
                    applyQty(parseValue(primaryQtyInput.value, parseValue(primaryQtyInput.min, 1)), primaryRoot);
                }
            }

            document.querySelectorAll('[data-scroll-link]').forEach((link) => {
                link.addEventListener('click', (event) => {
                    const href = link.getAttribute('href');

                    if (!href || !href.startsWith('#')) {
                        return;
                    }

                    const target = document.querySelector(href);

                    if (!target) {
                        return;
                    }

                    event.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });
        });
    </script>
</x-app-layout>
