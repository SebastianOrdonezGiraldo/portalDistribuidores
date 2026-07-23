{{-- Payment status notification to customer (validated / rejected / expired). --}}
<p>Hola {{ $order->contact_name }},</p>

@if($kind === 'validated')
    <p>Confirmamos que el pago de tu pedido <strong>{{ $order->oc_number }}</strong> fue validado. Continuaremos con la gestión comercial y el despacho.</p>
@elseif($kind === 'rejected')
    <p>El comprobante de pago de tu pedido <strong>{{ $order->oc_number }}</strong> fue rechazado. Puedes subir uno nuevo desde el detalle del pedido mientras la reserva siga vigente.</p>
    <p><a href="{{ $orderUrl }}">Subir nuevo comprobante</a></p>
@elseif($kind === 'expired')
    <p>El plazo para completar el pago de tu pedido <strong>{{ $order->oc_number }}</strong> venció y la reserva de inventario se liberó.</p>
    <p>Puedes armar un nuevo pedido desde el <a href="{{ $catalogUrl }}">catálogo</a>.</p>
@else
    <p>Hay una actualización sobre el pago de tu pedido <strong>{{ $order->oc_number }}</strong>.</p>
@endif

<p>Equipo ICMTHERAPY</p>
