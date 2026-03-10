# Portal de Distribuidores

Aplicación Laravel (Modular Monolith) para **Import Corporal Medical SAS**, enfocada en una experiencia de compra rápida para distribuidores.

## Stack
- Laravel 12
- PHP 8.3+
- PostgreSQL
- Blade + TailwindCSS
- Laravel Breeze (Blade)
- Colas configurables (`database` / `redis`)
- PDF con `barryvdh/laravel-dompdf`

## Arquitectura
- Dominio modular en `app/Modules`
- Controllers delgados (orquestación)
- Business logic en Actions/Services
- Validación en FormRequests
- Autorización en Policies + middleware `role:*`
- Procesos externos/lentos en Jobs
- Integraciones externas mediante Adapters (interfaces)
- Eventos de dominio (`OrderPlaced`)
- Search Strategy (`PostgresSearchEngine`, stub `MeilisearchSearchEngine`)

## Instalación
1. `composer install`
2. `cp .env.example .env`
3. `php artisan key:generate`
4. Configurar PostgreSQL en `.env`:
   - `DB_CONNECTION=pgsql`
   - `DB_HOST=127.0.0.1`
   - `DB_PORT=5432`
   - `DB_DATABASE=portal_distribuidores`
   - `DB_USERNAME=postgres`
   - `DB_PASSWORD=...`
5. `php artisan migrate`
6. (Opcional, solo ambiente demo) `SEED_DEMO_DATA=true php artisan db:seed`
7. `php artisan storage:link`
8. `npm install && npm run build`
9. `php artisan serve`
10. Correr worker de colas:
   - `php artisan queue:work`

Arranque rápido en Windows (abre servidor + worker en ventanas separadas):
- `powershell -ExecutionPolicy Bypass -File .\start.ps1`
- Opcional con migraciones pendientes: `powershell -ExecutionPolicy Bypass -File .\start.ps1 -RunMigrations`

## Credenciales seed
- Admin:
  - `admin@importcorporal.test`
  - `Password123!`
- Distribuidor demo:
  - `dist@importcorporal.test`
  - `Password123!`

Por defecto, `php artisan db:seed` solo crea usuarios de acceso.
El catálogo/categorías demo se siembra únicamente cuando `SEED_DEMO_DATA=true`.

## PDF de cotización
La URL del PDF se publica por `storage:link` en:
- `/storage/orders/CTC-XXXXXX.pdf`

## Rutas principales

Distribuidor:
- `/catalog`
- `/products/{id}`
- `/cart`
- `/checkout`
- `POST /orders`
- `/orders/{id}`
- `/documents/tech-sheet/{productDocument}`

Admin:
- `/admin`
- `/admin/categories`
- `/admin/products`
- `/admin/distributors`
- `/admin/users`
- `/admin/orders`
