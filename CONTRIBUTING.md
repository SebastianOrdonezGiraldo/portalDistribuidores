# Contributing

Guia practica para contribuir al portal sin romper las convenciones del
monolito modular. Para instalar el proyecto desde cero, empieza en `README.md`.

## Tabla de contenidos

1. [Regla base](#regla-base)
2. [Convenciones de codigo](#convenciones-de-codigo)
3. [Comandos de validacion](#comandos-de-validacion)
4. [Agregar un modulo](#agregar-un-modulo)
5. [Migraciones](#migraciones)
6. [Tests](#tests)
7. [Checklist de PR](#checklist-de-pr)
8. [Cuando actualizar docs](#cuando-actualizar-docs)

## Regla base

Antes de cambiar codigo, ubica el dominio en `app/Modules`. Los controladores
deben orquestar requests, permisos y redirects; las reglas de negocio deben
vivir en Actions, Services, Policies, Queries, DTOs, Enums o Jobs segun aplique.

Evita crear abstracciones nuevas si el modulo ya tiene un patron local claro.
Revisa primero archivos vecinos y tests existentes.

## Convenciones de codigo

- Estilo: Laravel Pint con preset `laravel`, configurado en `pint.json`.
- Analisis estatico: Larastan/PHPStan nivel 5, configurado en `phpstan.neon`.
- Backend: PHP tipado, Form Requests para validar entrada y Policies/Gates para
  autorizacion.
- Frontend: Blade + Tailwind + Alpine. Si tocas assets, valida con Vite.
- Dominio: mantener namespaces bajo `App\Modules\{Domain}`.
- Compartido: usar `App\Modules\Shared` solo para piezas realmente comunes.
- Persistencia: usar Eloquent, factories y migraciones revisables.
- Errores de dominio: preferir `Shared\Exceptions\DomainException` cuando una
  regla de negocio rechaza una operacion esperada.

## Comandos de validacion

Comandos canonicos usados por CI o equivalentes locales:

```bash
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --no-progress --memory-limit=512M
composer test
npm run build
composer audit --locked --ignore-unreachable
# En CI el audit npm usa --package-lock-only sobre manifests de HEAD en un
# directorio aislado (sin npm ci), porque el runner inyecta tooling externo.
npm audit --audit-level=high --package-lock-only
```

Notas:

- En Windows PowerShell, si el shim Unix no ejecuta, usa el `.bat`
  equivalente, por ejemplo `vendor\bin\pint.bat --test` y
  `vendor\bin\phpstan.bat analyse --no-progress --memory-limit=512M`.
- Para cambios solo documentales, no es necesario correr toda la suite si
  verificas paths, comandos y nombres de clases mencionados.
- Si PHPStan falla por memoria, conserva `--memory-limit=512M` antes de asumir
  que hay un error nuevo de codigo.

## Agregar un modulo

1. Crea `app/Modules/{Domain}` con namespace `App\Modules\{Domain}`.
2. Agrega solo las carpetas necesarias:
   - `Actions`: casos de uso con reglas de negocio.
   - `Http/Controllers`: entrada HTTP del modulo.
   - `Http/Requests`: validacion y normalizacion.
   - `Models`: entidades Eloquent del dominio.
   - `Services`: logica reutilizable o infraestructura.
   - `Jobs`: trabajo asincrono.
   - `Events` y `Listeners`: eventos de dominio o integracion interna.
   - `DTOs`: datos tipados entre capas.
   - `Enums`: valores cerrados propios del dominio.
   - `Policies`: reglas de autorizacion por modelo.
   - `Queries`: consultas complejas o motores de lectura.
   - `Mail`: correos del dominio.
3. Expone rutas en el archivo adecuado:
   - `routes/web.php`: flujos publicos o compartidos.
   - `routes/admin.php`: panel interno.
   - `routes/company.php`: panel de empresa/distribuidor.
   - `routes/auth.php`: autenticacion.
4. Registra bindings en `AppServiceProvider` solo cuando haya un contrato real,
   por ejemplo `SearchEngineInterface` o `InventorySyncInterface`.
5. Si el modulo necesita valores compartidos, crea Contracts, Enums o Value
   Objects en `Shared` solo cuando mas de un modulo los use.
6. Agrega factories, seeders o tests si el cambio introduce persistencia o flujo
   de usuario.
7. Actualiza `ARCHITECTURE.md` si el modulo crea una dependencia nueva, evento,
   job, contrato o decision arquitectonica relevante.

## Migraciones

Crear migracion:

```bash
php artisan make:migration add_example_column_to_products_table
```

Flujo esperado:

- Mantener `up()` y `down()` reversibles cuando sea razonable.
- Usar nombres descriptivos y cambios pequenos por migracion.
- Probar localmente con `php artisan migrate`.
- Revisar estado con `php artisan migrate:status`.
- En produccion o staging usar `php artisan migrate --force`.
- No usar `migrate:fresh` fuera de entornos locales de desarrollo/testing.
- Si una migracion transforma datos de forma no reversible, dejarlo explicito en
  comentario y cubrir el comportamiento con test o comando de verificacion.

## Tests

Usa PHPUnit mediante Artisan:

```bash
composer test
```

Para iterar rapido, ejecuta filtros puntuales:

```bash
php artisan test --filter=CreateOrderActionTest
php artisan test tests/Feature/OrderControllerTest.php
```

Criterios por tipo de cambio:

- Actions, Services, Enums y Value Objects: tests unitarios.
- Controllers, rutas, Form Requests, auth, media, PDF y descargas: tests feature.
- Migraciones o comandos: tests feature con `RefreshDatabase` cuando aplique.
- Frontend/Blade con assets modificados: `npm run build`.
- Cambios de seguridad o dependencias: auditorias Composer y npm.

## Checklist de PR

- [ ] El cambio esta ubicado en el modulo correcto.
- [ ] Los controladores siguen delgados y delegan reglas de negocio.
- [ ] Las validaciones estan en Form Requests cuando hay entrada HTTP.
- [ ] Las reglas de permiso estan en Policies o Gates.
- [ ] Pint pasa o se ejecuto en modo fix antes del PR.
- [ ] PHPStan nivel 5 pasa con `--memory-limit=512M`.
- [ ] Tests relevantes pasan.
- [ ] `npm run build` pasa si se tocaron assets o vistas dependientes de assets.
- [ ] Las migraciones tienen `down()` o una razon clara para no ser reversibles.
- [ ] La documentacion se actualizo si cambiaron arquitectura, comandos, rutas,
  eventos, jobs o decisiones clave.

## Cuando actualizar docs

Actualiza `ARCHITECTURE.md` cuando cambie alguna de estas piezas:

- Dependencias entre modulos.
- Flujo de carrito, checkout, pedido, PDF o email.
- Eventos, listeners, jobs o contratos registrados.
- Decision de arquitectura, busqueda, inventario, almacenamiento o colas.

Actualiza `docs/documentacion-api.md` o regenera Scribe si cambian rutas,
parametros, respuestas HTTP, descargas o formularios documentados.

Actualiza `README.md`, `DEPLOY.md` o `CI_CD_SETUP.md` solo si cambia instalacion,
operacion, despliegue o pipeline.
