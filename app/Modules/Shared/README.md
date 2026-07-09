# Shared Module

## Proposito

Contiene piezas transversales pequenas y estables que usan varios modulos:
contratos, enums, value objects, excepciones y helpers.

## Responsabilidades

- Definir contratos para bindings del contenedor.
- Centralizar enums compartidos por varios dominios.
- Proveer value objects sin persistencia.
- Exponer helpers de texto/media de bajo nivel.
- Mantener excepciones de dominio comunes.

## No debe contener

- Reglas de negocio especificas de un solo modulo.
- Controladores, requests o vistas.
- Servicios grandes que tengan un dueno de dominio claro.
- Dependencias hacia modulos concretos de negocio.

## Puntos de entrada

- Imports desde otros modulos.
- Bindings en `AppServiceProvider` para contratos.
- Enums usados por policies, modelos, controllers y vistas.

## Colabora con

- Todos los modulos pueden depender de Shared.
- Shared no debe depender de Admin, Catalog, Orders u otros modulos de negocio.

## Archivos clave para empezar

- `Contracts/SearchEngineInterface.php`
- `Contracts/InventorySyncInterface.php`
- `Enums/OrderStatus.php`
- `Enums/DocumentType.php`
- `Enums/UserRole.php`
- `ValueObjects/ProductSearchQuery.php`
- `Exceptions/DomainException.php`

## Notas actuales

- Si algo solo lo usa un modulo, dejalo en ese modulo hasta que exista una
  segunda necesidad real.
- `DocumentType::protectedValues()` es fuente compartida para documentos
  protegidos.
- `OrderStatus` centraliza transiciones y labels de estado de pedido.
