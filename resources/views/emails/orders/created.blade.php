<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva CTC {{ $order->oc_number }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <h2 style="margin: 0 0 12px 0;">Nueva CTC {{ $order->oc_number }}</h2>
    <p style="margin: 0 0 8px 0;"><strong>Distribuidor:</strong> {{ $order->distributor?->name }}</p>
    <p style="margin: 0 0 8px 0;"><strong>Razón Social:</strong> {{ $order->company_name }}</p>
    <p style="margin: 0 0 8px 0;"><strong>NIT/Cédula:</strong> {{ $order->company_nit ?? '-' }}</p>
    <p style="margin: 0 0 8px 0;"><strong>Correo Electrónico:</strong> {{ $order->contact_email ?? '-' }}</p>
    <p style="margin: 0 0 8px 0;"><strong>Contacto:</strong> {{ $order->contact_name }}</p>
    <p style="margin: 0 0 8px 0;"><strong>Dirección:</strong> {{ $order->company_address ?? '-' }}</p>
    <p style="margin: 0 0 8px 0;"><strong>Ciudad:</strong> {{ $order->city ?? '-' }}</p>
    <p style="margin: 0 0 8px 0;"><strong>Total:</strong> ${{ number_format((float) $order->total_amount, 2, ',', '.') }}</p>
    <p style="margin: 0 0 16px 0;"><strong>Notas:</strong> {{ $order->notes ?: 'Sin notas' }}</p>
    <p style="margin: 0;">Se adjunta el PDF de la CTC.</p>
</body>
</html>
