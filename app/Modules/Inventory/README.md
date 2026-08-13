# Inventory Module

## Proposito

Registra movimientos de stock y conserva el adaptador de sincronizacion externa
por contrato. ContaPyme es la fuente del stock base sincronizado (proyectado); el portal
calcula la disponibilidad local como `stock - reserved_stock` y nunca consulta
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
- HOLDs y reconciliacion de pedidos; eso vive en Orders.
- Reglas de carrito o checkout.
- Integraciones retiradas tratadas como activas.

## Puntos de entrada

- `Models/StockMovement.php`
- `Services/ContaPymeInventoryService.php`
- `Services/ContaPymeStockSyncRunner.php`
- Binding de `InventorySyncInterface` en `AppServiceProvider`.

## Colabora con

- `Catalog`: productos que exponen stock.
- `Orders`: HOLDs locales y reconciliacion de ventas.
- `Shared`: `InventorySyncInterface` e `InventoryItemData`.

## Notas actuales

- Match full: `products.sku` = ContaPyme `irecurso`. La reconciliacion puntual
  de variantes exige un `contapyme_inventory_mappings.irecurso` validado.
- El sync usa inventario **proyectado** de ContaPyme (`qinvproyectado`), que
  se persiste sin descuentos locales en `stock`. Los HOLDs permanecen separados
  en `reserved_stock`/`inventory_holds` y siempre se restan al mostrar disponibilidad.
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

### Precios comerciales

`contapyme:sync-prices` consulta `TCatElemInv::GetPrecioCalculado` únicamente
para productos activos con SKU local. La lista 1 se guarda como Gold en
`products.price` y la lista 3 como `silver_price`; catálogo, carrito y checkout
solo leen esos valores locales. Admite `--sku`, `--limit`, `--dry-run` y
`--force`. No se agrega al scheduler por defecto: la frecuencia debe ser una
decisión operativa explícita. Errores, ausencias y anomalías (`silver < gold`)
conservan el último precio válido y registran estado y diagnóstico. Las
variantes solo se procesan cuando tienen `contapyme_inventory_mappings.irecurso`.
