@props(['status', 'label' => null])

@php
    $value = $status instanceof \BackedEnum ? $status->value : strtolower((string) $status);

    $map = [
        'draft'            => ['class' => 'status-pending',    'label' => 'En revisión'],
        'pending_approval' => ['class' => 'badge-violet',      'label' => 'En revisión'],
        'pending'          => ['class' => 'status-pending',    'label' => 'Pendiente'],
        'submitted'        => ['class' => 'status-approved',   'label' => 'Aprobado'],
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
        'info'             => ['class' => 'status-processing', 'label' => 'Info'],
    ];

    $meta = $map[$value] ?? ['class' => 'bg-slate-100 text-slate-800', 'label' => ucfirst($value ?: 'Sin estado')];
    $displayLabel = $label ?? $meta['label'];
@endphp

<span {{ $attributes->merge(['class' => 'badge '.$meta['class'], 'aria-label' => $displayLabel]) }}>{{ $displayLabel }}</span>
