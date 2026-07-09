# Documents Module

## Proposito

Entrega documentos protegidos de producto, aplica autorizacion, controla cuota
mensual cuando corresponde y registra descargas auditables.

## Responsabilidades

- Descargar documentos por rutas tipadas.
- Validar que la ruta coincida con el tipo real del documento.
- Aplicar `ProductDocumentPolicy`.
- Controlar limites mensuales de descarga para distribuidores.
- Registrar descargas en `DocumentDownload`.
- Migrar/fallback de archivos legacy cuando sea necesario.

## No debe contener

- Adjuntar o administrar documentos en productos; eso vive en Catalog/Admin.
- Definir tipos protegidos de forma aislada; usa `Shared\Enums\DocumentType`.
- Reglas de catalogo o pedido.

## Puntos de entrada

- Rutas `documents.*.download` en `routes/web.php`.
- `Http/Controllers/TechSheetDownloadController.php`
- `Services/TechSheetDownloadService.php`
- `Policies/ProductDocumentPolicy.php`

## Colabora con

- `Catalog`: `ProductDocument` y metadata de archivos.
- `AuthAccess`: distribuidor del usuario autenticado.
- `Shared`: `DocumentType`.

## Archivos clave para empezar

- `Http/Controllers/TechSheetDownloadController.php`
- `Services/TechSheetDownloadService.php`
- `Models/DocumentDownload.php`
- `Policies/ProductDocumentPolicy.php`
- `../Shared/Enums/DocumentType.php`

## Notas actuales

- `DocumentType::protectedValues()` es la fuente compartida para tipos protegidos.
- No agregues botones de documento solo en Blade; tambien revisa ruta, policy,
  enum, upload/admin y pruebas.
