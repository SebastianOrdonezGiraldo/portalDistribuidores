@props(['status', 'label' => null])

{{--
Component contract:
- Props: status string/backed enum and optional display label override.
- Slots: none.
- Use for: normalized order/distributor/process status labels; workflow transitions stay in enums/services.
--}}
@php
    $value = $status instanceof \BackedEnum ? $status->value : strtolower((string) $status);

    $map = [
        'draft'            => ['class' => 'status-pending',    'label' => 'En revisión'],
        'pending_approval' => ['class' => 'badge-violet',      'label' => 'En revisión'],
        'pending_review'   => ['class' => 'status-pending',    'label' => 'Pendiente de revisión'],
        'pending'          => ['class' => 'status-pending',    'label' => 'Pendiente'],
        'submitted'        => ['class' => 'status-approved',   'label' => 'Registrado'],
        'sold'             => ['class' => 'status-processing', 'label' => 'Vendido'],
        'dispatched'       => ['class' => 'status-sent',       'label' => 'Despachado'],
        'delivered'        => ['class' => 'status-delivered',  'label' => 'Entregado'],
        'cancelled'        => ['class' => 'status-canceled',   'label' => 'Cancelado'],
        'approved'         => ['class' => 'status-approved',   'label' => 'Aprobado'],
        'sending'          => ['class' => 'status-processing', 'label' => 'Procesando'],
        'processing'       => ['class' => 'status-processing', 'label' => 'Procesando'],
        'sent'             => ['class' => 'status-sent',       'label' => 'Enviado'],
        'delivered'        => ['class' => 'status-delivered',  'label' => 'Entregado'],
        'rejected'         => ['class' => 'badge-danger',      'label' => 'Rechazado'],
        'cancelled'        => ['class' => 'status-canceled',   'label' => 'Cancelado'],
        'canceled'         => ['class' => 'status-canceled',   'label' => 'Cancelado'],
        'cancelado'        => ['class' => 'status-canceled',   'label' => 'Cancelado'],
        'failed'           => ['class' => 'status-error',      'label' => 'Error'],
        'error'            => ['class' => 'status-error',      'label' => 'Error'],
        'active'           => ['class' => 'status-approved',   'label' => 'Activo'],
        'inactive'         => ['class' => 'status-canceled',   'label' => 'Inactivo'],
        'suspended'        => ['class' => 'status-canceled',   'label' => 'Suspendido'],
        'info'             => ['class' => 'status-processing', 'label' => 'Info'],
    ];

    $meta = $map[$value] ?? ['class' => 'bg-slate-100 text-slate-800', 'label' => ucfirst($value ?: 'Sin estado')];
    $displayLabel = $label ?? $meta['label'];
@endphp

<span {{ $attributes->merge(['class' => 'badge '.$meta['class'], 'aria-label' => $displayLabel]) }}>{{ $displayLabel }}</span>
