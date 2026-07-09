# Categories Module

## Proposito

Gestiona la taxonomia del catalogo: categorias, jerarquia, sinonimos,
descendientes y breadcrumbs.

## Responsabilidades

- Crear y actualizar categorias desde Admin.
- Calcular arboles de categoria para navegacion.
- Resolver descendientes para filtros de catalogo.
- Construir breadcrumbs para paginas de producto.
- Mantener sinonimos usados por busqueda.

## No debe contener

- Reglas de producto o inventario.
- Ranking completo de busqueda; eso vive en Catalog.
- Pantallas administrativas fuera de categorias.

## Puntos de entrada

- `routes/admin.php` para administracion de categorias.
- `Actions/CreateCategoryAction.php`
- `Actions/UpdateCategoryAction.php`
- `Queries/CategoryTreeQuery.php`
- `Queries/CategoryDescendantsQuery.php`
- `Queries/CategoryBreadcrumbsQuery.php`

## Colabora con

- `Catalog`: filtros, busqueda, detalle de producto y relacion producto-categoria.
- `Admin`: CRUD de categorias.
- `Shared`: excepciones y soporte comun cuando aplique.

## Archivos clave para empezar

- `Models/Category.php`
- `Models/CategorySynonym.php`
- `Queries/CategoryTreeQuery.php`
- `Queries/CategoryDescendantsQuery.php`
- `Actions/CreateCategoryAction.php`
- `Actions/UpdateCategoryAction.php`

## Notas actuales

- El modulo es una dependencia de lectura frecuente para Catalog.
- Evita hacer consultas ad hoc de jerarquia en controladores; usa las Queries.
