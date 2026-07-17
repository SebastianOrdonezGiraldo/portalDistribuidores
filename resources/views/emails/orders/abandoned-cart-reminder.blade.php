@extends('emails.layouts.base')

@section('title', 'Tu carrito te espera | Portal Distribuidores')
@section('preheader', 'Aun tienes productos en tu carrito. Estaran disponibles por aproximadamente 12 horas mas.')
@section('heading', 'Tu carrito todavía te espera')

@section('content')
    <p style="margin: 0 0 12px;">
        Hola {{ $cart->user->name }},
    </p>

    <p style="margin: 0 0 16px;">
        Guardamos tu selección para que puedas continuar el pedido. Como el carrito lleva cerca de 36 horas sin cambios, permanecerá disponible durante aproximadamente 12 horas más.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin: 14px 0 18px; border: 1px solid #bae6eb; border-radius: 10px; background-color: #f0fdff;">
        <tr>
            <td style="padding: 14px 16px; font-size: 14px;">
                <p style="margin: 0 0 8px; color: #0f172a;"><strong>{{ $cart->items->sum('qty') }} unidades en tu carrito</strong></p>
                @foreach($cart->items->take(3) as $item)
                    <p style="margin: 4px 0; color: #475569;">• {{ $item->product?->name ?? 'Producto del catálogo' }} × {{ $item->qty }}</p>
                @endforeach
                @if($cart->items->count() > 3)
                    <p style="margin: 6px 0 0; color: #64748b;">Y {{ $cart->items->count() - 3 }} producto(s) más.</p>
                @endif
            </td>
        </tr>
    </table>

    <p style="margin: 0;">
        La disponibilidad y los precios se confirmarán nuevamente cuando continúes con el pedido.
    </p>
@endsection

@section('cta')
    <a href="{{ $cartUrl }}" target="_blank" rel="noopener noreferrer" style="display: inline-block; padding: 12px 18px; background-color: #36b1bb; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700; border-radius: 8px;">
        Continuar con mi carrito
    </a>
@endsection
