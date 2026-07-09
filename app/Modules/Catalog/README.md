# Catalog Module

## Proposito

Gestiona el catalogo comercial: productos, variantes, atributos, fotos,
documentos, videos, busqueda, importacion, carga segura y stock manual visible
para compra.

## Responsabilidades

- Exponer catalogo publico y detalle de producto.
- Crear, actualizar, duplicar e importar productos desde Admin.
- Gestionar variantes, atributos, fotos, documentos y videos.
- Implementar busqueda con SQL y ranking local.
- Validar cargas de archivos y limites de upload.
- Actualizar stock manual de productos y variantes.

## No debe contener

- Creacion de pedidos o reglas de checkout.
- Historial/auditoria de movimientos de stock; eso vive en Inventory.
- Descarga protegida final de documentos; eso vive en Documents.
- Reglas administrativas de alto nivel propias de Admin.

## Puntos de entrada

- `routes/web.php` para catalogo publico y detalle.
- `routes/admin.php` para CRUD, media, importacion y stock.
- `Http/Controllers/CatalogController.php`
- `Http/Controllers/ProductController.php`
- Admin usa `Actions`, `Services` y `Http/Requests` de este modulo.

## Colabora con

- `Categories`: filtros, arbol, descendientes y breadcrumbs.
- `Documents`: descarga protegida y tracking de documentos.
- `Orders`: lineas de carrito y snapshots de pedido.
- `Inventory`: movimientos de stock.
- `Shared`: contratos, enums, normalizadores y value objects.

## Archivos clave para empezar

- `Models/Product.php`
- `Queries/PostgresSearchEngine.php`
- `Services/ProductStockService.php`
- `Services/ProductVariantSyncService.php`
- `Services/ProductBulkImportService.php`
- `Actions/DuplicateProductAction.php`
- `Security/SafeUploadValidator.php`

## Notas actuales

- El stock operativo es manual/portal-owned en productos y variantes.
- `PostgresSearchEngine` prefiltra candidatos en SQL y rankea en PHP.
- Los documentos protegidos se adjuntan aqui, pero se descargan por Documents.
- Si agregas un tipo de documento protegido, revisa `Shared\Enums\DocumentType`.
