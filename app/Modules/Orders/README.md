# Orders Module

## Proposito

Gestiona el flujo comercial de carrito, checkout, creacion y edicion de pedidos,
estados, inventario asociado, PDF de cotizacion y notificaciones por correo.

## Responsabilidades

- Mantener el carrito en sesion.
- Crear pedidos desde checkout con snapshots de producto/variante.
- Validar y aplicar transiciones de estado.
- Descontar o restaurar inventario al cruzar estados que consumen stock.
- Generar PDFs privados de cotizacion.
- Enviar correos mediante jobs en cola.
- Exponer detalle, confirmacion y descarga de PDF de pedido.

## No debe contener

- Reglas de administracion de producto.
- Definicion de categorias o busqueda de catalogo.
- Reglas de documentos protegidos de producto.
- Permisos globales fuera de policies/capacidades existentes.

## Puntos de entrada

- `routes/web.php` para carrito, checkout y pedidos publicos.
- `Actions/CreateOrderAction.php`
- `Actions/UpdateOrderAction.php`
- `Services/Cart/CartService.php`
- `Services/OrderStatusTransitionService.php`
- `Listeners/GenerateOrderPdfListener.php`

## Colabora con

- `Catalog`: productos, variantes, precios y stock.
- `Inventory`: auditoria de movimientos de stock.
- `Company`: historial, edicion y reorden de distribuidor.
- `Admin`: operaciones internas sobre pedidos.
- `Shared`: `OrderStatus`, excepciones y value objects.

## Archivos clave para empezar

- `Http/Controllers/CartController.php`
- `Http/Controllers/CheckoutController.php`
- `Http/Controllers/OrderController.php`
- `Actions/CreateOrderAction.php`
- `Services/OrderInventoryService.php`
- `Services/OrderPdfGenerator.php`
- `Jobs/GenerateOrderPdfJob.php`
- `Jobs/SendOrderNotificationEmailJob.php`

## Notas actuales

- El evento principal es `OrderPlaced -> GenerateOrderPdfListener`.
- El listener encadena `GenerateOrderPdfJob` y luego
  `SendOrderNotificationEmailJob`; no hay listener separado de email.
- El flujo normal no requiere aprobacion interna de empresa.

## Pago manual (auditorias previas)

- `order_status` y `payment_status` son ortogonales. Validar pago pone
  `payment_status=validated` y auto-avanza a `sold` con nota de sistema.
  No se puede despachar si el pago no es `validated` o `not_applicable`.
- Stock al crear: `lockForUpdate` + transaccion del caller (atomico en paths
  actuales). Cancelar desde estados que consumen inventario restaura via
  `OrderStatusTransitionService` + `increaseForOrder`.
- ContaPyme reserva por `inventoryConsuming()` de `order_status`. Por eso al
  expirar `pending_upload` se marca `expired` y se cancela el pedido (libera
  stock y deja de contar como reserva).
- Detalle de pedido sin login: solo sesion `orders.guest_access`. El magic
  link de comprobante es la puerta publica cross-device (token hasheado).
