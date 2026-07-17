@extends('emails.layouts.base')

@section('title', 'Código de verificación | Portal Distribuidores')
@section('preheader', 'Tu código de verificación para activar el correo es: ' . $code)

@section('email_header')
    <tr>
        <td style="padding: 20px 28px; background-color: #ffffff; border-bottom: 1px solid #dbeafe;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td valign="middle">
                        <img src="{{ rtrim((string) config('app.url'), '/') }}/images/import-corporal-logo.png" alt="Import Corporal Medical SAS" width="142" style="display: block; width: 142px; height: auto; max-width: 142px;">
                    </td>
                    <td valign="middle" align="right">
                        <span style="display: inline-block; padding: 7px 10px; border-radius: 999px; background-color: #e0f2fe; color: #0369a1; font-size: 11px; font-weight: 700; letter-spacing: 0.7px; text-transform: uppercase;">Verificación segura</span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
@endsection

@section('heading', 'Confirma tu correo')

@section('content')
    <p style="margin: 0 0 12px;">
        Hola {{ $recipientName }},
    </p>

    <p style="margin: 0 0 22px;">
        Ya casi terminas. Usa este código para confirmar tu correo y continuar con tu solicitud como distribuidor.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 0 0 22px; background-color: #f0f9ff; border: 1px solid #bae6fd; border-radius: 14px;">
        <tr>
            <td align="center" style="padding: 24px 20px 22px;">
                <p style="margin: 0 0 14px; font-size: 11px; font-weight: 700; letter-spacing: 1.2px; text-transform: uppercase; color: #0369a1;">
                    Tu código de acceso
                </p>
                <table role="presentation" cellpadding="0" cellspacing="0" align="center">
                    <tr>
                        @foreach (str_split($code) as $digit)
                            <td style="width: 44px; height: 52px; border: 1px solid #7dd3fc; border-radius: 10px; background-color: #ffffff; color: #0f3b5b; font-family: Arial, Helvetica, sans-serif; font-size: 26px; font-weight: 700; line-height: 52px; text-align: center;">{{ $digit }}</td>
                            @if (! $loop->last)
                                <td style="width: 8px; font-size: 0; line-height: 0;">&nbsp;</td>
                            @endif
                        @endforeach
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 0 0 18px;">
        <tr>
            <td style="padding: 12px 14px; border-left: 3px solid #38bdf8; background-color: #f8fafc; font-size: 13px; line-height: 1.55; color: #475569;">
                El código es válido durante <strong style="color: #0f3b5b;">15 minutos</strong> y solo puede usarse una vez.
            </td>
        </tr>
    </table>

    <p style="margin: 0; font-size: 13px; line-height: 1.55; color: #64748b;">
        Si no realizaste este registro, puedes ignorar este correo de forma segura.
    </p>
@endsection
