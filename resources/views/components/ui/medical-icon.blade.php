@props(['name' => 'catalog'])

<span {{ $attributes->merge(['class' => 'medical-icon']) }} aria-hidden="true">
    @switch($name)
        @case('catalog')
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M4 5.5h16" />
                <path d="M4 12h16" />
                <path d="M4 18.5h16" />
                <path d="M8 3v18" />
                <path d="M16 3v18" />
            </svg>
            @break

        @case('cart')
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M4 4h2l2.2 10.2a2 2 0 0 0 2 1.6h6.9a2 2 0 0 0 1.9-1.3L21 8H7.1" />
                <path d="M10 20h.01" />
                <path d="M17 20h.01" />
            </svg>
            @break

        @case('order')
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M7 3h10a2 2 0 0 1 2 2v16l-3-1.5-3 1.5-3-1.5L7 21V5a2 2 0 0 1 2-2Z" />
                <path d="M9 8h6" />
                <path d="M9 12h6" />
                <path d="M9 16h4" />
            </svg>
            @break

        @case('document')
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z" />
                <path d="M14 3v5h5" />
                <path d="M8.5 13h7" />
                <path d="M8.5 16h5" />
            </svg>
            @break

        @case('company')
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M3 21h18" />
                <path d="M5 21V7l7-4 7 4v14" />
                <path d="M9 21v-6h6v6" />
                <path d="M9 9h.01M12 9h.01M15 9h.01" />
            </svg>
            @break

        @case('alert')
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M12 3 2.8 19a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3Z" />
                <path d="M12 9v4" />
                <path d="M12 17h.01" />
            </svg>
            @break

        @default
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M4 4h16v16H4z" />
                <path d="M8 8h8v8H8z" />
            </svg>
    @endswitch
</span>
