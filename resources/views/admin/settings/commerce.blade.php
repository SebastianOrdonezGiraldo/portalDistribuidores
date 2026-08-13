{{--
View contract:
- Source: App\Modules\Admin\Http\Controllers\CommerceSettingsAdminController::edit.
- Expects: $current (CommercePricingRules), $history, $latestRule, example price fields.
- Owns: commercial pricing rules form, Alpine preview, audit history.
--}}
<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            title="Reglas comerciales"
            subtitle="Configura umbrales y mínimos; los precios Gold/Silver vienen de ContaPyme."
        />
    </x-slot>

@php
        $money = fn ($value) => '$'.number_format((float) $value, 0, ',', '.');
        $displayPercent = old('silver_markup_percent', $current->silverMarkupPercentageDisplay());
        $displayRounding = (int) old('silver_rounding_multiple', $current->silverRoundingMultiple);
        $silverMinEnabled = (bool) old('silver_min_order_enabled', $current->silverMinOrderEnabled);
        $silverMinAmount = (int) old('silver_min_order_amount', $current->silverMinOrderAmount);
        $goldMinEnabled = (bool) old('gold_min_order_enabled', $current->goldMinOrderEnabled);
        $goldMinAmount = (int) old('gold_min_order_amount', $current->goldMinOrderAmount);
        $goldThresholdEnabled = (bool) old('gold_pricing_threshold_enabled', $current->goldPricingThresholdEnabled);
        $goldThresholdAmount = (int) old('gold_pricing_threshold_amount', $current->goldPricingThresholdAmount);
        $goldThresholdBasis = old('gold_pricing_threshold_basis', $current->goldPricingThresholdBasis->value);
@endphp

<div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
    Los precios Gold y Silver ya no se calculan con incremento ni redondeo:
    provienen de la sincronización local de ContaPyme. Estos campos históricos
    se conservan solo para compatibilidad de configuración.
