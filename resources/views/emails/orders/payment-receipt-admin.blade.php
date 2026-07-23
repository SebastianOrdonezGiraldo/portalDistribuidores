<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de pago Cliente Oro</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6;">
    <h2 style="margin: 0 0 12px 0;">Comprobante de pago — Cliente Oro</h2>

    <p style="margin: 0 0 10px 0;">
        Un cliente <strong>ICM Oro</strong> subió el comprobante de pago. El archivo va adjunto a este correo.
    </p>

    <p style="margin: 0 0 10px 0;">
        <strong>Pedido:</strong> {{ $order->oc_number }}<br>
        <strong>Empresa:</strong> {{ $order->company_name }}<br>
        <strong>NIT:</strong> {{ $order->company_nit }}<br>
        <strong>Contacto:</strong> {{ $order->contact_name }}<br>
        <strong>Correo:</strong> {{ $order->contact_email }}<br>
        <strong>Teléfono:</strong> {{ $order->phone }}<br>
        <strong>Método de pago:</strong> {{ $paymentMethodLabel }}<br>
        <strong>Total:</strong> ${{ number_format((float) $order->total_amount, 0, ',', '.') }}
    </p>

    <p style="margin: 0 0 10px 0;">
        Revisar en el panel de administración:<br>
        <a href="{{ $adminOrderUrl }}" target="_blank" rel="noopener noreferrer">{{ $adminOrderUrl }}</a>
    </p>

    <p style="margin: 0;">
        Cordialmente,<br>
        Portal de Distribuidores (notificación automática)
    </p>
</body>
</html>
