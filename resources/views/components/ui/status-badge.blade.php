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

    $meta = $map[$value] ?? ['class' => 'border-slate-300 bg-slate-50 text-slate-700', 'label' => ucfirst($value ?: 'Sin estado')];
@endphp

<span {{ $attributes->merge(['class' => 'badge '.$meta['class']]) }}>{{ $label ?? $meta['label'] }}</span>
