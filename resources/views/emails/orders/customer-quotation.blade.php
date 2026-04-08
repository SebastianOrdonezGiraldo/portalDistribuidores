@extends('emails.layouts.base')

@section('title', 'Cotizacion '.$order->oc_number.' recibida')
@section('preheader', 'Recibimos tu solicitud de cotizacion y ya esta en revision comercial.')
@section('heading', 'Recibimos tu solicitud de cotizacion '.$order->oc_number)

@section('content')
    <p style="margin: 0 0 12px;">
        Hola {{ $order->contact_name }},
    </p>

    <p style="margin: 0 0 12px;">
        Gracias por contactarnos. Registramos tu solicitud correctamente y nuestro equipo comercial la revisara en breve.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 14px 0 18px; border: 1px solid #e2e8f0; border-radius: 10px;">
        <tr>
            <td style="padding: 12px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #475569;">Numero de cotizacion</td>
            <td style="padding: 12px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #0f172a;" align="right"><strong>{{ $order->oc_number }}</strong></td>
        </tr>
        <tr>
            <td style="padding: 12px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #475569;">Empresa</td>
            <td style="padding: 12px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #0f172a;" align="right">{{ $order->company_name }}</td>
        </tr>
        <tr>
            <td style="padding: 12px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #475569;">Correo</td>
            <td style="padding: 12px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #0f172a;" align="right">{{ $order->contact_email ?? '-' }}</td>
        </tr>
        <tr>
            <td style="padding: 12px 14px; font-size: 13px; color: #475569;">Total estimado</td>
            <td style="padding: 12px 14px; font-size: 13px; color: #0f172a;" align="right"><strong>${{ number_format((float) $order->total_amount, 2, ',', '.') }}</strong></td>
        </tr>
    </table>

    <p style="margin: 0 0 12px;">
        Adjuntamos el PDF con el detalle de la cotizacion para tu referencia.
    </p>

    <p style="margin: 0;">
        Este correo confirma la recepcion de tu solicitud, <strong>no confirma una compra</strong>.
    </p>
@endsection

@section('cta')
    <a href="{{ $portalUrl }}" target="_blank" rel="noopener noreferrer" style="display: inline-block; padding: 12px 18px; background-color: #36b1bb; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700; border-radius: 8px;">
        Ir al portal
    </a>
@endsection
