# Admin Module

## Proposito

Superficie interna para operar el portal: dashboard, catalogo, categorias,
distribuidores, usuarios, pedidos, media de productos e inventario exportable.

## Responsabilidades

- Exponer pantallas y endpoints bajo `routes/admin.php`.
- Orquestar operaciones administrativas sobre otros dominios.
- Validar entrada administrativa con Form Requests propios.
- Aplicar policies y permisos antes de mutar recursos.
- Producir datos de dashboard e inventario para uso interno.

## No debe contener

- Reglas de negocio duplicadas de Catalog, Orders, Categories o AuthAccess.
- Logica de stock fuera de los servicios del dominio correspondiente.
- Consultas de catalogo o pedido copiadas desde otros modulos.

## Puntos de entrada

- `routes/admin.php`
- `Http/Controllers/DashboardController.php`
- `Http/Controllers/ProductAdminController.php`
- `Http/Controllers/OrderAdminController.php`
- `Http/Controllers/DistributorAdminController.php`
- `Http/Controllers/UserAdminController.php`

## Colabora con

- `Catalog`: productos, variantes, media, carga masiva y stock.
- `Categories`: arbol de categorias administrable.
- `Orders`: pedidos, estados, PDF y regeneracion.
- `AuthAccess`: distribuidores y usuarios asociados.
- `Shared`: enums y excepciones compartidas.

## Archivos clave para empezar

- `Http/Controllers/ProductAdminController.php`
- `Http/Controllers/OrderAdminController.php`
- `Http/Controllers/ProductMediaController.php`
- `Services/DashboardDataService.php`
- `Services/InventoryPdfGenerator.php`

## Notas actuales

- Admin es una superficie de coordinacion; las reglas duras deben vivir en el
  modulo dueno del dominio.
- Cambios en productos o pedidos suelen requerir revisar tests feature de admin.
- Para duplicacion de productos, el flujo pasa por Catalog y conserva media
  cuando los archivos existen.
