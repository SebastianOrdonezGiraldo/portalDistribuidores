Nueva CTC {{ $order->oc_number }}.

Se registro una nueva solicitud de cotizacion.

Resumen:
- Distribuidor: {{ $order->distributor?->name ?? '-' }}
- Razon social: {{ $order->company_name }}
- NIT/Cedula: {{ $order->company_nit ?? '-' }}
- Contacto: {{ $order->contact_name }}
- Correo: {{ $order->contact_email ?? '-' }}
- Ciudad: {{ $order->city ?? '-' }}{{ $order->department ? ' / '.$order->department : '' }}
- Total: ${{ number_format((float) $order->total_amount, 2, ',', '.') }}
- Notas: {{ $order->notes ?: 'Sin notas' }}

Se adjunta el PDF de la CTC para gestion interna.
