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
    <div class="pointer-events-none fixed inset-x-3 bottom-3 z-[80] w-auto space-y-2 sm:inset-x-auto sm:right-4 sm:bottom-4 sm:w-[min(92vw,28rem)]">
        @foreach($messages as $message)
            <x-ui.alert
                :variant="$message['variant']"
                data-toast
                data-toast-timeout="5000"
                data-cart-success="{{ !empty($message['cart_success']) ? 'true' : 'false' }}"
                class="pointer-events-auto animate-toast-in"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-2.5">
                        @if($message['variant'] === 'success')
                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 flex-shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        @elseif($message['variant'] === 'danger')
                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 flex-shrink-0 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        @elseif($message['variant'] === 'warning')
                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 flex-shrink-0 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        @endif
                        <div>
                            <p class="font-semibold">{{ $message['title'] }}</p>
                            <p class="mt-0.5 text-sm opacity-90">{{ $message['text'] }}</p>
                        </div>
                    </div>
                    <button type="button" data-toast-dismiss class="inline-flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg opacity-60 transition hover:opacity-100" aria-label="Cerrar">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
            </x-ui.alert>
        @endforeach
    </div>
@endif
