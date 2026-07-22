@props(['tier' => null, 'size' => 'md'])

{{--
Component contract:
- Props: tier (DistributorTier|string|null), size sm|md|lg.
- Slots: none.
- Use for: ICM Plata / ICM Oro badges in tables, cards and forms.
--}}
@php
    use App\Modules\Shared\Enums\DistributorTier;

    $resolved = match (true) {
        $tier instanceof DistributorTier => $tier,
        is_string($tier) && filled($tier) && DistributorTier::tryFrom($tier) !== null => DistributorTier::from($tier),
        default => DistributorTier::Silver,
    };

    $styles = match ($resolved) {
        DistributorTier::Gold => 'border border-amber-200 bg-amber-50 text-amber-800',
        DistributorTier::Silver => 'border border-slate-200 bg-slate-100 text-slate-700',
    };

    $sizeClasses = match ($size) {
        'sm' => 'px-2 py-0.5 text-[11px]',
        'lg' => 'px-3 py-1 text-sm',
        default => 'px-2.5 py-0.5 text-xs',
    };

    $label = $resolved->label();
@endphp

<span {{ $attributes->merge([
    'class' => 'badge inline-flex items-center font-semibold '.$styles.' '.$sizeClasses,
    'aria-label' => 'Nivel comercial: '.$label,
]) }}>
    {{ $label }}
</span>
