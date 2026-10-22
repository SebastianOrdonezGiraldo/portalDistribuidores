# Company Module

## Proposito

Superficie de autoservicio para usuarios distribuidores: dashboard, perfil de
empresa, sucursales y gestion de pedidos propios.

## Responsabilidades

- Exponer rutas bajo `routes/company.php`.
- Mostrar dashboard e historial de pedidos del distribuidor.
- Permitir edicion de perfil y sucursales de empresa.
- Permitir reordenes y edicion de pedidos permitidos.
- Aplicar capacidades de usuario y ownership por distribuidor.

## No debe contener

- Reglas globales de pedido; eso vive en Orders.
- CRUD administrativo de distribuidores; eso vive en Admin/AuthAccess.
- Reglas de producto o busqueda propias.
- Flujo de aprobacion interna de empresa (se elimino; ver notas).

## Puntos de entrada

- `routes/company.php`
- `Http/Controllers/CompanyDashboardController.php`
- `Http/Controllers/CompanyOrderController.php`
- `Http/Controllers/CompanyProfileController.php`
- `Http/Controllers/CompanyBranchController.php`

## Colabora con

- `AuthAccess`: distribuidor asociado al usuario.
- `Orders`: pedidos, PDF, reorden y estados.
- `Catalog`: productos y variantes usados en reordenes.
- `Shared`: enums, excepciones y permisos compartidos.

## Archivos clave para empezar

- `Policies/CompanyPolicy.php`
- `Models/CompanyBranch.php`
- `Models/CompanyList.php`
- `Models/CompanyListItem.php`
- `Http/Controllers/CompanyOrderController.php`
- `Http/Controllers/CompanyDashboardController.php`

## Notas actuales

- Las aprobaciones internas y las listas de empresa se retiraron (rutas en abril
  de 2026, codigo en octubre de 2026). `User::orderRequiresApproval()` sigue
  devolviendo `false`; `CompanyApprovalControllerTest` verifica que esas rutas
  no existan.
- Las tablas `company_lists` y `company_list_items` y sus modelos siguen
  existiendo; eliminarlas requiere una migracion explicita.
