# Documentacion HTTP / OpenAPI

El portal usa Scribe para generar documentacion HTTP de rutas operativas. No es una API publica JSON: muchas rutas responden HTML Blade, redirects, descargas de archivos o JSON puntual para interacciones AJAX.

## Generar documentacion

```bash
composer run docs:generate
```

Para diagnostico detallado:

```bash
composer run docs:generate:verbose
```

Scribe genera la vista, assets y artefactos en rutas ignoradas por Git:

- `.scribe/`
- `resources/views/scribe/`
- `public/vendor/scribe/`
- `storage/app/scribe/`
- `storage/app/private/scribe/`

## Acceso

Con `SCRIBE_ENABLED=true`, la documentacion queda en:

- `/docs`
- `/docs.openapi`
- `/docs.postman`

Las rutas usan middleware `web`, `auth`, `verified` y `role:admin`; un distribuidor autenticado no debe poder acceder.

## Entornos

- Local: `SCRIBE_ENABLED=true`
- Staging: `SCRIBE_ENABLED=true`
- Produccion: `SCRIBE_ENABLED=false`

En produccion no debe exponerse `/docs`, porque incluye rutas administrativas y contratos operativos internos.

## Alcance inicial

La primera version cubre autenticacion, catalogo, carrito, pedidos, documentos, area de empresa y administracion. Se excluyen redirects, pantallas `create/edit` puramente visuales y rutas deshabilitadas como `empresa/usuarios*`.
