# Inventory Module

## Proposito

Registra movimientos de stock y conserva el adaptador de sincronizacion externa
por contrato. ContaPyme es la fuente del stock fisico; el portal conserva la
disponibilidad local rapida en `products.stock` y nunca consulta ContaPyme durante
una visita publica.

## Responsabilidades

- Auditar cambios de stock en `StockMovement`.
- Proveer un adaptador `ContaPymeInventoryService` detras de
  `InventorySyncInterface`.
- Normalizar datos externos de inventario hacia `InventoryItemData`.
- Sincronizar masivamente desde `GetSaldosProductosEnBodegas` para evitar una
  llamada remota por SKU.
- Conciliar identidad con `GetListaElemInv` antes de aceptar saldos o ceros.
- Registrar cada ejecucion en `contapyme_sync_runs` y los mapeos explicitos en
  `contapyme_inventory_mappings`.
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
- Las variantes activas solo se sincronizan cuando tienen un `irecurso` explicito
  en `contapyme_inventory_mappings`; las demas se marcan como
  `skipped_variants` y conservan su stock.
- El scheduler ejecuta el mismo `ContaPymeStockSyncJob` que el boton manual cada
  cinco minutos. Ambos origenes comparten lock, cooldown, reporte y estado.
- `php artisan contapyme:diagnose --json` valida `GetAuth` y `Test` sin modificar
  inventario ni mostrar `keyagente`.
- Un timeout, respuesta HTTP/JSON/DataSnap invalida o bodega no confirmada no
  escribe stock local. Un cero solo se guarda cuando la identidad y la bodega
  estan confirmadas.
- La bodega se fija con `CONTAPYME_BODEGA_ID`. Se aceptan los aliases
  `CONTAPYME_WAREHOUSE`, `CONTAPYME_BASE_URL` y `CONTAPYME_PASSWORD_HASH`, pero
  `.env.example` recomienda `CONTAPYME_URL` y `CONTAPYME_PASSWORD_MD5`.
- Hay carpetas residuales vacias bajo este modulo; no las uses como evidencia de
  funcionalidades activas.
