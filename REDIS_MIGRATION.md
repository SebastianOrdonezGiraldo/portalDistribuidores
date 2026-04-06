# Migración: Database Queue → Redis Queue

**Documento:** Guía completa para migrar el driver de colas de PostgreSQL (database) a Redis en producción.

**Timeline:** 3-4 días (incluye testing en staging + monitoring inicial)

**Mejora esperada:** ~4x más rápido en procesamiento de jobs

---

## 1. Introducción

El Portal de Distribuidores actualmente usa **PostgreSQL** como driver de colas. Los jobs se almacenan en la tabla `jobs` y son procesados por un worker systemd.

### Problema

- ❌ PostgreSQL multiuso se sobrecarga con picos de órdenes
- ❌ Database driver no está optimizado para colas
- ❌ Latencia más alta (DB queries vs in-memory Redis)
- ❌ Escalabilidad limitada (múltiples workers competirán por acceso a BD)

### Solución

Migrar a **Redis**, un datastore in-memory optimizado para colas:

- ✅ ~4x más rápido que PostgreSQL para colas
- ✅ Menor latencia y overhead
- ✅ Mejor escalabilidad (múltiples workers sin contención)
- ✅ Menor carga en BD principal
- ✅ Failed jobs aún se almacenan en DB (seguridad)

---

## 2. Arquitectura

### Estado Actual (Database Queue)

```
Distribuidor crea orden
    ↓
OrderPlaced evento
    ↓
GenerateOrderPdfJob → despachado a tabla `jobs`
SendOrderNotificationEmailJob → despachado a tabla `jobs`
    ↓
Worker systemd lee tabla `jobs` cada ~3 segundos
    ↓
Jobs procesados (PDF generado, email enviado)
    ↓
Registrados en `failed_jobs` si fallan
```

### Post-Migración (Redis Queue)

```
Distribuidor crea orden
    ↓
OrderPlaced evento
    ↓
GenerateOrderPdfJob → despachado a Redis queue:default
SendOrderNotificationEmailJob → despachado a Redis queue:default
    ↓
Worker systemd lee Redis inmediatamente (sin polling)
    ↓
Jobs procesados (PDF generado, email enviado)
    ↓
Registrados en `failed_jobs` (BD) si fallan
```

**Cambios principales:**
- Jobs: PostgreSQL → Redis
- Failed jobs: PostgreSQL (sin cambios, más seguro)
- Worker: Mismo código, distinto storage backend

---

## 3. Requisitos Previos

### Local (Desarrollo)

- PHP 8.3 con Composer
- PostgreSQL (para BD principal, sin cambios)
- **Redis server instalado:**
  - macOS: `brew install redis`
  - Linux: `apt install -y redis-server`
  - Windows: WSL2 + apt, o usar imagen Docker

### Staging/Producción

- VPS Hostinger Ubuntu 24.04
- SSH access como root o sudo
- **Redis instalable:** `apt install -y redis-server redis-tools`

---

## 4. Fase 1: Preparación Local (DEV)

### 4.1 Instalar Predis Dependency

Ya está en `composer.json`:

```bash
grep "predis/predis" composer.json
# Output: "predis/predis": "^2.2"
```

Si no está, ejecutar:
```bash
composer require predis/predis:^2.2
```

### 4.2 Actualizar `.env` Local

Copiar variables Redis de `.env.example`:

```bash
# .env
QUEUE_CONNECTION=database          # Dejar así en desarrollo
REDIS_QUEUE_HOST=127.0.0.1
REDIS_QUEUE_PORT=6379
REDIS_QUEUE_PASSWORD=
REDIS_QUEUE_DB=2
REDIS_QUEUE_CONNECTION=default
```

### 4.3 Instalar Redis Server Localmente

**macOS:**
```bash
brew install redis
brew services start redis
```

**Linux (Ubuntu/Debian):**
```bash
apt update && apt install -y redis-server
systemctl start redis-server
systemctl enable redis-server
```

**Docker (alternativa portable):**
```bash
docker run -d -p 6379:6379 redis:latest
```

### 4.4 Verificar Conectividad Redis

```bash
# Test 1: CLI
redis-cli ping
# Output: PONG

# Test 2: PHP/Artisan
php artisan tinker
>>> Redis::ping()
# Output: 'PONG'
>>> Redis::set('test', 'hello')  # Returns: true
>>> Redis::get('test')           # Output: 'hello'
>>> exit
```

### 4.5 Test de Queue en Local (Database Driver)

