@php
    $footerCategories = $footerTopCategories ?? collect();
@endphp

<footer class="mt-8 border-t border-slate-200 bg-white/95">
    <div class="px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid gap-8 md:grid-cols-2 xl:grid-cols-4">
            <section>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Import Corporal Medical SAS</p>
                <div class="mt-3 flex items-start gap-3">
                    <img src="{{ asset('images/import-corporal-logo.png') }}" alt="Import Corporal Medical SAS" class="h-12 w-auto">
                    <p class="text-sm text-slate-600">Distribuidores mayoristas de productos de fisioterapia.</p>
                </div>
            </section>

            <section>
                <h2 class="text-sm font-semibold text-slate-900">Accesos Directos</h2>
                <ul class="mt-3 space-y-2">
                    <li>
                        <a href="{{ route('catalog.index') }}" class="text-sm text-slate-600 transition hover:text-brand-primary">Catalogo</a>
                    </li>
                    <li>
                        <a href="{{ route('cart.index') }}" class="text-sm text-slate-600 transition hover:text-brand-primary">Carrito</a>
                    </li>
                    <li>
                        <a href="{{ route('login') }}" class="text-sm text-slate-600 transition hover:text-brand-primary">Iniciar sesion</a>
                    </li>
                </ul>
            </section>

            <section>
                <h2 class="text-sm font-semibold text-slate-900">Categorias</h2>
                <ul class="mt-3 space-y-2">
                    @forelse($footerCategories as $category)
                        <li>
                            <a href="{{ route('catalog.index', ['category_id' => $category->id]) }}" class="text-sm text-slate-600 transition hover:text-brand-primary">{{ $category->name }}</a>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">Sin categorias destacadas</li>
                    @endforelse
                </ul>
            </section>

            <section>
                <h2 class="text-sm font-semibold text-slate-900">&iquest;En qu&eacute; te podemos ayudar?</h2>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li>
                        <a href="https://wa.me/573117479607?text=Hola%20vengo%20desde%20la%20plataforma" target="_blank" rel="noopener noreferrer" class="transition hover:text-brand-primary">WhatsApp de asesor&iacute;a</a>
                    </li>
                    <li>
                        <a href="tel:+573117479607" class="transition hover:text-brand-primary">+57 311 7479607</a>
                    </li>
                    <li>
                        <a href="mailto:ventas@importcorporalmedical.com" class="transition hover:text-brand-primary">ventas@importcorporalmedical.com</a>
                    </li>
                    <li>Armenia, Quindio</li>
                </ul>
            </section>
        </div>
    </div>
</footer>
