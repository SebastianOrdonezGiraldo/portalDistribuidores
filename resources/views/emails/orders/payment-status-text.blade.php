Hola {{ $order->contact_name }},

@if($kind === 'validated')
Confirmamos que el pago de tu pedido {{ $order->oc_number }} fue validado. Continuaremos con la gestión comercial y el despacho.
@elseif($kind === 'rejected')
El comprobante de pago de tu pedido {{ $order->oc_number }} fue rechazado. Puedes subir uno nuevo desde: {{ $orderUrl }}
@elseif($kind === 'expired')
El plazo para completar el pago de tu pedido {{ $order->oc_number }} venció y la reserva de inventario se liberó. Arma un nuevo pedido en: {{ $catalogUrl }}
@else
Hay una actualización sobre el pago de tu pedido {{ $order->oc_number }}.
@endif

Equipo ICMTHERAPY
