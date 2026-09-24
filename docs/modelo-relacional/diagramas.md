# Diagramas — Portal de Distribuidores

Diez diagramas del sistema, cada uno derivado del código y no de la memoria.
La línea *Fuente* de cada uno dice qué archivo define lo dibujado; si ese
archivo cambia, el diagrama queda obsoleto.

Complemento de [`README.md`](README.md) (modelo relacional) y
[`schema.sql`](schema.sql) (DDL completa).

## Índice

**Datos**
1. [Modelo entidad-relación completo](#1-modelo-entidad-relación-completo)
2. [ER — Catálogo](#2-er--catálogo)
3. [ER — Empresas, carrito y pedidos](#3-er--empresas-carrito-y-pedidos)
4. [ER — Inventario y ERP](#4-er--inventario-y-erp)

**Flujos de negocio**
5. [Estados del pedido](#5-estados-del-pedido)
6. [Estados del pago manual](#6-estados-del-pago-manual)
7. [Checkout a cotización enviada](#7-checkout-a-cotización-enviada)

**Documentos y almacenamiento**
8. [Subida de documento protegido](#8-subida-de-documento-protegido)
9. [Descarga con autorización y cupo](#9-descarga-con-autorización-y-cupo)
10. [Topología de discos y Cloudflare R2](#10-topología-de-discos-y-cloudflare-r2)

---

## 1. Modelo entidad-relación completo

Las 29 tablas de dominio y sus 46 llaves foráneas. Las 8 tablas de
infraestructura Laravel se omiten por no participar del dominio.

*Fuente: `database/migrations/`, consolidado en `schema.sql`.*

```mermaid
erDiagram
    distributors ||--o| users : "1:1 UNIQUE"
    distributors ||--o{ company_branches : sucursales
    distributors ||--o{ company_lists : listas
    distributors ||--o{ orders : coloca
    distributors ||--o{ document_downloads : cupo
    users ||--o| carts : "1:1 UNIQUE"
    users ||--o{ orders : crea
    users ||--o{ order_status_histories : actor
    users ||--o{ inventory_hold_events : actor
    users ||--o{ stock_movements : actor
    users ||--o{ commerce_pricing_rules : publica

    categories ||--o{ categories : parent_id
    categories ||--o{ category_synonyms : sinonimos
    categories ||--o{ products : clasifica

    product_attributes ||--o{ product_attribute_values : valores
    product_attributes ||--o{ products : variant_attribute_id
    products ||--o{ product_variants : variantes
    product_attribute_values ||--o{ product_variants : valor
    products ||--o{ product_photos : fotos
    products ||--o{ product_documents : documentos
    products ||--o{ product_videos : videos
    product_documents ||--o{ document_downloads : descargas

    carts ||--o{ cart_items : lineas
    products ||--o{ cart_items : referencia
    product_variants ||--o{ cart_items : referencia

    company_lists ||--o{ company_list_items : items
    products ||--o{ company_list_items : referencia
    product_variants ||--o{ company_list_items : referencia

    orders ||--o{ order_items : lineas
    orders ||--o{ order_status_histories : bitacora
    orders ||--o{ payment_upload_tokens : tokens
    orders ||--o{ inventory_holds : reserva
    orders ||--o{ inventory_hold_events : eventos
    orders ||--o{ stock_movements : movimientos
    commerce_pricing_rules ||--o{ orders : "regla vigente"
    products ||--o{ order_items : referencia
    product_variants ||--o{ order_items : referencia

    inventory_holds ||--o{ inventory_hold_events : eventos
    products ||--o{ inventory_holds : reserva
    product_variants ||--o{ inventory_holds : reserva
    products ||--o{ stock_movements : kardex
    product_variants ||--o{ stock_movements : kardex
    products ||--o| contapyme_inventory_mappings : "mapeo ERP"
    product_variants ||--o| contapyme_inventory_mappings : "mapeo ERP"
```

`catalog_banners`, `commerce_tier_advisors` y `contapyme_sync_runs` no tienen
llaves foráneas: son tablas de configuración y de bitácora sin padre.

---

## 2. ER — Catálogo

Producto, su árbol de categorías, el eje de variación y los medios asociados.

*Fuente: `app/Modules/Catalog/Models/`, `app/Modules/Categories/Models/`.*

```mermaid
erDiagram
    categories {
        bigint id PK
        bigint parent_id FK "SET NULL"
        varchar slug UK
        boolean is_active
    }
    products {
        bigint id PK
        bigint category_id FK "RESTRICT"
        bigint variant_attribute_id FK "SET NULL"
        varchar sku UK
        numeric price
        numeric stock
        numeric reserved_stock
        boolean is_vat_excluded
    }
    product_variants {
        bigint id PK
        bigint product_id FK "CASCADE"
        bigint product_attribute_value_id FK "RESTRICT"
        numeric price
        numeric stock
        numeric reserved_stock
    }
    product_documents {
        bigint id PK
        bigint product_id FK "CASCADE"
        varchar type "UNIQUE con product_id"
        varchar path
        varchar filename
    }

    categories ||--o{ categories : parent_id
    categories ||--o{ category_synonyms : sinonimos
    categories ||--o{ products : clasifica
    product_attributes ||--o{ product_attribute_values : valores
    product_attributes ||--o{ products : eje_de_variacion
    products ||--o{ product_variants : variantes
    product_attribute_values ||--o{ product_variants : valor
    products ||--o{ product_photos : fotos
    products ||--o{ product_documents : documentos
    products ||--o{ product_videos : videos
```

---

## 3. ER — Empresas, carrito y pedidos

El camino comercial completo, del distribuidor a la línea de pedido.

*Fuente: `app/Modules/Company/Models/`, `app/Modules/Orders/Models/`.*

```mermaid
erDiagram
    distributors {
        bigint id PK
        varchar nit
        varchar status
        varchar tier "oro o plata"
        bigint tier_changed_by_id FK "SET NULL"
    }
    users {
        bigint id PK
        bigint distributor_id FK "SET NULL, UNIQUE"
        varchar email UK
        varchar role
        boolean is_active
    }
    orders {
        bigint id PK
        bigint distributor_id FK "RESTRICT"
        bigint user_id FK "RESTRICT"
        bigint commerce_pricing_rule_id FK "RESTRICT"
        varchar oc_number UK
        varchar status
        varchar payment_status
        numeric total_amount
        varchar distributor_tier_snapshot
    }
    order_items {
        bigint id PK
        bigint order_id FK "CASCADE"
        bigint product_id FK "SET NULL"
        bigint product_variant_id FK "SET NULL"
        varchar product_name_snapshot
        varchar sku_snapshot
        numeric price_each
        numeric vat_rate_snapshot
    }
    commerce_pricing_rules {
        bigint id PK
        integer silver_markup_basis_points
        integer silver_rounding_multiple
        integer gold_pricing_threshold_amount
    }

    distributors ||--o| users : "1:1"
    distributors ||--o{ company_branches : sucursales
    distributors ||--o{ company_lists : listas
    distributors ||--o{ orders : coloca
    company_lists ||--o{ company_list_items : items
    users ||--o| carts : "1:1"
    users ||--o{ orders : crea
    carts ||--o{ cart_items : lineas
    orders ||--o{ order_items : lineas
    orders ||--o{ order_status_histories : bitacora
    orders ||--o{ payment_upload_tokens : tokens
    commerce_pricing_rules ||--o{ orders : "regla vigente"
```

---

## 4. ER — Inventario y ERP

La doble contabilidad: `inventory_holds` audita, `reserved_stock` proyecta.

*Fuente: `app/Modules/Inventory/Models/`, `app/Modules/Orders/Services/OrderInventoryService.php`.*

```mermaid
erDiagram
    inventory_holds {
        bigint id PK
        bigint order_id FK "CASCADE"
        bigint product_id FK "RESTRICT"
        bigint product_variant_id FK "RESTRICT"
        varchar inventory_key "UNIQUE con order_id"
        numeric quantity
        varchar status "active o released"
        varchar release_reason
    }
    inventory_hold_events {
        bigint id PK
        bigint inventory_hold_id FK "CASCADE"
        bigint order_id FK "CASCADE"
        bigint user_id FK "SET NULL"
        varchar action
        numeric previous_quantity
        numeric new_quantity
    }
    stock_movements {
        bigint id PK
        bigint product_id FK "SET NULL"
        bigint order_id FK "SET NULL"
        varchar source
        numeric previous_stock
        numeric new_stock
        numeric delta
    }
    contapyme_inventory_mappings {
        bigint id PK
        bigint product_id FK "CASCADE, UNIQUE"
        bigint product_variant_id FK "CASCADE, UNIQUE"
        varchar irecurso UK
        varchar status
    }
    contapyme_sync_runs {
        uuid id PK
        varchar origin
        varchar status
        integer processed
        integer failed
        json diagnostics
    }

    orders ||--o{ inventory_holds : reserva
    inventory_holds ||--o{ inventory_hold_events : eventos
    orders ||--o{ inventory_hold_events : eventos
    products ||--o{ inventory_holds : bloquea
    product_variants ||--o{ inventory_holds : bloquea
    products ||--o{ stock_movements : kardex
    products ||--o| contapyme_inventory_mappings : mapeo
    product_variants ||--o| contapyme_inventory_mappings : mapeo
```

---

## 5. Estados del pedido

Las transiciones son exactamente las de `OrderStatus::nextAllowedStatuses()`.
Ningún camino fuera de este grafo es aceptado por
`OrderStatusTransitionService`, que además bloquea con `lockForUpdate` y
escribe una fila en `order_status_histories` por cada salto.

*Fuente: `app/Modules/Shared/Enums/OrderStatus.php`, `app/Modules/Orders/Services/OrderStatusTransitionService.php`.*

```mermaid
stateDiagram-v2
    direction LR
    [*] --> PendingApproval : requiere aprobacion interna
    [*] --> Submitted : checkout directo

    PendingApproval --> Submitted : aprobar
    PendingApproval --> Rejected : rechazar
    PendingApproval --> Cancelled

    Rejected --> PendingApproval : reenviar corregido
    Rejected --> Cancelled

    Submitted --> Sold : exige nota
    Submitted --> Cancelled

    Sold --> Dispatched : exige nota y pago OK
    Sold --> Cancelled

    Dispatched --> Delivered
    Dispatched --> Sent
    Dispatched --> Cancelled

    Sent --> Delivered
    Sent --> Cancelled

    Delivered --> [*]
    Cancelled --> [*]

    note right of Submitted
        Unico estado que consume inventario.
        Al entrar crea el HOLD, al salir lo libera.
    end note

    note right of Sent
        Estado legado, conservado
        para datos historicos.
    end note
```

`Draft` existe como valor por defecto de la columna pero no tiene transiciones
salidas definidas; los pedidos nacen en `PendingApproval` o `Submitted`.

---

## 6. Estados del pago manual

Aplica solo cuando el checkout se hizo con intención de pago. Los pedidos sin
pago nacen en `NotApplicable` y ahí se quedan.

*Fuente: `app/Modules/Shared/Enums/PaymentStatus.php`, `app/Modules/Orders/Services/Payment/`.*

```mermaid
stateDiagram-v2
    direction LR
    [*] --> NotApplicable : cotizacion sin pago
    [*] --> PendingUpload : checkout con metodo de pago

    PendingUpload --> Confirming : distribuidor sube comprobante
    Rejected --> Confirming : sube comprobante corregido

    Confirming --> Validated : admin valida
    Confirming --> Rejected : admin rechaza

    PendingUpload --> Expired : vence la reserva
    Rejected --> Expired : vence la reserva

    Validated --> [*]
    Expired --> [*]
    NotApplicable --> [*]

    note right of Expired
        Job payments:expire-pending
        cada 5 minutos.
        TTL 45 min configurable.
    end note

    note left of Validated
        Solo NotApplicable y Validated
        permiten despachar el pedido.
    end note
```

---

## 7. Checkout a cotización enviada

El camino completo desde el POST hasta el correo, incluyendo el encadenamiento
de jobs que impide enviar el correo sin PDF.

*Fuente: `app/Modules/Orders/Actions/CreateOrderAction.php`, `app/Modules/Orders/Listeners/GenerateOrderPdfListener.php`.*

```mermaid
sequenceDiagram
    autonumber
    actor D as Distribuidor
    participant OC as OrderController
    participant CA as CreateOrderAction
    participant PC as OrderPricingCalculator
    participant IS as OrderInventoryService
    participant DB as PostgreSQL
    participant Q as Cola sobre PostgreSQL
    participant R2 as R2 disco privado
    participant SMTP as SMTP

    D->>OC: POST /orders
    OC->>CA: execute(user, CreateOrderData)
    CA->>PC: calculate(tier, items)
    PC-->>CA: candidatos Oro y Plata, umbral, minimo
    alt no alcanza el pedido minimo del nivel
        CA-->>D: DomainException con el faltante exacto
    end
    CA->>DB: BEGIN
    CA->>DB: insert orders con snapshots de pricing, nivel y asesor
    CA->>DB: insert order_items con snapshots de nombre, SKU, IVA
    CA->>DB: asigna oc_number definitivo
    opt status = Submitted
        CA->>IS: holdForOrder
        IS->>DB: insert inventory_holds y suma reserved_stock
    end
    CA->>DB: COMMIT
    CA-)Q: evento OrderPlaced encadena dos jobs
    Q->>Q: GenerateOrderPdfJob con 3 reintentos
    Q->>R2: guarda el PDF de cotizacion
    Q->>Q: SendOrderNotificationEmailJob con 5 reintentos
    Q->>SMTP: correo con el PDF adjunto
    Note over Q: Si el PDF agota reintentos<br/>la cadena se detiene<br/>y el correo no sale
```

---

## 8. Subida de documento protegido

Mismo camino para ficha técnica, INVIMA, manual, guía rápida y calibración.

*Fuente: `app/Modules/Catalog/Actions/AttachProtectedProductDocumentAction.php`, `app/Modules/Catalog/Security/SafeUploadValidator.php`.*

```mermaid
sequenceDiagram
    autonumber
    actor A as Admin
    participant PA as ProductAdminController
    participant AC as AttachProtectedProductDocumentAction
    participant SV as SafeUploadValidator
    participant DK as Disco private
    participant DB as PostgreSQL

    A->>PA: POST producto con archivo PDF
    PA->>AC: execute(product, file, DocumentType)
    AC->>SV: assertSafePdf
    SV->>SV: MIME debe ser application/pdf
    SV->>SV: primeros 5 bytes deben ser %PDF-
    alt firma o MIME invalidos
        SV-->>A: ValidationException
    end
    AC->>DK: store en products/documents
    DK-->>AC: clave aleatoria de 40 caracteres
    AC->>SV: sanitizeOriginalFilename
    SV-->>AC: nombre ASCII de hasta 80 caracteres
    AC->>DB: BEGIN
    AC->>DB: upsert por UNIQUE product_id + type
    AC->>DB: COMMIT
    alt la transaccion falla
        AC->>DK: borrado compensatorio del objeto nuevo
    end
    opt reemplazo de documento anterior
        AC->>DK: borra la clave anterior en private y en public
    end
```

---

## 9. Descarga con autorización y cupo

El archivo nunca se expone con una URL de R2. Se transmite por PHP después de
tres verificaciones independientes.

*Fuente: `app/Modules/Documents/Http/Controllers/TechSheetDownloadController.php`, `app/Modules/Documents/Services/TechSheetDownloadService.php`.*

```mermaid
sequenceDiagram
    autonumber
    actor U as Usuario
    participant RT as Ruta tipada
    participant TC as TechSheetDownloadController
    participant PO as ProductDocumentPolicy
    participant SV as TechSheetDownloadService
    participant DB as PostgreSQL
    participant DK as Disco private en R2

    U->>RT: GET /documents/invima/25
    RT->>TC: throttle api-endpoints y suspicious_automation
    TC->>TC: el type de la ruta debe igualar documento.type
    alt no coinciden
        TC-->>U: 404
    end
    TC->>PO: authorize download
    PO->>PO: tipo protegido y producto activo
    PO->>PO: admin, o distribuidor con distributor_id
    alt no autorizado
        PO-->>U: 403
    end
    opt tipo tech_sheet y usuario distribuidor
        TC->>SV: canDownload
        SV->>DB: cuenta document_downloads del mes en America/Bogota
        alt cupo agotado
            SV-->>U: vuelve con el remanente y el limite
        end
        TC->>SV: registerDownload
        SV->>DB: insert document_downloads
    end
    TC->>DK: download(path, filename)
    alt la clave no existe en private
        TC->>DK: migra desde el disco public legado
    end
    DK-->>TC: stream del objeto
    TC-->>U: StreamedResponse con el nombre original
```

---

## 10. Topología de discos y Cloudflare R2

Dos discos lógicos, dos buckets, dos credenciales y dos estrategias de entrega
distintas.

*Fuente: `config/filesystems.php`, `app/Modules/Shared/Support/PublicMediaUrl.php`.*

```mermaid
flowchart LR
    B["Navegador"]

    subgraph portal["Portal Laravel"]
        direction TB
        PMU["PublicMediaUrl<br/>fromPublicDisk"]
        TDC["TechSheetDownloadController<br/>policy y cupo"]
        OPG["OrderPdfGenerator"]
    end

    subgraph r2["Cloudflare R2"]
        direction TB
        PUB[("AWS_BUCKET<br/>visibility public<br/>fotos y banners")]
        PRI[("PRIVATE_BUCKET<br/>visibility private<br/>documentos y PDFs")]
    end

    B -->|"render de la pagina"| PMU
    PMU -->|"URL firmada, TTL 20 min"| B
    B ==>|"descarga directa del navegador"| PUB

    B -->|"GET /documents/tipo/id"| TDC
    TDC -->|"lectura server-side"| PRI
    TDC -->|"StreamedResponse"| B

    OPG -->|"guarda cotizacion"| PRI
```

La diferencia importante: la flecha gruesa hacia `AWS_BUCKET` es tráfico que
sale del navegador directo a Cloudflare. Hacia `PRIVATE_BUCKET` no hay ninguna
flecha desde el navegador — todo pasa por PHP.

### Resolución del driver por entorno

```mermaid
flowchart TD
    S["config/filesystems.php se evalua al cargar"] --> Q1{"PUBLIC_DISK_DRIVER"}
    Q1 -->|"s3"| R2D["disco public = R2"]
    Q1 -->|"local"| LOC["disco public = storage/app/public"]
    Q1 -->|"vacio"| Q2{"APP_ENV es production<br/>y AWS_BUCKET esta definido"}
    Q2 -->|"si"| R2D
    Q2 -->|"no"| LOC

    S --> Q3{"PRIVATE_DISK_DRIVER"}
    Q3 -->|"s3"| R2P["disco private = R2"]
    Q3 -->|"local"| LOCP["disco private = storage/app/private"]
    Q3 -->|"vacio"| Q4{"APP_ENV es production<br/>y PRIVATE_BUCKET esta definido"}
    Q4 -->|"si"| R2P
    Q4 -->|"no"| LOCP
```

Los tres ajustes propios de R2, en ambos discos: `region = auto`,
`use_path_style_endpoint = true` y
`endpoint = https://ACCOUNT_ID.r2.cloudflarestorage.com`.