Mientras seguimos con `QUEUE_CONNECTION=database` localmente:

```bash
# Terminal 1: Start worker
php artisan queue:listen --tries=1 --timeout=0

# Terminal 2: Create test order
php artisan tinker
>>> $order = App\Modules\Orders\Models\Order::factory()->create(['status' => 'pending']);
>>> event(new App\Modules\Orders\Events\OrderPlaced($order));
>>>  exit

# Verificar en Terminal 1: Job debería procesarse
# Verificar en BD: order.pdf_path debería tener valor
```

### 4.6 Compilar Assets (si es necesario)

```bash
npm install
npm run build
```

### 4.7 Ejecutar Tests

```bash
composer test

# Esperado: ✅ Todos los tests pasan (jobs son driver-agnostic)
```

**Resultado esperado:** Fase local completada, Redis instalado y verificado, tests pasando.

---

## 5. Fase 2: Testing en Staging (1-2 días)

### 5.1 Prerequisites

- SSH access a `staging-pedidos.importcorporalmedical.com`
- Working directory: `/var/www/portalDistribuidores-staging`

### 5.2 Instalar Redis en Staging VPS

```bash
# Como root
ssh root@staging.example.com

sudo apt update && apt install -y redis-server redis-tools

# Verificar
redis-cli ping
# Output: PONG

# Auto-start on reboot
sudo systemctl enable redis-server
sudo systemctl status redis-server
```

### 5.3 Deploy Código a Staging

```bash
cd /var/www/portalDistribuidores-staging

# Git pull (Predis ya en composer.json)
git pull origin develop

# Instalar dependencias
composer install

# Assets (if touched)
npm install
npm run build

# Clear cache
php artisan cache:clear
php artisan config:clear
```

### 5.4 Configurar `.env` Staging para Testing

```bash
# Editar con sudo nano
sudo nano /var/www/portalDistribuidores-staging/.env

# Cambiar:
QUEUE_CONNECTION=redis
REDIS_QUEUE_DB=3          # Diferente de producción (aislamiento)

# Guardar (Ctrl+O, Ctrl+X)
```

### 5.5 Verificar Conectividad

```bash
cd /var/www/portalDistribuidores-staging

php artisan tinker
>>> Redis::ping()
# Output: 'PONG'
>>> exit
```

### 5.6 Drain Pending Database Jobs

Antes de switchover, procesar jobs históricos con driver anterior:

```bash
# Procesar jobs que quedan en tabla jobs
QUEUE_CONNECTION=database php artisan queue:work --tries=3 --timeout=0 --stop-when-empty

# Verificar vacío
psql -d portal_distribuidores_staging -c "SELECT COUNT(*) FROM jobs;"
# Output: 0
```

### 5.7 Test Queue Operation

**Terminal 1:** Iniciar worker con Redis

```bash
php artisan queue:work --queue=default --tries=3 --sleep=3 --timeout=0

# Debería mostrar:
# Processing [1/1] » App\Modules\Orders\Jobs\GenerateOrderPdfJob (cuando hay jobs)
```

**Terminal 2:** Crear orden de test

```bash
cd /var/www/portalDistribuidores-staging

php artisan tinker
>>> $distributor = App\Modules\AuthAccess\Models\Distributor::first();
>>> $order = App\Modules\Orders\Models\Order::factory(['distributor_id' => $distributor->id])->create(['status' => 'pending']);
>>> event(new App\Modules\Orders\Events\OrderPlaced($order));
>>> exit

# Terminal 1 debería mostrar jobs siendo procesados
# Luego verificar:
>>> $order = App\Modules\Orders\Models\Order::latest()->first();
>>> $order->pdf_path;  # Debería tener ruta como: order_pdfs/YYYY/MM/order-{id}.pdf
```

### 5.8 Monitor Redis Queue Depth

Durante testing, monitorear jobs pendientes:

```bash
# En otra terminal
redis-cli MONITOR      # Ver todos los comandos (muy verbose)

# O más simple:
redis-cli DBSIZE       # Total keys en Redis
redis-cli LLEN queues:default  # Jobs pendientes en cola

# O repetir cada 5 segundos:
watch -n 5 'redis-cli LLEN queues:default'
```

### 5.9 Monitor Application Logs

```bash
# Real-time application logs
tail -f /var/www/portalDistribuidores-staging/storage/logs/laravel.log

# Format: [timestamp] INFO: Job GenerateOrderPdfJob procesado exitosamente
```

