@extends('emails.layouts.base')

@section('title', 'Nueva CTC '.$order->oc_number)
@section('preheader', 'Nueva solicitud registrada por '.$order->company_name.'.')
@section('heading', 'Nueva CTC '.$order->oc_number)

@section('content')
    <p style="margin: 0 0 12px;">
        Se registro una nueva solicitud de cotizacion en el portal.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 14px 0 18px; border: 1px solid #e2e8f0; border-radius: 10px;">
        <tr>
            <td style="padding: 11px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #475569;">Distribuidor</td>
            <td style="padding: 11px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px;" align="right">{{ $order->distributor?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td style="padding: 11px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #475569;">Razon social</td>
            <td style="padding: 11px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px;" align="right">{{ $order->company_name }}</td>
        </tr>
        <tr>
            <td style="padding: 11px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #475569;">NIT/Cedula</td>
            <td style="padding: 11px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px;" align="right">{{ $order->company_nit ?? '-' }}</td>
        </tr>
        <tr>
            <td style="padding: 11px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #475569;">Contacto</td>
            <td style="padding: 11px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px;" align="right">{{ $order->contact_name }}</td>
        </tr>
        <tr>
            <td style="padding: 11px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #475569;">Correo</td>
            <td style="padding: 11px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px;" align="right">{{ $order->contact_email ?? '-' }}</td>
        </tr>
        <tr>
            <td style="padding: 11px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #475569;">Ciudad</td>
            <td style="padding: 11px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px;" align="right">{{ $order->city ?? '-' }}{{ $order->department ? ' / '.$order->department : '' }}</td>
        </tr>
        <tr>
            <td style="padding: 11px 14px; font-size: 13px; color: #475569;">Total</td>
            <td style="padding: 11px 14px; font-size: 13px;" align="right"><strong>${{ number_format((float) $order->total_amount, 2, ',', '.') }}</strong></td>
        </tr>
    </table>

    <p style="margin: 0 0 10px;">
        <strong>Notas:</strong> {{ $order->notes ?: 'Sin notas' }}
    </p>

    <p style="margin: 0;">
        Se adjunta el PDF de la CTC para gestion interna.
    </p>
@endsection
