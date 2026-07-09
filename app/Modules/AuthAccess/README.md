# AuthAccess Module

## Proposito

Agrupa el acceso de distribuidores, roles de usuario, middleware de rol y correos
relacionados con activacion o verificacion de cuentas.

## Responsabilidades

- Representar distribuidores con `Models/Distributor.php`.
- Restringir rutas por rol con `Middleware/RoleMiddleware.php`.
- Enviar correos de registro, activacion y verificacion.
- Mantener relaciones de distribuidor con usuarios, pedidos y datos de empresa.

## No debe contener

- Formularios de login/registro genericos de Breeze.
- Reglas de pedido, catalogo o inventario.
- Pantallas administrativas completas; eso vive en Admin.

## Puntos de entrada

- Middleware `role:*` usado por `routes/admin.php` y `routes/company.php`.
- Relaciones desde `App\Models\User`.
- Correos disparados desde controladores o servicios de acceso/admin.

## Colabora con

- `Company`: datos empresariales ligados al distribuidor.
- `Orders`: historial y relacion de pedidos por distribuidor.
- `Shared`: `UserRole` y `DistributorStatus`.

## Archivos clave para empezar

- `Models/Distributor.php`
- `Middleware/RoleMiddleware.php`
- `Mail/DistributorRegistrationNotificationMail.php`
- `Mail/DistributorAccountActivatedMail.php`
- `Mail/EmailVerificationCodeMail.php`

## Notas actuales

- Los permisos finos no deben vivir en el middleware; usa helpers de `User`,
  policies o gates segun corresponda.
- `Distributor` es la entidad puente entre usuarios tipo distribuidor, empresa y
  pedidos.