### 5.10 Test Multiple Orders (Load Test Light)

```bash
# Crear múltiples órdenes en loop
php artisan tinker
>>> for ($i = 0; $i < 5; $i++) {
  $order = App\Modules\Orders\Models\Order::factory()->create(['status' => 'pending']);
  event(new App\Modules\Orders\Events\OrderPlaced($order));
  echo("[{$i}] Order {$order->id} enqueued\n");
}
>>> exit

# Esperar ~10-15 segundos
# Verificar que todos los PDFs se generaron:
>>> psql -d portal_distribuidores_staging -c "SELECT id, status, pdf_path FROM orders ORDER BY created_at DESC LIMIT 5;"
```

### 5.11 Monitor During 1-2 Hours

Crear órdenes reales en staging (si hay acceso de distribuidores de test):

- Verificar que PDFs se generan rápidamente (~2-5 segundos vs antes ~20 segundos)
- Verificar que emails se envían (si MAIL_MAILER=smtp)
- Revisar `storage/logs/laravel.log` — ningún error
- Revisar `failed_jobs` — debería estar vacío

```bash
# Monitoreo de failed jobs
while true; do
  count=$(psql -d portal_distribuidores_staging -t -c "SELECT COUNT(*) FROM failed_jobs;")
  echo "Failed jobs: $count at $(date)"
  sleep 10
done

# Si hay failed jobs, inspeccionar:
psql -d portal_distribuidores_staging -c "SELECT id, queue, exception FROM failed_jobs ORDER BY failed_at DESC LIMIT 1 \gx"
```

### 5.12 Queue Restart Test

```bash
# Gracfully restart worker (completa job actual, después reinicia)
php artisan queue:restart

# Esperar 2 segundos
sleep 2

# Verificar que worker restarteó:
ps aux | grep "queue:work"
# Debería mostrar proceso nuevo con timestamp reciente

# El trabajo en process debería completarse sin pérdida
```

### 5.13 Rollback Test (si necesario)

Si algo falla, verificar rollback:

```bash
# Revert a database driver
sed -i 's/QUEUE_CONNECTION=redis/QUEUE_CONNECTION=database/' /var/www/portalDistribuidores-staging/.env

# Restart worker
php artisan queue:restart
sleep 2
systemctl restart laravel-queue-staging

# Verificar que funciona:
php artisan queue:work --once  # Procesa un job

# Status
systemctl status laravel-queue-staging
tail -f /var/log/laravel/queue-staging.log
```

### 5.14 Success Criteria

✅ Todos los tests pasan

✅ Órdenes se crean sin errores

✅ PDFs se generan en ~2-5 segundos (antes ~20s)

✅ Emails se envían correctamente

✅ `failed_jobs` vacío (o muy pocos sin tendencia creciente)

✅ Worker no consume CPU excesivo

✅ Redis memory < 200MB

✅ Logs limpios (sin errores recurrentes)

---

## 6. Fase 3: Production Cutover (2-4 horas)

### 6.1 Prerequisites

- Redis instalado y running en production
- Staging testing completado exitosamente
- Ventana de bajo tráfico conocida (por ej, 2AM-4AM)
- Backup de BD listo

### 6.2 Instalar Redis en Production

```bash
# Como root o con sudo
ssh root@pedidos.importcorporalmedical.com

apt update && apt install -y redis-server redis-tools

# Verificar
redis-cli ping
# Output: PONG

# Auto-start
systemctl enable redis-server
```

### 6.3 Deploy Código Actualizado

```bash
cd /var/www/portalDistribuidores

# Actualizar código
git pull origin master

# Instalar deps
composer install

# Assets
npm install
npm run build

# Cache
php artisan cache:clear
php artisan config:clear
```

### 6.4 Drain Production Database Jobs

Procesar jobs pendientes con old driver:

```bash
# timeout=0 significa sin timeout en jobs cortos
QUEUE_CONNECTION=database php artisan queue:work --tries=3 --timeout=0 --stop-when-empty

# Verificar
psql -d portal_distribuidores -c "SELECT COUNT(*) FROM jobs;"
# Output: 0
```

### 6.5 Switch to Redis Driver

```bash
# Editar .env
sudo nano /var/www/portalDistribuidores/.env

# Cambiar:
QUEUE_CONNECTION=redis
REDIS_QUEUE_DB=2        # Diferente de staging

# Guardar
```

### 6.6 Graceful Restart Workers

