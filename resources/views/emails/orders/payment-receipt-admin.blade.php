@extends('emails.layouts.base')

@section('title', 'Comprobante Oro '.$order->oc_number)
@section('preheader', 'Cliente ICM Oro subió comprobante de pago para '.$order->oc_number.'.')
@section('heading', 'Comprobante de pago — Cliente Oro')

@section('content')
    <p style="margin: 0 0 12px;">
        Un cliente <strong>ICM Oro</strong> subió el comprobante de pago. El archivo va adjunto a este correo.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 14px 0 18px; border: 1px solid #e2e8f0; border-radius: 10px;">
        <tr>
            <td style="padding: 11px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #475569;">Pedido</td>
            <td style="padding: 11px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #0f172a;" align="right"><strong>{{ $order->oc_number }}</strong></td>
        </tr>
        <tr>
            <td style="padding: 11px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #475569;">Empresa</td>
            <td style="padding: 11px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #0f172a;" align="right">{{ $order->company_name }}</td>
        </tr>
        <tr>
            <td style="padding: 11px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #475569;">NIT / Cédula</td>
            <td style="padding: 11px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #0f172a;" align="right">{{ $order->company_nit ?? '—' }}</td>
        </tr>
        <tr>
            <td style="padding: 11px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #475569;">Contacto</td>
            <td style="padding: 11px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #0f172a;" align="right">{{ $order->contact_name }}</td>
        </tr>
        <tr>
            <td style="padding: 11px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #475569;">Correo</td>
            <td style="padding: 11px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #0f172a;" align="right">{{ $order->contact_email ?? '—' }}</td>
        </tr>
        <tr>
            <td style="padding: 11px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #475569;">Teléfono</td>
            <td style="padding: 11px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #0f172a;" align="right">{{ $order->phone ?? '—' }}</td>
        </tr>
        <tr>
            <td style="padding: 11px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #475569;">Método de pago</td>
            <td style="padding: 11px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #0f172a;" align="right">{{ $paymentMethodLabel }}</td>
        </tr>
        <tr>
            <td style="padding: 11px 14px; font-size: 13px; color: #475569;">Total</td>
            <td style="padding: 11px 14px; font-size: 13px; color: #0f172a;" align="right"><strong>${{ number_format((float) $order->total_amount, 0, ',', '.') }}</strong></td>
        </tr>
    </table>

    <p style="margin: 0;">
        Revisa el pedido en el panel de administración para validar o rechazar el comprobante.
    </p>
@endsection

@section('cta')
    <a href="{{ $adminOrderUrl }}" target="_blank" rel="noopener noreferrer" style="display: inline-block; padding: 12px 18px; background-color: #36b1bb; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700; border-radius: 8px;">
        Abrir pedido en admin
    </a>
@endsection
