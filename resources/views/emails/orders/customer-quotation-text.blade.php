Recibimos tu solicitud de cotizacion {{ $order->oc_number }}.

Hola {{ $order->contact_name }},

Tu solicitud fue registrada correctamente y esta en revision comercial.

Resumen:
- Numero: {{ $order->oc_number }}
- Empresa: {{ $order->company_name }}
- Correo: {{ $order->contact_email ?? '-' }}
- Total estimado: ${{ number_format((float) $order->total_amount, 2, ',', '.') }}

Adjuntamos el PDF con el detalle de la cotizacion.
Este correo confirma recepcion, no confirma compra.

Portal: {{ $portalUrl }}

Import Corporal Medical SAS
