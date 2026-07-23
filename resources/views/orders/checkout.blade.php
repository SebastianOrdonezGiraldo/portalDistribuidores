{{--
View contract:
- Source: App\Modules\Orders\Http\Controllers\CheckoutController::__invoke.
- Expects: $items, $total, optional $distributor, $branches, and $departments.
- Owns: final order form, branch-prefill UI, and order summary.
- Notes: empty-cart redirects and role permissions are enforced before rendering.
--}}
<x-app-layout>
    @php
        $defaultContactName = $distributor?->contact_name ?: auth()->user()?->name;
        $defaultContactEmail = $distributor?->contact_email ?: auth()->user()?->email;
        $money = fn ($value) => '$'.number_format((float) $value, 0, ',', '.');

        $tier = $items->isNotEmpty() ? $items->first()['tier'] : \App\Modules\Shared\Enums\DistributorTier::Silver;
        $isGold = $tier === \App\Modules\Shared\Enums\DistributorTier::Gold;
        $vatRate = \App\Modules\Orders\Support\OrderLineVat::DEFAULT_RATE;

        $grossTotal = (float) $total;
        $listTotal = (float) $items->sum(fn ($item) => (float) $item['silver_unit_price'] * (int) $item['qty']);
        $savingsTotal = (float) $items->sum('line_savings');
        $netTotal = (float) $items->sum(fn ($item) => $item['is_vat_excluded']
            ? (float) $item['subtotal']
            : (float) $item['subtotal'] / (1 + $vatRate));
        $ivaTotal = max(0, $grossTotal - $netTotal);
        $savingsPct = $listTotal > 0 ? (int) round($savingsTotal / $listTotal * 100) : 0;
        $hasSavings = $savingsTotal > 0.5;

        $goldTotal = (float) $items->sum(fn ($item) => (float) $item['base_unit_price'] * (int) $item['qty']);
        $potentialSavings = max(0, $grossTotal - $goldTotal);
        $potentialSavingsPct = $grossTotal > 0 ? (int) round($potentialSavings / $grossTotal * 100) : 0;
        $hasPotentialSavings = $potentialSavings > 0.5;
        $canOpenUpgradeModal = ! $isGold && auth()->user()?->distributor !== null;

        $visibleItems = 3;
        $hiddenCount = max(0, $items->count() - $visibleItems);
    @endphp

    <x-slot name="header">
        <div class="checkout-header">
            <a href="{{ route('cart.index') }}" class="checkout-back focus-ring" aria-label="Volver al carrito">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
            </a>
            <div>
                <h1 class="text-balance text-2xl font-semibold tracking-tight text-slate-900">Confirmar pedido</h1>
                <p class="mt-1 max-w-2xl text-sm text-slate-600">Verifica la información de entrega y los detalles de tu pedido antes de finalizar.</p>
            </div>
        </div>
    </x-slot>

    <form action="{{ route('orders.store') }}" method="POST" data-loading-form class="grid min-w-0 gap-4 lg:grid-cols-[1.85fr_1fr] lg:items-start">
        @csrf

        {{-- Datos de entrega y contacto --}}
        <x-ui.card class="min-w-0 p-5 sm:p-6">
            <div class="checkout-section-title">
                <span class="checkout-section-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7h11v9H3z"/><path d="M14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.8"/><circle cx="17.5" cy="18" r="1.8"/></svg>
                </span>
                <h2 class="text-base font-bold text-slate-900">Datos de entrega y contacto</h2>
            </div>

            @if(isset($branches) && $branches->isNotEmpty())
                <div class="mt-5">
                    <label class="form-label" for="branch_select">Sucursal de entrega</label>
                    <x-ui.select id="branch_select" onchange="applyBranch(this)">
                        <option value="">— Ingresar dirección manualmente —</option>
                        @foreach($branches as $branch)
                            <option
                                value="{{ $branch->id }}"
                                data-address="{{ $branch->address }}"
                                data-city="{{ $branch->city }}"
                                {{ $branch->is_default ? 'selected' : '' }}
                            >
                                {{ $branch->name }}{{ $branch->is_default ? ' (predeterminada)' : '' }}
                                @if($branch->city) — {{ $branch->city }} @endif
                            </option>
                        @endforeach
                    </x-ui.select>
                    <p class="form-help">Selecciona una sucursal para prellenar la dirección automáticamente.</p>
                </div>
            @endif

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label" for="company_name">Razón social *</label>
                    <x-ui.input id="company_name" name="company_name" :value="old('company_name', $distributor?->name)" required />
                    <x-input-error :messages="$errors->get('company_name')" />
                </div>
                <div>
                    <label class="form-label" for="company_nit">NIT / Cédula *</label>
                    <x-ui.input
                        id="company_nit"
                        name="company_nit"
                        :value="old('company_nit', $distributor?->nit)"
                        inputmode="numeric"
                        pattern="[0-9]+"
                        placeholder="9001234567"
                        required
                    />
                    <p class="form-help">Ingresa solo números, sin puntos, espacios ni guiones.</p>
                    <x-input-error :messages="$errors->get('company_nit')" />
                </div>
                <div>
                    <label class="form-label" for="contact_name">Nombre de contacto *</label>
                    <x-ui.input id="contact_name" name="contact_name" :value="old('contact_name', $defaultContactName)" required />
                    <x-input-error :messages="$errors->get('contact_name')" />
                </div>
                <div>
                    <label class="form-label" for="contact_email">Correo de contacto *</label>
                    <x-ui.input id="contact_email" type="email" name="contact_email" :value="old('contact_email', $defaultContactEmail)" required />
                    <x-input-error :messages="$errors->get('contact_email')" />
                </div>
                <div>
                    <label class="form-label" for="phone">Teléfono *</label>
                    <x-ui.input id="phone" type="tel" name="phone" :value="old('phone', $distributor?->phone)" placeholder="300 000 0000" required />
                    <x-input-error :messages="$errors->get('phone')" />
                </div>
                <div>
                    <label class="form-label" for="company_address">Dirección *</label>
                    <x-ui.input id="company_address" name="company_address" :value="old('company_address', $distributor?->address)" required />
                    <x-input-error :messages="$errors->get('company_address')" />
                </div>
                <div>
                    <label class="form-label" for="city">Ciudad *</label>
                    <x-ui.input id="city" name="city" :value="old('city', $distributor?->city)" required />
                    <x-input-error :messages="$errors->get('city')" />
                </div>
                <div>
                    <label class="form-label" for="department">Departamento *</label>
                    <x-ui.select id="department" name="department" required>
                        <option value="">Selecciona un departamento</option>
                        @foreach($departments as $department)
                            <option value="{{ $department }}" @selected(old('department') === $department)>{{ $department }}</option>
                        @endforeach
                    </x-ui.select>
                    <x-input-error :messages="$errors->get('department')" />
                </div>
            </div>

            <div class="checkout-info-banner mt-5">
                <div class="min-w-0 flex-1">
                    <div class="flex items-start gap-2">
                        <span class="inline-flex h-8 w-8 flex-none items-center justify-center rounded-full bg-sky-100 text-sky-700" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">Información importante para la entrega</p>
                            <p class="mt-1 text-xs leading-relaxed text-slate-600">Verifica que los datos de entrega sean correctos. Cualquier error puede generar retrasos en el despacho de tu pedido.</p>
                        </div>
                    </div>
                </div>
                <div class="checkout-info-illustration hidden sm:block" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-28 text-brand-primary/30" viewBox="0 0 120 80" fill="none">
                        <rect x="8" y="28" width="52" height="32" rx="4" stroke="currentColor" stroke-width="2"/>
                        <path d="M60 36h20l12 12v12H60V36z" stroke="currentColor" stroke-width="2"/>
                        <circle cx="24" cy="64" r="6" stroke="currentColor" stroke-width="2"/>
                        <circle cx="76" cy="64" r="6" stroke="currentColor" stroke-width="2"/>
                        <path d="M4 52h8M108 20l-8 8M96 12l8 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </div>
            </div>

            <div class="mt-5">
                <label class="form-label" for="notes">Observaciones operativas (opcional)</label>
                <x-ui.textarea id="notes" name="notes" rows="4" maxlength="300" placeholder="Indica referencias de entrega, horarios o instrucciones especiales para el despacho." data-notes-input>{{ old('notes') }}</x-ui.textarea>
                <div class="mt-1 flex items-center justify-between gap-2">
                    <p class="form-help">Incluye referencias de entrega, horarios o datos de recepción.</p>
                    <p class="text-xs text-slate-400"><span data-notes-count>{{ strlen(old('notes', '')) }}</span> / 300</p>
                </div>
                <x-input-error :messages="$errors->get('notes')" />
            </div>
        </x-ui.card>

        {{-- Resumen del pedido --}}
        <aside class="min-w-0 max-w-full lg:sticky lg:top-24 lg:h-fit">
            <x-ui.card class="min-w-0 p-5">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-base font-bold text-slate-900">Resumen del pedido</h2>
                    @if($isGold)
                        <span class="checkout-tier-badge">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="m5 16 -1.6 -8 4.6 3.5L12 5l3.9 6.5L20.6 8 19 16z"/></svg>
                            Cliente ORO
                        </span>
                    @else
                        <span class="checkout-tier-badge checkout-tier-badge--silver">
                            <span class="checkout-tier-dot" aria-hidden="true"></span>
                            Cliente Plata
                        </span>
                    @endif
                </div>

                <div class="mt-4 space-y-3">
                    @foreach($items as $index => $item)
                        @php $product = $item['product']; @endphp
                        <div class="checkout-summary-item {{ $index >= $visibleItems ? 'hidden' : '' }}" @if($index >= $visibleItems) data-checkout-hidden-item @endif>
                            <x-ui.product-thumb :product="$product" size="sm" />
                            <div class="min-w-0 flex-1">
                                <p class="line-clamp-2 text-sm font-semibold text-slate-900">{{ $product->name }}</p>
                                <p class="text-xs text-slate-500">SKU: {{ $product->sku }}</p>
                                @if($item['variant_label'])
                                    <p class="text-xs text-slate-400">{{ $item['variant_label'] }}</p>
                                @endif
                                <p class="mt-0.5 text-xs text-slate-500">{{ (int) $item['qty'] }} {{ $item['unit_label'] }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-sm font-bold text-slate-900">{{ $money($item['subtotal']) }}</p>
                                <p class="text-[0.65rem] text-slate-400">{{ $item['vat_label'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($hiddenCount > 0)
                    <button type="button" class="checkout-more-toggle mt-3" data-checkout-toggle aria-expanded="false">
                        <span data-checkout-toggle-label>Ver {{ $hiddenCount }} {{ \Illuminate\Support\Str::plural('producto', $hiddenCount) }} más</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform" data-checkout-toggle-icon viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                    </button>
                @endif

                <dl class="mt-5 space-y-2.5 border-t border-slate-200 pt-4 text-sm">
                    @if($isGold && $hasSavings)
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Subtotal (precio Plata)</dt>
                            <dd class="font-medium text-slate-700">{{ $money($listTotal) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="font-medium text-amber-600">Descuento Cliente ORO ({{ $savingsPct }}%)</dt>
                            <dd class="font-semibold text-amber-600">− {{ $money($savingsTotal) }}</dd>
                        </div>
                        <div class="!mt-3 border-t border-dashed border-slate-200 pt-3"></div>
                    @elseif(! $isGold)
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Subtotal (precio Plata)</dt>
                            <dd class="font-medium text-slate-700">{{ $money($grossTotal) }}</dd>
                        </div>
                        @if($hasPotentialSavings)
                            <div class="flex items-center justify-between gap-3">
                                <dt class="checkout-savings-line font-medium text-amber-600">Ahorro si fueras Cliente ORO ({{ $potentialSavingsPct }}%)</dt>
                                <dd class="font-semibold text-amber-600">− {{ $money($potentialSavings) }}</dd>
                            </div>
                        @endif
                        <div class="!mt-3 border-t border-dashed border-slate-200 pt-3"></div>
                    @endif
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Base gravable</dt>
                        <dd class="font-medium text-slate-700">{{ $money($netTotal) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">IVA (13%)</dt>
                        <dd class="font-medium text-slate-700">{{ $money($ivaTotal) }}</dd>
                    </div>
                </dl>

                <div class="cart-review-total {{ $isGold ? 'cart-review-total--gold' : 'cart-review-total--silver' }} mt-4">
                    @if($isGold)
                        <svg class="cart-review-crown" xmlns="http://www.w3.org/2000/svg" width="72" height="72" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m5 16 -1.6 -8 4.6 3.5L12 5l3.9 6.5L20.6 8 19 16z"/></svg>
                    @endif
                    <p class="relative text-xs font-semibold uppercase tracking-wide text-slate-500">Total estimado</p>
                    <p class="relative mt-1 break-words text-3xl font-bold tracking-tight text-slate-950">{{ $money($grossTotal) }} <span class="text-base font-semibold text-slate-400">COP</span></p>
                    <p class="relative text-xs text-slate-500">IVA incluido{{ $isGold ? ' · La disponibilidad final se confirma con el equipo comercial.' : '' }}</p>
                </div>

                @if(! $isGold && $hasPotentialSavings)
                    <div class="checkout-oro-upsell mt-4">
                        <p class="text-sm font-semibold text-slate-900">
                            Con nivel <span class="text-brand-dark">ORO</span> ahorrarías
                            <span class="text-brand-dark">{{ $money($potentialSavings) }}</span> en este pedido.
                        </p>
                        @if($canOpenUpgradeModal)
                            <button type="button" class="checkout-oro-upsell-cta" @click="$dispatch('open-modal', 'tier-upgrade')">
                                Conocer beneficios ORO
                            </button>
                        @else
                            @php
                                $upgradeMessage = (string) (\App\Modules\Shared\Enums\DistributorTier::Silver->upgrade()['whatsapp_message'] ?? '');
                                $upgradeUrl = filled($upgradeMessage)
                                    ? 'https://wa.me/'.config('commerce.support.whatsapp_number', '573117479607').'?text='.rawurlencode($upgradeMessage)
                                    : null;
                            @endphp
                            @if($upgradeUrl)
                                <a href="{{ $upgradeUrl }}" target="_blank" rel="noopener noreferrer" class="checkout-oro-upsell-cta">
                                    Conocer beneficios ORO
                                </a>
                            @endif
                        @endif
                    </div>
                @endif

                @if($isGold)
                <div class="checkout-trust-row mt-4">
                    <div class="checkout-trust-item">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-brand-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 13a8 8 0 0 1 16 0"/><path d="M4 13v3a2 2 0 0 0 2 2h1v-5H6a2 2 0 0 0-2 2z"/><path d="M20 13v3a2 2 0 0 1-2 2h-1v-5h1a2 2 0 0 1 2 2z"/></svg>
                        <span>Atención comercial personalizada</span>
                    </div>
                    <div class="checkout-trust-item">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-brand-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <span>Validación final por tu asesor</span>
                    </div>
                    <div class="checkout-trust-item">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-brand-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3 5 6v5c0 4.5 3 7.5 7 10 4-2.5 7-5.5 7-10V6z"/></svg>
                        <span>Compra segura y garantizada</span>
                    </div>
                </div>
                @endif

                <div class="mt-5 space-y-3" x-data="checkoutPayment(@js([
                    'methods' => collect(\App\Modules\Shared\Enums\PaymentMethod::cases())->mapWithKeys(fn ($m) => [
                        $m->value => [
                            'label' => $m->label(),
                            'lines' => $m->instructionLines(),
                        ],
                    ])->all(),
                    'intent' => old('checkout_intent', 'quote'),
                    'method' => old('payment_method'),
                ]))">
                    <input type="hidden" name="checkout_intent" :value="intent">
                    <input type="hidden" name="payment_method" :value="method || ''">

                    <div class="grid gap-2 sm:grid-cols-2">
                        <button
                            type="submit"
                            class="btn btn-secondary w-full justify-center"
                            data-loading-label="Enviando cotización..."
                            @click="intent = 'quote'; method = null"
                        >
                            Solo cotizar
                        </button>
                        <button
                            type="button"
                            class="btn btn-primary w-full justify-center"
                            @click="intent = 'pay'; showPay = true"
                        >
                            Pagar ahora
                        </button>
                    </div>

                    <div x-show="showPay || intent === 'pay'" x-cloak class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-900">Elige cómo pagar</p>
                        <p class="mt-1 text-xs text-slate-500">Los datos de cuenta se mostrarán al seleccionar. Luego confirma el pedido.</p>

                        <div class="mt-3 grid gap-2">
                            <template x-for="(meta, key) in methods" :key="key">
                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2.5 hover:border-brand-primary/40">
                                    <input type="radio" class="mt-1" name="payment_method_ui" :value="key" x-model="method">
                                    <span>
                                        <span class="block text-sm font-semibold text-slate-900" x-text="meta.label"></span>
                                    </span>
                                </label>
                            </template>
                        </div>

                        <div x-show="method" x-cloak class="mt-3 rounded-xl border border-dashed border-amber-200 bg-amber-50/60 px-3 py-3 text-sm text-slate-700">
                            <p class="font-semibold text-amber-900" x-text="methods[method]?.label"></p>
                            <ul class="mt-1 list-disc space-y-0.5 pl-4 text-xs text-slate-600">
                                <template x-for="(line, idx) in (methods[method]?.lines || [])" :key="idx">
                                    <li x-text="line"></li>
                                </template>
                            </ul>
                        </div>

                        <x-input-error class="mt-2" :messages="$errors->get('payment_method')" />
                        <x-input-error class="mt-2" :messages="$errors->get('checkout_intent')" />

                        <button
                            type="submit"
                            class="btn btn-primary mt-4 w-full justify-center"
                            data-loading-label="Registrando pedido..."
                            @click="intent = 'pay'"
                            :disabled="!method"
                        >
                            Confirmar y pagar
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </button>
                    </div>

                    <a href="{{ route('cart.index') }}" class="btn btn-secondary w-full justify-center">Volver al carrito</a>
                </div>

                <script>
                    function checkoutPayment(initial) {
                        return {
                            methods: initial.methods || {},
                            intent: initial.intent || 'quote',
                            method: initial.method || null,
                            showPay: (initial.intent || 'quote') === 'pay',
                        };
                    }
                </script>
            </x-ui.card>
        </aside>
    </form>

    <script>
        function applyBranch(select) {
            const opt = select.options[select.selectedIndex];
            if (!opt || !opt.value) {
                return;
            }
            const addr = opt.dataset.address || '';
            const city = opt.dataset.city || '';
            if (addr) {
                document.getElementById('company_address').value = addr;
            }
            if (city) {
                document.getElementById('city').value = city;
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const branchSelect = document.getElementById('branch_select');
            if (branchSelect && branchSelect.value) {
                applyBranch(branchSelect);
            }

            const toggle = document.querySelector('[data-checkout-toggle]');
            if (toggle) {
                toggle.addEventListener('click', () => {
                    const hidden = document.querySelectorAll('[data-checkout-hidden-item]');
                    const expanded = toggle.getAttribute('aria-expanded') === 'true';
                    hidden.forEach((el) => el.classList.toggle('hidden', expanded));
                    toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                    const icon = toggle.querySelector('[data-checkout-toggle-icon]');
                    if (icon) {
                        icon.classList.toggle('rotate-180', !expanded);
                    }
                    const label = toggle.querySelector('[data-checkout-toggle-label]');
                    if (label) {
                        label.textContent = expanded
                            ? `Ver ${hidden.length} ${hidden.length === 1 ? 'producto más' : 'productos más'}`
                            : 'Ver menos productos';
                    }
                });
            }

            const notesInput = document.querySelector('[data-notes-input]');
            const notesCount = document.querySelector('[data-notes-count]');
            if (notesInput && notesCount) {
                const refreshNotesCount = () => {
                    notesCount.textContent = String(notesInput.value.length);
                };
                notesInput.addEventListener('input', refreshNotesCount);
                refreshNotesCount();
            }
        });
    </script>
</x-app-layout>
