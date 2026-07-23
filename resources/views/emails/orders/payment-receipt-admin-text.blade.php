Comprobante de pago — Cliente Oro

Un cliente ICM Oro subió el comprobante de pago. El archivo va adjunto a este correo.

Pedido: {{ $order->oc_number }}
Empresa: {{ $order->company_name }}
NIT: {{ $order->company_nit }}
Contacto: {{ $order->contact_name }}
Correo: {{ $order->contact_email }}
Teléfono: {{ $order->phone }}
Método de pago: {{ $paymentMethodLabel }}
Total: ${{ number_format((float) $order->total_amount, 0, ',', '.') }}

Revisar en el panel:
{{ $adminOrderUrl }}

Portal de Distribuidores (notificación automática)
