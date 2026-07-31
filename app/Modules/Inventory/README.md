# Inventory Module

## Proposito

Registra movimientos de stock y conserva el adaptador de sincronizacion externa
por contrato. ContaPyme es la fuente del stock disponible (proyectado); el portal
conserva la disponibilidad local rapida en `products.stock` y nunca consulta
ContaPyme durante una visita publica.

## Responsabilidades

- Auditar cambios de stock en `StockMovement`.
- Proveer un adaptador `ContaPymeInventoryService` detras de
  `InventorySyncInterface`.
- Normalizar datos externos de inventario hacia `InventoryItemData`.
- Sincronizar masivamente desde `GetSaldosProductosEnBodegas` con
  `binventarioproyectado` (match `products.sku` = ContaPyme `irecurso`).
- Confirmar ceros con `GetExisteElemInv` cuando un SKU del portal no viene en
  el bulk (ContaPyme omite saldos en cero).
- Registrar cada ejecucion en `contapyme_sync_runs`.
- Permitir sincronizacion puntual por SKU mediante el mismo endpoint de saldos
  filtrado por `irecurso`.

## No debe contener

- El formulario administrativo de stock; eso vive en Catalog/Admin.
- Descuentos/restauraciones por pedidos; eso vive en Orders.
- Reglas de carrito o checkout.
- Integraciones retiradas tratadas como activas.

## Puntos de entrada

- `Models/StockMovement.php`
- `Services/ContaPymeInventoryService.php`
- `Services/ContaPymeStockSyncRunner.php`
- Binding de `InventorySyncInterface` en `AppServiceProvider`.

## Colabora con

- `Catalog`: productos que exponen stock.
- `Orders`: deducciones/restauraciones registradas como movimientos.
- `Shared`: `InventorySyncInterface` e `InventoryItemData`.

## Notas actuales

- Match: `products.sku` = ContaPyme `irecurso`. No se usa
  `contapyme_inventory_mappings` en el sync.
- El sync usa inventario **proyectado** de ContaPyme (`qinvproyectado`), que
  corresponde al disponible (contable menos pedidos sin entregar). El sync no
  vuelve a restar reservas del portal.
- El sync no filtra por bodega: suma el saldo proyectado de todas las filas
  devueltas.
- Flujo full: `GetAuth` → `GetSaldosProductosEnBodegas` (proyectado) → por cada
  producto activo con SKU, actualizar si aparece en el bulk; si no,
  `GetExisteElemInv` (existe → escribir 0; no existe → no tocar /
  `missing_contapyme`).
- El scheduler ejecuta el mismo `ContaPymeStockSyncJob` que el boton manual cada
  dos minutos.
- `php artisan contapyme:diagnose --json` valida `GetAuth` y `Test` sin modificar
  inventario ni mostrar `keyagente`.
- Un timeout o respuesta HTTP/JSON/DataSnap invalida no escribe stock local.
