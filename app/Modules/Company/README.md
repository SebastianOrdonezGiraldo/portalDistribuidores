# Company Module

## Proposito

Superficie de autoservicio para usuarios distribuidores: dashboard, perfil de
empresa, sucursales, listas y gestion de pedidos propios.

## Responsabilidades

- Exponer rutas bajo `routes/company.php`.
- Mostrar dashboard e historial de pedidos del distribuidor.
- Permitir edicion de perfil, sucursales y listas de empresa.
- Permitir reordenes y edicion de pedidos permitidos.
- Aplicar capacidades de usuario y ownership por distribuidor.

## No debe contener

- Reglas globales de pedido; eso vive en Orders.
- CRUD administrativo de distribuidores; eso vive en Admin/AuthAccess.
- Reglas de producto o busqueda propias.
- Flujo activo de aprobacion interna sin reactivar rutas y permisos.

## Puntos de entrada

- `routes/company.php`
- `Http/Controllers/CompanyDashboardController.php`
- `Http/Controllers/CompanyOrderController.php`
- `Http/Controllers/CompanyProfileController.php`
- `Http/Controllers/CompanyBranchController.php`
- `Http/Controllers/CompanyListController.php`

## Colabora con

- `AuthAccess`: distribuidor asociado al usuario.
- `Orders`: pedidos, PDF, reorden y estados.
- `Catalog`: productos y variantes usados en listas/reordenes.
- `Shared`: enums, excepciones y permisos compartidos.

## Archivos clave para empezar

- `Policies/CompanyPolicy.php`
- `Models/CompanyBranch.php`
- `Models/CompanyList.php`
- `Models/CompanyListItem.php`
- `Http/Controllers/CompanyOrderController.php`
- `Http/Controllers/CompanyDashboardController.php`

## Notas actuales

- Las aprobaciones de empresa existen como codigo historico/inactivo: los helpers
  actuales de `User` no habilitan aprobacion ni obligan pedidos a revision.
- Si se reactiva aprobacion, hay que revisar rutas, tests, permisos y eventos de
  pedido antes de considerarla parte del flujo principal.
