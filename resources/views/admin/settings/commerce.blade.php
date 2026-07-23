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
            subtitle="Configura cómo se calcula el precio ICM Plata a partir del precio base ICM Oro."
        />
    </x-slot>

    @php
        $money = fn ($value) => '$'.number_format((float) $value, 0, ',', '.');
        $displayPercent = old('silver_markup_percent', $current->silverMarkupPercentageDisplay());
        $displayRounding = (int) old('silver_rounding_multiple', $current->silverRoundingMultiple);
    @endphp

    <div
        class="grid gap-4 lg:grid-cols-[1.6fr_1fr]"
        x-data="commercePricingPreview({
            percent: '{{ $displayPercent }}',
            rounding: {{ $displayRounding }},
            goldPesos: 100000
        })"
    >
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
                    Fórmula vigente: Precio Plata = techo(Precio Oro × (1 + incremento)) y luego redondeo al múltiplo seleccionado.
                </p>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="silver_markup_percent">Incremento del precio Plata sobre el precio Oro</label>
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
                        <p class="form-help">Este porcentaje se suma al precio base Oro antes de aplicar el redondeo.</p>
                        <x-input-error :messages="$errors->get('silver_markup_percent')" />
                    </div>

                    <div>
                        <label class="form-label" for="silver_rounding_multiple">Redondear el precio Plata al siguiente múltiplo de</label>
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
                        <li>Esta regla recalcula únicamente el precio Plata derivado a partir de ese precio Oro.</li>
                        <li>Los clientes Plata verán el nuevo precio en catálogo, ficha de producto, carrito y checkout.</li>
                        <li>Los clientes Oro siguen comprando al precio base; solo cambia el precio Plata de referencia que ven tachado.</li>
                        <li>Si inicias sesión como administrador o invitado en el catálogo, verás el precio base Oro sin el incremento Plata.</li>
                    </ul>
                </div>

                <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50/70 p-4 text-sm text-amber-950">
                    <p class="font-semibold">Al guardar, los precios visibles y los carritos abiertos se recalcularán con la nueva regla. Los pedidos confirmados conservarán sus precios históricos.</p>
                    <p class="mt-2 text-amber-900/80">Las líneas existentes de pedidos editados conservan su precio. Las líneas nuevas usarán la regla vigente.</p>
                </div>

                <div class="mt-5 flex justify-end">
                    <x-ui.button type="submit" variant="primary" data-loading-label="Guardando...">
                        Guardar reglas comerciales
                    </x-ui.button>
                </div>
            </x-ui.card>
        </form>

        <aside class="space-y-4">
            <x-ui.card class="p-5">
                <h2 class="card-title">Vista previa</h2>
                <p class="mt-1 text-xs text-slate-500">Cálculo informativo en el navegador. La lógica definitiva permanece en el servidor.</p>

                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Precio Oro</dt>
                        <dd class="font-semibold text-slate-900" x-text="formatMoney(goldPesos)"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Incremento aplicado</dt>
                        <dd class="font-semibold text-slate-900" x-text="percentLabel()"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Precio Plata antes del redondeo</dt>
                        <dd class="font-semibold text-slate-900" x-text="formatMoney(plataBeforeRounding())"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Precio Plata final</dt>
                        <dd class="font-bold text-brand-dark" x-text="formatMoney(plataFinal())"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-t border-slate-200 pt-2">
                        <dt class="text-slate-500">Diferencia entre Plata y Oro</dt>
                        <dd class="font-semibold text-amber-700" x-text="formatMoney(plataFinal() - goldPesos)"></dd>
                    </div>
                </dl>
            </x-ui.card>

            <x-ui.card class="p-5">
                <h2 class="card-title">Configuración vigente</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Incremento</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $current->silverMarkupPercentageDisplay() }}%</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-slate-500">Redondeo</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $money($current->silverRoundingMultiple) }}</dd>
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

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="px-3 py-2">Fecha</th>
                        <th class="px-3 py-2">Porcentaje</th>
                        <th class="px-3 py-2">Redondeo</th>
                        <th class="px-3 py-2">Administrador</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($history as $rule)
                        @php
                            $bp = (int) $rule->silver_markup_basis_points;
                            $pct = intdiv($bp, 100).'.'.str_pad((string) ($bp % 100), 2, '0', STR_PAD_LEFT);
                        @endphp
                        <tr>
                            <td class="px-3 py-2 text-slate-700">{{ $rule->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-3 py-2 font-semibold text-slate-900">{{ $pct }}%</td>
                            <td class="px-3 py-2 text-slate-700">{{ $money($rule->silver_rounding_multiple) }}</td>
                            <td class="px-3 py-2 text-slate-700">
                                @if($rule->createdBy)
                                    {{ $rule->createdBy->name }}
                                @else
                                    Sistema
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-4 text-slate-500">Aún no hay versiones registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <script>
        function commercePricingPreview({ percent, rounding, goldPesos }) {
            const toBasisPoints = (value) => {
                const raw = String(value ?? '').trim().replace(',', '.');
                if (!/^\d+(\.\d{0,2})?$/.test(raw) || raw === '') {
                    return 0;
                }
                const [whole, fraction = ''] = raw.split('.');
                return (Number(whole || '0') * 100) + Number((fraction + '00').slice(0, 2));
            };

            const ceilDiv = (numerator, denominator) => Math.floor((numerator + denominator - 1) / denominator);

            return {
                percent,
                rounding,
                goldPesos,
                formatMoney(pesos) {
                    const safe = Math.max(0, Math.round(Number(pesos) || 0));
                    return `$${safe.toLocaleString('es-CO')}`;
                },
                percentLabel() {
                    const bp = toBasisPoints(this.percent);
                    const whole = Math.floor(bp / 100);
                    const fraction = String(bp % 100).padStart(2, '0');
                    return `${whole}.${fraction}%`;
                },
                plataBeforeRounding() {
                    const goldCents = this.goldPesos * 100;
                    const factor = 10000 + toBasisPoints(this.percent);
                    const unroundedCents = ceilDiv(goldCents * factor, 10000);
                    return Math.floor(unroundedCents / 100);
                },
                plataFinal() {
                    const goldCents = this.goldPesos * 100;
                    const factor = 10000 + toBasisPoints(this.percent);
                    const unroundedCents = ceilDiv(goldCents * factor, 10000);
                    const multipleCents = Math.max(1, Number(this.rounding) || 1000) * 100;
                    const roundedCents = ceilDiv(unroundedCents, multipleCents) * multipleCents;
                    return Math.floor(roundedCents / 100);
                },
            };
        }
    </script>
</x-app-layout>
