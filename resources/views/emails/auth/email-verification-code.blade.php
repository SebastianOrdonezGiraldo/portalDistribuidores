@extends('emails.layouts.base')

@section('title', 'Código de verificación | Portal Distribuidores')
@section('preheader', 'Tu código de verificación para activar el correo es: ' . $code)
@section('heading', 'Verifica tu correo electrónico')

@section('content')
    <p style="margin: 0 0 12px;">
        Hola {{ $user->name }},
    </p>

    <p style="margin: 0 0 18px;">
        Para completar tu registro en el Portal de Distribuidores, ingresa el siguiente código de verificación:
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 0 0 20px;">
        <tr>
            <td align="center">
                <div style="display: inline-block; padding: 16px 32px; background-color: #f1f5f9; border: 2px dashed #36b1bb; border-radius: 10px; font-size: 36px; font-weight: 700; letter-spacing: 10px; color: #0f172a; font-family: 'Courier New', Courier, monospace;">
                    {{ $code }}
                </div>
            </td>
        </tr>
    </table>

    <p style="margin: 0 0 8px; font-size: 14px; color: #475569;">
        Este código es válido por <strong>15 minutos</strong>.
    </p>

    <p style="margin: 0; font-size: 13px; color: #94a3b8;">
        Si no realizaste este registro, puedes ignorar este correo de forma segura.
    </p>
@endsection
