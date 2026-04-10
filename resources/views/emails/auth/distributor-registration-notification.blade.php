<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva solicitud de registro</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6;">
    <h2 style="margin: 0 0 12px 0;">Nueva solicitud de registro de distribuidor</h2>

    <p style="margin: 0 0 10px 0;">
        Se recibió una solicitud de alta en el portal. Los datos quedaron en estado pendiente de revisión.
    </p>

    <p style="margin: 0 0 10px 0;">
        <strong>Empresa:</strong> {{ $distributor->name }}<br>
        <strong>NIT:</strong> {{ $distributor->nit }}<br>
        <strong>Ciudad:</strong> {{ $distributor->city }}<br>
        @if($distributor->address)
            <strong>Dirección:</strong> {{ $distributor->address }}<br>
        @endif
        <strong>Teléfono:</strong> {{ $distributor->phone }}<br>
        <strong>Contacto:</strong> {{ $distributor->contact_name }}<br>
        <strong>Correo de contacto:</strong> {{ $distributor->contact_email }}
    </p>

    <p style="margin: 0 0 10px 0;">
        Puedes revisar y gestionar distribuidores en el panel de administración:<br>
        <a href="{{ $adminDistributorsUrl }}" target="_blank" rel="noopener noreferrer">{{ $adminDistributorsUrl }}</a>
    </p>

    <p style="margin: 0;">
        Cordialmente,<br>
        Portal de Distribuidores (notificación automática)
    </p>
</body>
</html>
