@props(['status'])

@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $steps = [
        ['value' => 'submitted', 'label' => 'Registrado'],
        ['value' => 'sold', 'label' => 'Vendido'],
        ['value' => 'dispatched', 'label' => 'Despachado'],
        ['value' => 'sent', 'label' => 'Enviado'],
        ['value' => 'delivered', 'label' => 'Entregado'],
    ];
    $positions = ['submitted' => 0, 'sold' => 1, 'sending' => 1, 'dispatched' => 2, 'sent' => 3, 'delivered' => 4];
    $currentPosition = $positions[$value] ?? -1;
    $isNegative = in_array($value, ['cancelled', 'canceled', 'rejected', 'failed', 'error'], true);
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }} aria-label="Progreso del pedido">
    <ol class="grid grid-cols-5" role="list">
        @foreach($steps as $index => $step)
            @php
                $isComplete = ! $isNegative && $currentPosition > $index;
                $isCurrent = ! $isNegative && $currentPosition === $index;
            @endphp
            <li class="relative flex min-w-0 flex-col items-center text-center">
                @if(!$loop->first)
                    <span @class([
                        'absolute right-1/2 top-[0.45rem] h-0.5 w-full',
                        'bg-brand-primary' => $isComplete || $isCurrent,
                        'bg-slate-200' => ! ($isComplete || $isCurrent),
                    ]) aria-hidden="true"></span>
                @endif
                <span @class([
                    'relative z-10 flex h-4 w-4 items-center justify-center rounded-full border-2 bg-white',
                    'border-brand-primary text-brand-primary' => $isComplete || $isCurrent,
                    'border-slate-300 text-slate-300' => ! ($isComplete || $isCurrent),
                    'ring-4 ring-cyan-100' => $isCurrent,
                ])>
                    @if($isComplete)
                        <svg aria-hidden="true" class="h-2.5 w-2.5" viewBox="0 0 12 12" fill="none"><path d="m2.5 6 2 2 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    @endif
                </span>
                <span @class([
                    'mt-2 truncate text-[0.62rem] font-medium sm:text-[0.68rem]',
                    'text-brand-dark' => $isCurrent,
                    'text-slate-600' => $isComplete,
                    'text-slate-400' => ! ($isComplete || $isCurrent),
                ])>{{ $step['label'] }}</span>
            </li>
        @endforeach
    </ol>
</div>
