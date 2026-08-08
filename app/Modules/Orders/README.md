# Orders Module

## Proposito

Gestiona el flujo comercial de carrito, checkout, creacion y edicion de pedidos,
estados, inventario asociado, PDF de cotizacion y notificaciones por correo.

## Responsabilidades

- Mantener el carrito en sesion.
- Crear pedidos desde checkout con snapshots de producto/variante.
- Validar y aplicar transiciones de estado.
- Crear, ajustar y liberar HOLDs locales sin mutar el stock base sincronizado.
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

## Pago manual e inventario

- `order_status` y `payment_status` son ortogonales. Validar pago pone
  `payment_status=validated` y mantiene el pedido en `submitted`.
  No se puede despachar si el pago no es `validated` o `not_applicable`.
- `products.stock` y `product_variants.stock` son el stock base. La disponibilidad
  es `stock - reserved_stock`, donde `reserved_stock` es una proyeccion atomica
  y auditable de `inventory_holds` activos.
- Solo `submitted` mantiene HOLD. Cancelar o expirar el pago libera el HOLD sin
  aumentar el stock base.
- `submitted -> sold` consulta ContaPyme en modo lectura, actualiza el stock base
  y solo entonces libera el HOLD. Un fallo conserva `submitted` y el HOLD.
- Detalle de pedido sin login: solo sesion `orders.guest_access`. El magic
  link de comprobante es la puerta publica cross-device (token hasheado).
