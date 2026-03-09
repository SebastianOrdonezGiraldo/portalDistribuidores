<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotización {{ $order->oc_number }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <h2 style="margin: 0 0 12px 0;">Recibimos tu solicitud de cotización {{ $order->oc_number }}</h2>

    <p style="margin: 0 0 10px 0;">
        Hola {{ $order->contact_name }},
    </p>

    <p style="margin: 0 0 10px 0;">
        Gracias por contactarnos. Tu <strong>cotización</strong> fue registrada correctamente y está en proceso de revisión comercial.
    </p>

    <p style="margin: 0 0 10px 0;">
        <strong>Número de cotización:</strong> {{ $order->oc_number }}<br>
        <strong>Empresa:</strong> {{ $order->company_name }}<br>
        <strong>Correo de contacto:</strong> {{ $order->contact_email ?? '-' }}
    </p>

    <p style="margin: 0 0 10px 0;">
        Adjuntamos el PDF con el detalle de la cotización para tu referencia.
    </p>

    <p style="margin: 0 0 10px 0;">
        Este correo <strong>no confirma una compra</strong>; confirma la recepción de tu solicitud de cotización.
    </p>

    <p style="margin: 0;">
        Equipo comercial<br>
        Import Corporal Medical SAS
    </p>
</body>
</html>