</div>

    @php
        $advisorByTier = $advisors ?? [];
    @endphp

    <div
        class="grid gap-4 lg:grid-cols-[1.6fr_1fr]"
        x-data="{
            percent: '{{ $displayPercent }}',
            rounding: {{ $displayRounding }},
            silverMinEnabled: {{ $silverMinEnabled ? 'true' : 'false' }},
            goldMinEnabled: {{ $goldMinEnabled ? 'true' : 'false' }},
            goldThresholdEnabled: {{ $goldThresholdEnabled ? 'true' : 'false' }},
            goldThresholdBasis: '{{ $goldThresholdBasis }}'
        }"
    >
        <div class="space-y-4">
            <form
                action="{{ route('admin.settings.commerce.update') }}"
                method="POST"
                data-loading-form
                data-confirm="¿Actualizar las reglas comerciales? Los precios visibles y los carritos abiertos se recalcularán con la nueva configuración."
                class="space-y-4"
            >
            @csrf
            @method('PATCH')

            <x-ui.card class="p-5">
                <h2 class="card-title">Precios por nivel</h2>
                <p class="mt-1 text-sm text-slate-600">
                    Gold y Silver se sincronizan desde las listas de precios de ContaPyme.
                </p>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="silver_markup_percent">Incremento del precio Plata sobre el precio Oro (histórico)</label>
                        <div class="relative">
                            <x-ui.input
                                id="silver_markup_percent"
                                name="silver_markup_percent"
                                inputmode="decimal"
                                :value="$displayPercent"
                                x-model="percent"
                                required
                            />
                            <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm font-semibold text-slate-500">%</span>
                        </div>
                        <p class="form-help">Se conserva para compatibilidad y auditoría; no modifica los precios sincronizados.</p>
                        <x-input-error :messages="$errors->get('silver_markup_percent')" />
                    </div>

                    <div>
                        <label class="form-label" for="silver_rounding_multiple">Redondeo histórico (sin uso en precios)</label>
                        <x-ui.select id="silver_rounding_multiple" name="silver_rounding_multiple" x-model.number="rounding" required>
                            <option value="100" @selected($displayRounding === 100)>$100</option>
                            <option value="500" @selected($displayRounding === 500)>$500</option>
                            <option value="1000" @selected($displayRounding === 1000)>$1.000</option>
                        </x-ui.select>
                        <x-input-error :messages="$errors->get('silver_rounding_multiple')" />
                    </div>
                </div>

                <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50/80 p-4 text-sm text-slate-700">
                    <p class="font-semibold text-slate-900">Qué cambia y qué no</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        <li>El precio base de cada producto (Oro) en administración no se modifica: sigue siendo el valor guardado en el producto.</li>
                        <li>El precio Silver se lee del último valor válido sincronizado desde ContaPyme.</li>
                        <li>Los clientes Plata y Oro usan la pareja Gold/Silver local en catálogo, carrito y checkout.</li>
                        <li>Los pedidos confirmados conservan sus precios históricos.</li>
                    </ul>
                </div>

                <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50/70 p-4 text-sm text-amber-950">
                    <p class="font-semibold">Al guardar, solo se actualizan los mínimos y umbrales. Los precios comerciales se actualizan con <code>contapyme:sync-prices</code>.</p>
                    <p class="mt-2 text-amber-900/80">Las líneas existentes de pedidos editados conservan su precio histórico.</p>
                </div>
            </x-ui.card>

            <x-ui.card class="p-5">
                <h2 class="card-title">Pedidos mínimos</h2>
                <p class="mt-1 text-sm text-slate-600">
                    Define el valor mínimo que debe alcanzar un pedido para poder finalizar la compra.
                </p>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="space-y-3 rounded-xl border border-slate-200 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="text-sm font-semibold text-slate-900">Cliente Plata</h3>
                            <span
                                class="rounded-full px-2 py-0.5 text-[0.65rem] font-semibold"
                                :class="silverMinEnabled ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                                x-text="silverMinEnabled ? 'Activo' : 'Inactivo'"
                            ></span>
                        </div>
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="silver_min_order_enabled" value="1" class="rounded border-slate-300" x-model="silverMinEnabled" @checked($silverMinEnabled)>
                            Exigir pedido mínimo para Cliente Plata
                        </label>
                        <div>
                            <label class="form-label" for="silver_min_order_amount">Monto mínimo</label>
                            <x-ui.input
                                id="silver_min_order_amount"
                                name="silver_min_order_amount"
                                type="number"
                                min="1"
                                step="1"
                                :value="$silverMinAmount"
                                x-bind:readonly="!silverMinEnabled"
                                x-bind:class="!silverMinEnabled && 'opacity-60'"
                                required
                            />
                            <p class="form-help">El valor incluye IVA</p>
                            <x-input-error :messages="$errors->get('silver_min_order_amount')" />
                        </div>
                        <p class="text-xs text-slate-500" x-show="!silverMinEnabled" x-cloak>
                            Los clientes de este nivel pueden comprar sin pedido mínimo
                        </p>
                    </div>

                    <div class="space-y-3 rounded-xl border border-slate-200 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <h3 class="text-sm font-semibold text-slate-900">Cliente Oro</h3>
                            <span
                                class="rounded-full px-2 py-0.5 text-[0.65rem] font-semibold"
                                :class="goldMinEnabled ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                                x-text="goldMinEnabled ? 'Activo' : 'Inactivo'"
                            ></span>
                        </div>
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="gold_min_order_enabled" value="1" class="rounded border-slate-300" x-model="goldMinEnabled" @checked($goldMinEnabled)>
                            Exigir pedido mínimo para Cliente Oro
                        </label>
                        <div>
                            <label class="form-label" for="gold_min_order_amount">Monto mínimo</label>
                            <x-ui.input
                                id="gold_min_order_amount"
                                name="gold_min_order_amount"
                                type="number"
                                min="1"
                                step="1"
                                :value="$goldMinAmount"
                                x-bind:readonly="!goldMinEnabled"
                                x-bind:class="!goldMinEnabled && 'opacity-60'"
                                required
                            />
                            <p class="form-help">El valor incluye IVA</p>
                            <x-input-error :messages="$errors->get('gold_min_order_amount')" />
                        </div>
                        <p class="text-xs text-slate-500" x-show="!goldMinEnabled" x-cloak>
                            Los clientes de este nivel pueden comprar sin pedido mínimo
                        </p>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card class="p-5">
                <h2 class="card-title">Activación de precios Oro</h2>
                <p class="mt-1 text-sm text-slate-600">
                    Define el monto que debe alcanzar un Cliente Oro para recibir los precios especiales de su nivel.
                </p>

                <div class="mt-5 space-y-4">
                    <div class="flex items-start justify-between gap-3">
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="gold_pricing_threshold_enabled" value="1" class="rounded border-slate-300" x-model="goldThresholdEnabled" @checked($goldThresholdEnabled)>
                            Exigir monto mínimo para activar precios Oro
                        </label>
                        <span
                            class="rounded-full px-2 py-0.5 text-[0.65rem] font-semibold"
                            :class="goldThresholdEnabled ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                            x-text="goldThresholdEnabled ? 'Activo' : 'Inactivo'"
                        ></span>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="form-label" for="gold_pricing_threshold_amount">Monto requerido</label>
                            <x-ui.input
                                id="gold_pricing_threshold_amount"
                                name="gold_pricing_threshold_amount"
                                type="number"
                                min="1"
                                step="1"
                                :value="$goldThresholdAmount"
                                x-bind:readonly="!goldThresholdEnabled"
                                x-bind:class="!goldThresholdEnabled && 'opacity-60'"
                                required
                            />
                            <x-input-error :messages="$errors->get('gold_pricing_threshold_amount')" />
                        </div>
                        <div>
                            <label class="form-label" for="gold_pricing_threshold_basis">Base de evaluación</label>
                            <x-ui.select
                                id="gold_pricing_threshold_basis"
                                name="gold_pricing_threshold_basis"
                                x-model="goldThresholdBasis"
                                x-bind:disabled="!goldThresholdEnabled"
                                x-bind:class="!goldThresholdEnabled && 'opacity-60'"
                                required
                            >
                                <option value="gold_candidate" @selected($goldThresholdBasis === 'gold_candidate')>Total calculado con precios Oro</option>
                                <option value="silver_candidate" @selected($goldThresholdBasis === 'silver_candidate')>Total calculado con precios Plata</option>
                            </x-ui.select>
                            {{-- Disabled selects are omitted from POST; keep a hidden mirror. --}}
                            <template x-if="!goldThresholdEnabled">
                                <input type="hidden" name="gold_pricing_threshold_basis" :value="goldThresholdBasis">
                            </template>
                            <x-input-error :messages="$errors->get('gold_pricing_threshold_basis')" />
                        </div>
                    </div>

                    <p class="text-xs text-slate-500" x-show="goldThresholdEnabled && goldThresholdBasis === 'gold_candidate'" x-cloak>
                        El cliente recibe precios Oro solamente cuando el pedido calculado con precios Oro alcanza el monto requerido.
                    </p>
                    <p class="text-xs text-slate-500" x-show="goldThresholdEnabled && goldThresholdBasis === 'silver_candidate'" x-cloak>
                        El beneficio se activa cuando el pedido calculado con precios Plata alcanza el monto requerido.
                    </p>
                    <p class="text-xs text-slate-500" x-show="!goldThresholdEnabled" x-cloak>
                        Los Clientes Oro reciben precios Oro sin requisito adicional.
                    </p>
                </div>

                <div class="mt-5 flex justify-end">
                    <x-ui.button type="submit" variant="primary" class="btn-commerce-save" data-loading-label="Guardando...">
                        Guardar reglas comerciales
                    </x-ui.button>
                </div>
            </x-ui.card>
            </form>

            <form
                action="{{ route('admin.settings.commerce.advisors.update') }}"
                method="POST"
                data-loading-form
                class="space-y-4"
            >
                @csrf
                @method('PATCH')

                <x-ui.card class="p-5">
                    <h2 class="card-title">Asesores comerciales</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Define el contacto comercial que corresponde a cada nivel. Estos cambios no crean una nueva versión de pricing.
                    </p>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        @foreach(\App\Modules\Shared\Enums\DistributorTier::cases() as $tier)
                            @php
                                $advisor = $advisorByTier[$tier->value] ?? null;
                                $name = old('advisors.'.$tier->value.'.advisor_name', $advisor?->name ?? '');
                                $email = old('advisors.'.$tier->value.'.advisor_email', $advisor?->email ?? '');
                                $whatsapp = old('advisors.'.$tier->value.'.advisor_whatsapp', $advisor?->whatsapp ?? '');
                            @endphp

                            <div class="space-y-3 rounded-xl border border-slate-200 p-4">
                                <h3 class="text-sm font-semibold text-slate-900">{{ $tier->label() }}</h3>

                                <div>
                                    <label class="form-label" for="advisor-{{ $tier->value }}-name">Nombre</label>
                                    <x-ui.input
                                        id="advisor-{{ $tier->value }}-name"
                                        name="advisors[{{ $tier->value }}][advisor_name]"
                                        value="{{ $name }}"
                                        maxlength="120"
                                        required
                                    />
                                    <x-input-error :messages="$errors->get('advisors.'.$tier->value.'.advisor_name')" />
                                </div>

                                <div>
                                    <label class="form-label" for="advisor-{{ $tier->value }}-email">Correo para recibir pedidos</label>
                                    <x-ui.input
                                        id="advisor-{{ $tier->value }}-email"
                                        name="advisors[{{ $tier->value }}][advisor_email]"
                                        type="email"
                                        value="{{ $email }}"
                                        maxlength="120"
                                        required
                                    />
                                    <x-input-error :messages="$errors->get('advisors.'.$tier->value.'.advisor_email')" />
                                </div>

                                <div>
                                    <label class="form-label" for="advisor-{{ $tier->value }}-whatsapp">WhatsApp</label>
                                    <x-ui.input
                                        id="advisor-{{ $tier->value }}-whatsapp"
                                        name="advisors[{{ $tier->value }}][advisor_whatsapp]"
                                        type="tel"
                                        inputmode="tel"
                                        value="{{ $whatsapp }}"
                                        maxlength="30"
                                        placeholder="+57 300 123 4567"
                                        required
                                    />
                                    <p class="form-help">Se guarda normalizado para enlaces de WhatsApp.</p>
                                    <x-input-error :messages="$errors->get('advisors.'.$tier->value.'.advisor_whatsapp')" />
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-5 flex justify-end">
                        <x-ui.button type="submit" variant="primary" data-loading-label="Guardando...">
                            Guardar asesores
                        </x-ui.button>
                    </div>
                </x-ui.card>
            </form>
        </div>

        <aside class="space-y-4">
            <x-ui.card class="p-5">
                <h2 class="card-title">Precios sincronizados</h2>
                <p class="mt-1 text-sm text-slate-600">El precio Silver mostrado en catálogo y checkout es el último valor válido recibido de ContaPyme. Ejecuta <code>contapyme:sync-prices</code> para actualizarlo.</p>
            </x-ui.card>

            <x-ui.card class="p-5">
                <h2 class="card-title">Configuración vigente</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Incremento histórico</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $current->silverMarkupPercentageDisplay() }}%</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Redondeo histórico</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $money($current->silverRoundingMultiple) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Pedido mínimo Plata</dt>
                        <dd class="mt-1 font-semibold text-slate-900">
                            {{ $current->silverMinOrderEnabled ? 'Activo — '.$money($current->silverMinOrderAmount) : 'Inactivo' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Pedido mínimo Oro</dt>
                        <dd class="mt-1 font-semibold text-slate-900">
                            {{ $current->goldMinOrderEnabled ? 'Activo — '.$money($current->goldMinOrderAmount) : 'Inactivo' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Umbral precios Oro</dt>
                        <dd class="mt-1 font-semibold text-slate-900">
                            @if($current->goldPricingThresholdEnabled)
                                Activo — {{ $money($current->goldPricingThresholdAmount) }} — {{ $current->goldPricingThresholdBasis->historyLabel() }}
                            @else
                                Inactivo
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Última modificación</dt>
                        <dd class="mt-1 font-semibold text-slate-900">
                            @if($latestRule?->created_at)
                                {{ $latestRule->created_at->format('d/m/Y H:i') }}
                            @else
                                Sin registros
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Modificada por</dt>
                        <dd class="mt-1 font-semibold text-slate-900">
                            @if($latestRule?->createdBy)
                                {{ $latestRule->createdBy->name }}
                                <span class="block text-xs font-normal text-slate-500">{{ $latestRule->createdBy->email }}</span>
                            @elseif($latestRule)
                                Sistema (regla inicial)
                            @else
                                Aún no aplica
                            @endif
                        </dd>
                    </div>
                </dl>
            </x-ui.card>
        </aside>
    </div>

    <x-ui.card class="mt-4 p-5">
        <h2 class="card-title">Historial reciente</h2>
        <p class="mt-1 text-sm text-slate-600">Últimas 10 versiones publicadas. Las configuraciones anteriores pueden republicarse.</p>

        <div class="mt-4 hidden overflow-x-auto md:block">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-3 py-2">Fecha</th>
                        <th class="px-3 py-2">Usuario</th>
                        <th class="px-3 py-2">Pedido mínimo Plata</th>
                        <th class="px-3 py-2">Pedido mínimo Oro</th>
                        <th class="px-3 py-2">Umbral de precio Oro</th>
                        <th class="px-3 py-2">Base de evaluación</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($history as $rule)
                        @php
                            $basis = \App\Modules\Shared\Enums\GoldThresholdBasis::tryFrom((string) $rule->gold_pricing_threshold_basis);
                        @endphp
                        <tr>
                            <td class="px-3 py-2 text-slate-700">{{ $rule->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-3 py-2 text-slate-700">
                                @if($rule->createdBy)
                                    {{ $rule->createdBy->name }}
                                @else
                                    Sistema
                                @endif
                            </td>
                            <td class="px-3 py-2 text-slate-700">
                                {{ $rule->silver_min_order_enabled ? 'Activo — '.$money($rule->silver_min_order_amount) : 'Inactivo' }}
                            </td>
                            <td class="px-3 py-2 text-slate-700">
                                {{ $rule->gold_min_order_enabled ? 'Activo — '.$money($rule->gold_min_order_amount) : 'Inactivo' }}
                            </td>
                            <td class="px-3 py-2 text-slate-700">
                                @if($rule->gold_pricing_threshold_enabled)
                                    Activo — {{ $money($rule->gold_pricing_threshold_amount) }}
                                @else
                                    Inactivo
                                @endif
                            </td>
                            <td class="px-3 py-2 text-slate-700">
                                @if($rule->gold_pricing_threshold_enabled)
                                    {{ $basis?->historyLabel() ?? $rule->gold_pricing_threshold_basis }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-4 text-slate-500">Aún no hay versiones registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 space-y-3 md:hidden">
            @forelse($history as $rule)
                @php
                    $basis = \App\Modules\Shared\Enums\GoldThresholdBasis::tryFrom((string) $rule->gold_pricing_threshold_basis);
                @endphp
                <article class="commerce-history-card">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $rule->created_at?->format('d/m/Y H:i') }}</p>
                            <p class="text-xs text-slate-500">
                                @if($rule->createdBy)
                                    {{ $rule->createdBy->name }}
                                @else
                                    Sistema
                                @endif
                            </p>
                        </div>
                    </div>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div>
                            <dt class="text-xs text-slate-500">Pedido mínimo Plata</dt>
                            <dd class="font-medium text-slate-800">
                                {{ $rule->silver_min_order_enabled ? 'Activo — '.$money($rule->silver_min_order_amount) : 'Inactivo' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Pedido mínimo Oro</dt>
                            <dd class="font-medium text-slate-800">
                                {{ $rule->gold_min_order_enabled ? 'Activo — '.$money($rule->gold_min_order_amount) : 'Inactivo' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-slate-500">Umbral de precio Oro</dt>
                            <dd class="font-medium text-slate-800">
                                @if($rule->gold_pricing_threshold_enabled)
                                    Activo — {{ $money($rule->gold_pricing_threshold_amount) }} — {{ $basis?->historyLabel() ?? $rule->gold_pricing_threshold_basis }}
                                @else
                                    Inactivo
                                @endif
                            </dd>
                        </div>
                    </dl>
                </article>
            @empty
                <p class="text-sm text-slate-500">Aún no hay versiones registradas.</p>
            @endforelse
        </div>
    </x-ui.card>

</x-app-layout>
