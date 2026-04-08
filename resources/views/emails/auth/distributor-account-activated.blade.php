@extends('emails.layouts.base')

@section('title', 'Cuenta activada | Portal Distribuidores')
@section('preheader', 'Tu cuenta fue activada y ya puedes ingresar al portal.')
@section('heading', 'Tu cuenta ya esta activa')

@section('content')
    <p style="margin: 0 0 12px;">
        Hola {{ $user->name }},
    </p>

    <p style="margin: 0 0 12px;">
        Tu cuenta asociada a <strong>{{ $distributor->name }}</strong> fue activada exitosamente.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 14px 0 18px; border: 1px solid #e2e8f0; border-radius: 10px;">
        <tr>
            <td style="padding: 12px 14px; font-size: 14px;">
                <p style="margin: 0 0 6px;"><strong>Usuario:</strong> {{ $user->email }}</p>
                <p style="margin: 0;"><strong>Empresa:</strong> {{ $distributor->name }}</p>
            </td>
        </tr>
    </table>

    <p style="margin: 0 0 12px;">
        Desde este momento puedes ingresar para gestionar catalogo, pedidos y documentos.
    </p>

    <p style="margin: 0;">
        Si no recuerdas tu contrasena, puedes restablecerla desde este enlace:
        <a href="{{ $resetPasswordUrl }}" target="_blank" rel="noopener noreferrer" style="color: #0f766e;">Restablecer contrasena</a>.
    </p>
@endsection

@section('cta')
    <a href="{{ $loginUrl }}" target="_blank" rel="noopener noreferrer" style="display: inline-block; padding: 12px 18px; background-color: #36b1bb; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700; border-radius: 8px;">
        Ingresar al portal
    </a>
@endsection
