@php
    $whatsappUrl = 'https://wa.me/573117479607?text=Hola%20vengo%20desde%20la%20plataforma';
    $isProductDetail = request()->routeIs('products.show');
    $bottomPositionClasses = $isProductDetail ? 'bottom-24 sm:bottom-6' : 'bottom-5 sm:bottom-6';
@endphp

<a
    href="{{ $whatsappUrl }}"
    target="_blank"
    rel="noopener noreferrer"
    title="Hablar por WhatsApp"
    class="group fixed right-4 {{ $bottomPositionClasses }} z-[110] inline-flex h-14 w-14 items-center justify-center rounded-full bg-emerald-500 text-white shadow-lg transition hover:-translate-y-0.5 hover:bg-emerald-600 focus-ring sm:h-auto sm:w-auto sm:gap-2 sm:px-4 sm:py-3"
>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.34 1.78.65 2.63a2 2 0 0 1-.45 2.11L8.03 9.74a16 16 0 0 0 6.23 6.23l1.28-1.28a2 2 0 0 1 2.11-.45c.85.31 1.73.53 2.63.65A2 2 0 0 1 22 16.92z" />
    </svg>
    <span class="hidden sm:inline text-sm font-semibold">WhatsApp</span>
    <span class="sr-only">Hablar por WhatsApp</span>
</a>
