@props([
    'tier',
])

{{--
Component contract:
- Props: DistributorTier $tier (renders when hasBenefitsModal()).
- Slots: none.
- Use for: upgrade pitch (Plata) or benefits summary (Oro); WhatsApp only when message is set.
--}}
@php
    /** @var \App\Modules\Shared\Enums\DistributorTier $tier */
    $upgrade = $tier->upgrade();
    $isUpgradePitch = $tier->showUpgradeCta();
    $title = (string) ($upgrade['modal_title'] ?? ($isUpgradePitch ? 'Sube de nivel' : 'Beneficios de tu nivel'));
    $body = (string) ($upgrade['modal_body'] ?? '');
    $message = filled($upgrade['whatsapp_message'] ?? null)
        ? (string) $upgrade['whatsapp_message']
        : null;
    $number = (string) config('commerce.support.whatsapp_number', '573117479607');
    $whatsappUrl = $message
        ? 'https://wa.me/'.$number.'?text='.rawurlencode($message)
        : null;
    $benefits = $tier->benefits();
    $dismissLabel = $isUpgradePitch ? 'Ahora no' : 'Entendido';
    $whatsappLabel = $isUpgradePitch ? 'Contactar por WhatsApp' : 'Hablar con soporte';
@endphp

@if($tier->hasBenefitsModal())
    <x-modal name="tier-upgrade" maxWidth="lg">
        <div class="p-6">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <x-tier.badge :tier="$tier" />
                    <h3 class="mt-3 text-lg font-semibold text-slate-900">{{ $title }}</h3>
                </div>
                <button type="button" class="btn btn-ghost !min-h-9 !px-2" @click="$dispatch('close-modal', 'tier-upgrade')" aria-label="Cerrar">
                    ×
                </button>
            </div>

            @if(filled($body))
                <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $body }}</p>
            @endif

            @if($benefits !== [])
                <ul class="mt-4 space-y-2">
                    @foreach($benefits as $benefit)
                        <li class="flex items-start gap-2 text-sm text-slate-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0 text-brand-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                            <span>{{ $benefit }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:justify-end">
                <button type="button" class="btn {{ $whatsappUrl ? 'btn-secondary' : 'btn-primary' }} justify-center" @click="$dispatch('close-modal', 'tier-upgrade')">
                    {{ $dismissLabel }}
                </button>
                @if($whatsappUrl)
                    <a
                        href="{{ $whatsappUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="btn btn-primary justify-center"
                    >
                        {{ $whatsappLabel }}
                    </a>
                @endif
            </div>
        </div>
    </x-modal>
@endif
