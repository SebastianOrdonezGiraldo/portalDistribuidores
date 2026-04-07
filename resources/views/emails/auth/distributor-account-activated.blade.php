<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuenta activada</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6;">
    <h2 style="margin: 0 0 12px 0;">Tu cuenta ya está activa en el Portal de Distribuidores</h2>

    <p style="margin: 0 0 10px 0;">
        Hola {{ $user->name }},
    </p>

    <p style="margin: 0 0 10px 0;">
        Te informamos que tu cuenta asociada a <strong>{{ $distributor->name }}</strong> fue activada exitosamente.
    </p>

    <p style="margin: 0 0 10px 0;">
        Desde este momento puedes ingresar al portal para gestionar catálogo, pedidos y documentos.
    </p>

    <p style="margin: 0 0 10px 0;">
        <strong>Usuario de acceso:</strong> {{ $user->email }}<br>
        <strong>Ingreso al portal:</strong>
        <a href="{{ $loginUrl }}" target="_blank" rel="noopener noreferrer">{{ $loginUrl }}</a>
    </p>

    <p style="margin: 0 0 10px 0;">
        Si no recuerdas tu contraseña, puedes restablecerla aquí:
        <a href="{{ $resetPasswordUrl }}" target="_blank" rel="noopener noreferrer">{{ $resetPasswordUrl }}</a>
    </p>

    <p style="margin: 0;">
        Cordialmente,<br>
        Equipo de Servicio Comercial<br>
        Import Corporal Medical SAS
    </p>
</body>
</html>

