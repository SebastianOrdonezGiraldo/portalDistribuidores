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
        'draft'            => ['class' => 'status-pending',    'label' => 'En revisión', 'tone' => 'warning'],
        'pending_approval' => ['class' => 'status-pending',    'label' => 'En revisión', 'tone' => 'warning'],
        'pending_review'   => ['class' => 'status-pending',    'label' => 'Pendiente de revisión', 'tone' => 'warning'],
        'pending'          => ['class' => 'status-pending',    'label' => 'Pendiente', 'tone' => 'warning'],
        'submitted'        => ['class' => 'status-processing', 'label' => 'Registrado', 'tone' => 'info'],
        'sold'             => ['class' => 'status-processing', 'label' => 'Vendido', 'tone' => 'info'],
        'dispatched'       => ['class' => 'status-processing', 'label' => 'Despachado', 'tone' => 'info'],
        'approved'         => ['class' => 'status-delivered',  'label' => 'Aprobado', 'tone' => 'success'],
        'sending'          => ['class' => 'status-processing', 'label' => 'Procesando', 'tone' => 'info'],
        'processing'       => ['class' => 'status-processing', 'label' => 'Procesando', 'tone' => 'info'],
        'sent'             => ['class' => 'status-processing', 'label' => 'Enviado', 'tone' => 'info'],
        'delivered'        => ['class' => 'status-delivered',  'label' => 'Entregado', 'tone' => 'success'],
        'rejected'         => ['class' => 'status-error',      'label' => 'Rechazado', 'tone' => 'danger'],
        'cancelled'        => ['class' => 'status-error',      'label' => 'Cancelado', 'tone' => 'danger'],
        'canceled'         => ['class' => 'status-error',      'label' => 'Cancelado', 'tone' => 'danger'],
        'cancelado'        => ['class' => 'status-error',      'label' => 'Cancelado', 'tone' => 'danger'],
        'failed'           => ['class' => 'status-error',      'label' => 'Error', 'tone' => 'danger'],
        'error'            => ['class' => 'status-error',      'label' => 'Error', 'tone' => 'danger'],
        'active'           => ['class' => 'status-delivered',  'label' => 'Activo', 'tone' => 'success'],
        'inactive'         => ['class' => 'bg-slate-100 text-slate-700', 'label' => 'Inactivo', 'tone' => 'neutral'],
        'suspended'        => ['class' => 'status-error',      'label' => 'Suspendido', 'tone' => 'danger'],
        'info'             => ['class' => 'status-processing', 'label' => 'Info', 'tone' => 'info'],
        // PaymentStatus (manual payment)
        'not_applicable'   => ['class' => 'payment-status-na', 'label' => 'Sin pago', 'tone' => 'neutral'],
        'pending_upload'   => ['class' => 'payment-status-pending', 'label' => 'Pendiente de comprobante', 'tone' => 'warning'],
        'confirming'       => ['class' => 'payment-status-confirming', 'label' => 'Confirmando pago', 'tone' => 'info'],
        'validated'        => ['class' => 'payment-status-validated', 'label' => 'Pago validado', 'tone' => 'success'],
        'rejected'         => ['class' => 'payment-status-rejected', 'label' => 'Comprobante rechazado', 'tone' => 'danger'],
        'expired'          => ['class' => 'payment-status-expired', 'label' => 'Pago expirado', 'tone' => 'danger'],
    ];

    $meta = $map[$value] ?? ['class' => 'bg-slate-100 text-slate-700', 'label' => ucfirst($value ?: 'Sin estado'), 'tone' => 'neutral'];
    $displayLabel = $label ?? $meta['label'];
@endphp

<span {{ $attributes->merge(['class' => 'badge gap-1.5 '.$meta['class'], 'aria-label' => 'Estado: '.$displayLabel]) }}>
    @switch($meta['tone'])
        @case('warning')
            <svg aria-hidden="true" class="h-3 w-3" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="6" stroke="currentColor" stroke-width="1.5"/><path d="M8 4.5v3.75l2.25 1.25" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            @break
        @case('info')
            <svg aria-hidden="true" class="h-3 w-3" viewBox="0 0 16 16" fill="none"><path d="M3 8h9M9 5l3 3-3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            @break
        @case('success')
            <svg aria-hidden="true" class="h-3 w-3" viewBox="0 0 16 16" fill="none"><path d="m3.5 8.25 2.75 2.75 6.25-6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            @break
        @case('danger')
            <svg aria-hidden="true" class="h-3 w-3" viewBox="0 0 16 16" fill="none"><path d="m5 5 6 6m0-6-6 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
            @break
        @default
            <span aria-hidden="true" class="h-1.5 w-1.5 rounded-full bg-current"></span>
    @endswitch
    {{ $displayLabel }}
</span>
