@props(['current' => 'cart'])

@php
    $steps = [
        ['key' => 'cart', 'label' => 'Carrito', 'hint' => 'Productos y cantidades'],
        ['key' => 'checkout', 'label' => 'Datos comerciales', 'hint' => 'Contacto y entrega'],
        ['key' => 'submitted', 'label' => 'Solicitud enviada', 'hint' => 'CTC en gestión'],
    ];

    $currentIndex = collect($steps)->search(fn ($step) => $step['key'] === $current);
    $currentIndex = $currentIndex === false ? 0 : $currentIndex;
@endphp

<nav {{ $attributes->merge(['class' => 'flow-steps']) }} aria-label="Progreso del pedido" data-flow-steps data-current-step="{{ $current }}">
    @foreach($steps as $index => $step)
        @php
            $state = $index < $currentIndex ? 'complete' : ($index === $currentIndex ? 'current' : 'pending');
        @endphp

        <div class="flow-step is-{{ $state }}" data-flow-step="{{ $step['key'] }}">
            <span class="flow-step-mark" aria-hidden="true">
                @if($state === 'complete')
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M20 6 9 17l-5-5" />
                    </svg>
                @else
                    {{ $index + 1 }}
                @endif
            </span>
            <span class="min-w-0">
                <span class="block font-semibold">{{ $step['label'] }}</span>
                <span class="block truncate text-xs opacity-75">{{ $step['hint'] }}</span>
            </span>
        </div>
    @endforeach
</nav>
