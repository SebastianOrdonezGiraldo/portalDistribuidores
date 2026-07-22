@props([
    'tier',
])

{{--
Component contract:
- Props: DistributorTier $tier (only renders when upgrade CTA is enabled for that tier).
- Slots: none.
- Use for: upgrade pitch + WhatsApp support CTA (reuses commerce.support.whatsapp_number).
--}}
@php
    /** @var \App\Modules\Shared\Enums\DistributorTier $tier */
    $upgrade = $tier->upgrade();
    $title = (string) ($upgrade['modal_title'] ?? '');
    $body = (string) ($upgrade['modal_body'] ?? '');
    $message = (string) ($upgrade['whatsapp_message'] ?? 'Hola, quiero información sobre el Nivel Oro.');
    $number = (string) config('commerce.support.whatsapp_number', '573117479607');
    $whatsappUrl = 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    $benefits = $tier->benefits();
@endphp

@if($tier->showUpgradeCta())
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
                <button type="button" class="btn btn-secondary justify-center" @click="$dispatch('close-modal', 'tier-upgrade')">
                    Ahora no
                </button>
                <a
                    href="{{ $whatsappUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="btn btn-primary justify-center"
                >
                    Contactar por WhatsApp
                </a>
            </div>
        </div>
    </x-modal>
@endif
