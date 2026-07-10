# Inventory Module

## Proposito

Registra movimientos de stock y conserva el adaptador de sincronizacion externa
por contrato. ContaPyme es la fuente del stock fisico para productos simples;
el portal conserva disponibilidad local rapida en `products.stock`.

## Responsabilidades

- Auditar cambios de stock en `StockMovement`.
- Proveer un adaptador `ContaPymeInventoryService` detras de
  `InventorySyncInterface`.
- Normalizar datos externos de inventario hacia `InventoryItemData`.
- Sincronizar masivamente desde `GetSaldosProductosEnBodegas` para evitar una
  llamada remota por SKU.
- Permitir sincronizacion puntual por SKU mediante
  `GetSaldoFisicoProductoEnBodegas` para diagnostico y compatibilidad.

## No debe contener

- El formulario administrativo de stock; eso vive en Catalog/Admin.
- Descuentos/restauraciones por pedidos; eso vive en Orders.
- Reglas de carrito o checkout.
- Integraciones retiradas tratadas como activas.

## Puntos de entrada

- `Models/StockMovement.php`
- `Services/ContaPymeInventoryService.php`
- Binding de `InventorySyncInterface` en `AppServiceProvider`.

## Colabora con

- `Catalog`: productos y variantes que exponen stock.
- `Orders`: deducciones/restauraciones registradas como movimientos.
- `Shared`: `InventorySyncInterface` e `InventoryItemData`.

## Archivos clave para empezar

- `Models/StockMovement.php`
- `Services/ContaPymeInventoryService.php`
- `../Shared/Contracts/InventorySyncInterface.php`
- `../Shared/ValueObjects/InventoryItemData.php`

## Notas actuales

- `products.stock` es disponibilidad: stock fisico de la bodega configurada
  menos cantidades de pedidos en `submitted`, `sold`, `dispatched` y
  `delivered`.
- `OrderInventoryService` conserva la deduccion/restauracion inmediata para
  que una orden afecte disponibilidad entre dos sincronizaciones. El sync
  masivo recalcula ese valor desde ContaPyme y las reservas activas.
- Las variantes activas son una anomalia de catalogo para esta integracion y se
  marcan como `skipped_variants`; no se sincronizan.
- La bodega se fija con `CONTAPYME_BODEGA_ID`. Se aceptan los aliases
  `CONTAPYME_WAREHOUSE`, `CONTAPYME_BASE_URL` y `CONTAPYME_PASSWORD_HASH`, pero
  `.env.example` recomienda `CONTAPYME_URL` y `CONTAPYME_PASSWORD_MD5`.
- Hay carpetas residuales vacias bajo este modulo; no las uses como evidencia de
  funcionalidades activas.
