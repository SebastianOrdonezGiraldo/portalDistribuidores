@php
    $messages = [];
    $statusText = (string) session('status', '');
    $isCartSuccess = str_contains(strtolower($statusText), 'agregado al carrito');

    if (session('status')) {
        $messages[] = [
            'variant' => 'success',
            'title' => 'Operacion completada',
            'text' => session('status'),
            'cart_success' => $isCartSuccess,
        ];
    }

    if (session('warning')) {
        $messages[] = ['variant' => 'warning', 'title' => 'Atencion', 'text' => session('warning')];
    }

    if (session('error')) {
        $messages[] = ['variant' => 'danger', 'title' => 'Error', 'text' => session('error')];
    }

    if ($errors->any()) {
        foreach ($errors->all() as $error) {
            $messages[] = ['variant' => 'danger', 'title' => 'Validacion', 'text' => $error];
        }
    }
@endphp

@if(count($messages) > 0)
    <div class="pointer-events-none fixed right-4 top-4 z-[80] w-[min(92vw,28rem)] space-y-2">
        @foreach($messages as $message)
            <x-ui.alert
                :variant="$message['variant']"
                data-toast
                data-toast-timeout="5000"
                data-cart-success="{{ !empty($message['cart_success']) ? 'true' : 'false' }}"
                class="pointer-events-auto"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold">{{ $message['title'] }}</p>
                        <p class="mt-0.5 text-sm">{{ $message['text'] }}</p>
                    </div>
                    <button type="button" data-toast-dismiss class="btn btn-ghost !h-7 !px-2 !py-0 text-xs">Cerrar</button>
                </div>
            </x-ui.alert>
        @endforeach
    </div>
@endif
