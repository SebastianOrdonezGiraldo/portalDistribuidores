# Inventory Module

## Proposito

Registra movimientos de stock y conserva el adaptador de sincronizacion externa
por contrato. El stock operativo actual se administra desde el portal.

## Responsabilidades

- Auditar cambios de stock en `StockMovement`.
- Proveer un adaptador `ContaPymeInventoryService` detras de
  `InventorySyncInterface`.
- Normalizar datos externos de inventario hacia `InventoryItemData`.
- Permitir sincronizacion puntual por SKU cuando se use el contrato.

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

- El flujo principal es portal-owned/manual: `ProductStockService` y
  `OrderInventoryService` actualizan stock local y registran movimientos.
- Hay carpetas residuales vacias bajo este modulo; no las uses como evidencia de
  funcionalidades activas.
