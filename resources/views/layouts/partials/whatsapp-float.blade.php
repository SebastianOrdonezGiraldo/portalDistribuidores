@php
    $whatsappUrl = 'https://wa.me/573117479607?text=Hola%20vengo%20desde%20la%20plataforma';
    $isProductDetail = request()->routeIs('products.show');
    $bottomPositionClasses = $isProductDetail ? 'bottom-[42dvh] sm:bottom-6' : 'bottom-5 sm:bottom-6';
@endphp

<a
    href="{{ $whatsappUrl }}"
    target="_blank"
    rel="noopener noreferrer"
    class="catalog-support-bubble catalog-support-bubble--whatsapp fixed right-4 {{ $bottomPositionClasses }} z-[110]"
    aria-label="Hablar por WhatsApp"
    title="Hablar por WhatsApp"
>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        <path d="M20.5 11.7a8.5 8.5 0 0 1-12.56 7.5L3.5 20.5l1.3-4.2A8.5 8.5 0 1 1 20.5 11.7Z"/>
        <path d="M8.1 7.8c.2-.5.5-.5.8-.5h.5c.2 0 .4.1.5.4l.7 1.7c.1.2.1.4 0 .6l-.5.7c-.1.2-.1.3 0 .5.6 1.1 1.5 2 2.6 2.6.2.1.3.1.5 0l.7-.5c.2-.1.4-.1.6 0l1.7.7c.3.1.4.3.4.5v.5c0 .3 0 .6-.5.8-.5.2-1.6.5-3-.1-1-.4-2.2-1.1-3.4-2.3-1-1-1.8-2.1-2.2-3.1-.6-1.4-.3-2.5-.1-3Z"/>
    </svg>
    <span class="sr-only">Hablar por WhatsApp</span>
</a>