```bash
# Signal workers to finish current job then exit
php artisan queue:restart

# Wait for graceful shutdown
sleep 3

# Restart systemd service
systemctl restart laravel-queue-prod

# Verify
systemctl status laravel-queue-prod
ps aux | grep "queue:work"
```

### 6.7 Immediate Verification

```bash
# Check worker running
systemctl status laravel-queue-prod

# Tail logs
tail -f /var/log/laravel/queue-prod.log

# Redis check
redis-cli DBSIZE
redis-cli LLEN queues:default
```

### 6.8 Test with Real Order

1. **Acceder a producción como distribuidor**
2. **Crear una orden real** via UI
3. **Verificar:** PDF se genera y email se envía
4. **Revisar logs:** `tail -f storage/logs/laravel.log`

---

## 7. Post-Cutover Monitoring (24 hours)

### 7.1 Queue Health

```bash
# Monitor queue depth (debe estar ~0)
watch -n 10 'redis-cli LLEN queues:default'

# Monitor failed jobs (debe estar 0 o muy bajo)
watch -n 10 'psql -d portal_distribuidores -t -c "SELECT COUNT(*) FROM failed_jobs;"'
```

### 7.2 Worker Health

```bash
# Check worker status
systemctl status laravel-queue-prod

# Monitor restarts (systemd logs)
journalctl -u laravel-queue-prod -f

# If worker crashes, must auto-restart within 5 seconds
```

### 7.3 Redis Health

```bash
# Memory usage (must be < 1GB)
redis-cli INFO memory | grep used_memory_human

# Persistence (if enabled)
redis-cli LASTSAVE

# Clients connected
redis-cli INFO clients | grep connected_clients
```

### 7.4 Alert Rules

Create monitoring alerts if:

- Queue depth > 100 for > 5 minutes (worker can't keep up)
- Failed jobs growing (jobs hitting errors)
- Redis memory > 500MB (increase maxmemory setting)
- Worker process not running (systemd should restart, but verify)
- Orders taking > 30 seconds for PDF (investigate slowness)

### 7.5 Database Check

```bash
# Verify failed_jobs table for trend
psql -d portal_distribuidores -c "
  SELECT DATE(failed_at), COUNT(*)
  FROM failed_jobs
  WHERE failed_at > NOW() - INTERVAL '24 hours'
  GROUP BY DATE(failed_at)
  ORDER BY DATE(failed_at);
"

# Should show 0 or very few entries
```

---

## 8. Rollback Strategy

If issues occur within 24 hours:

### 8.1 Quick Rollback (5 minutes)

```bash
# Revert .env
sed -i 's/QUEUE_CONNECTION=redis/QUEUE_CONNECTION=database/' /var/www/portalDistribuidores/.env

# Restart worker (will read database driver again)
php artisan queue:restart
sleep 2
systemctl restart laravel-queue-prod

# Verify
systemctl status laravel-queue-prod
tail -f /var/log/laravel/queue-prod.log
```

### 8.2 Data Safety

- ✅ Jobs in Redis: ephemeral, no problem if lost (worker would re-enqueue on app restart)
- ✅ Failed jobs: stored in PostgreSQL, completely safe
- ✅ Database: untouched during migration

### 8.3 Post-Rollback

```bash
# Process any jobs that were in Redis when reverted
QUEUE_CONNECTION=database php artisan queue:work --tries=3 --timeout=0 --stop-when-empty

# Verify clean state
psql -d portal_distribuidores -c "SELECT COUNT(*) FROM jobs;"
redis-cli DBSIZE
```

---

## 9. Troubleshooting

### Worker Not Starting

```bash
# Check systemd unit
systemctl status laravel-queue-prod
journalctl -u laravel-queue-prod -n 50

# Manual start to see error
php artisan queue:work --queue=default

# Common issues:
#  - Config syntax error in .env
#  - Redis not running
#  - Wrong REDIS_QUEUE_HOST/PORT
#  - Insufficient permissions
```

### Jobs Not Processing

```bash
# Check queue depth
redis-cli LLEN queues:default

# Check worker logs
tail -f /var/log/laravel/queue-prod.log

# Try manual process
php artisan queue:work --tries=1 --stop-when-empty

# If "No jobs", but trying to create order, check:
#  - ORDER_NOTIFICATION_EMAIL_DISPATCH=queue in .env?
#  - OrderPlaced event fired?
#  - No exceptions in app logs?
```

### Redis Connection Errors

```bash
# Verify Redis running
redis-cli ping

# Check config
redis-cli CONFIG GET "maxmemory"
redis-cli CONFIG GET "appendonly"

# Check firewall (if Redis on different host)
nc -zv REDIS_QUEUE_HOST REDIS_QUEUE_PORT
```

### High Memory Usage

```bash
# Check current usage
redis-cli INFO memory | grep "used_memory_human"

# Set memory limit in production
redis-cli CONFIG SET maxmemory 2gb
redis-cli CONFIG SET maxmemory-policy allkeys-lru

# Make persistent (edit /etc/redis/redis.conf)
sudo nano /etc/redis/redis.conf
# Search for: maxmemory, maxmemory-policy
# Set: maxmemory 2gb
# Set: maxmemory-policy allkeys-lru
# Restart: systemctl restart redis-server
```

### Failed Jobs Piling Up

```bash
# Inspect failed job
psql -d portal_distribuidores -c "
  SELECT id, queue, exception
  FROM failed_jobs
  ORDER BY failed_at DESC
  LIMIT 1 \gx
"

# If same error repeating, fix root cause:
#  - Check exception text
#  - File path issue? → Check config/queue.php
#  - Mail sending? → Check MAIL_* config
#  - Model serialization? → Check job code

# Clear failed jobs (only if root cause fixed)
php artisan queue:clear
```

---

## 10. Performance Benchmarks

Expected improvements after Redis migration:

| Métrica | Database Driver | Redis Driver | Mejora |
|---------|---|---|---|
| Job latency | ~500-1000ms | ~50-100ms | ~10x |
| PDF generation time | ~20-30s | ~2-5s | ~5-10x |
| Queue throughput | ~10-20 jobs/sec | ~100-200 jobs/sec | ~10x |
| DB CPU usage | 15-25% (queue polls) | <5% (no polling) | Significativo |
| Memory footprint | ~500MB (DB overhead) | ~100-200MB (Redis) | Reducido |

---

## 11. Long-term Maintenance

### Backup Redis Data (Optional)

If using Redis for cache too, consider backups:

```bash
# Enable persistence in /etc/redis/redis.conf
appendonly yes
appendfsync everysec

# Or periodic snapshots
redis-cli BGSAVE

# Restore if needed
redis-cli SHUTDOWN
# restore dump.rdb backup
redis-server
```

### Monitoring Integration

Integrate Redis metrics with monitoring tool:

- Queue depth (alert if > 1000)
- Failed jobs count
- Redis memory usage
- Worker uptime

Example cron job:

```bash
#!/bin/bash
# /usr/local/bin/queue-health-check.sh

QUEUE_JOBS=$(redis-cli LLEN queues:default)
FAILED_JOBS=$(psql -d portal_distribuidores -t -c "SELECT COUNT(*) FROM failed_jobs WHERE failed_at > NOW() - INTERVAL '1 hour';")
REDIS_MEM=$(redis-cli INFO memory | grep "used_memory_human" | cut -d':' -f2)

if [ "$QUEUE_JOBS" -gt 1000 ]; then
  curl -X POST "https://monitoring.example.com/alert" \
    -d "severity=warning&msg=Queue depth high: $QUEUE_JOBS"
fi

if [ "$FAILED_JOBS" -gt 5 ]; then
  curl -X POST "https://monitoring.example.com/alert" \
    -d "severity=warning&msg=Failed jobs: $FAILED_JOBS"
fi

echo "$(date) | Queue: $QUEUE_JOBS | Failed: $FAILED_JOBS | Memory: $REDIS_MEM" >> /var/log/queue-health.log
```

Then add to crontab:
```bash
crontab -e
# */5 * * * * /usr/local/bin/queue-health-check.sh
```

---

## 12. References

- [Laravel Queue Documentation](https://laravel.com/docs/queues)
- [Redis Documentation](https://redis.io/docs/)
- [Predis Documentation](https://github.com/predis/predis)
- Project files: `config/queue.php`, `.env.example`

---

## Summary

✅ **Database → Redis migration is safe, well-tested, and improves performance significantly.**

- Fase 1 (Local): ~3 horas
- Fase 2 (Staging): ~1-2 días (incluye monitoring)
- Fase 3 (Production): ~2-4 horas (cutover + monitoring)

**Key success factors:**

1. Drain all database jobs before switching drivers
2. Test queue operation on staging for 24+ hours
3. Monitor worker, queue depth, and failed jobs after cutover
4. Keep rollback procedure ready for first 24 hours
5. Never disable workers in production

