# Views And Blade Components

This folder contains the Blade presentation layer for the Laravel modular monolith.
Use this guide as the first stop when opening a view: it documents what data a view
expects and which layer owns the business logic.

## Comment Conventions

Complex views should start with a short Blade comment:

```blade
{{--
View contract:
- Source: App\Modules\...\Http\Controllers\...
- Expects: $variable names and loaded relations used by the view.
- Owns: presentation state, links, layout, and UI-only derived labels.
- Notes: behavior owned elsewhere, such as policies, actions, jobs, or services.
--}}
```

Reusable components should document their props and slots:

```blade
{{--
Component contract:
- Props: public inputs accepted by @props.
- Slots: named slots or default slot content.
- Use for: where this component is safe to reuse.
--}}
```

Comments should explain contracts, not restate the HTML. Keep domain rules in
controllers, form requests, actions, policies, services, jobs, enums, or DTOs.

## Global Layout Data

`resources/views/layouts/app.blade.php` is rendered by `App\View\Components\AppLayout`
and receives global data from `App\Providers\AppServiceProvider` view composers.
Important globals include:

- `cartCount` and `navCartCount` for cart badges and navigation state.
- `footerTopCategories` and `headerQuickCategories` for category navigation.
- `pendingApprovalCount` for legacy company approval indicators. Company approvals
  exist as historical/inactive surface and are not part of the current main order flow.

`resources/views/layouts/guest.blade.php` is rendered by `App\View\Components\GuestLayout`
and is used by unauthenticated/auth screens.

## Main View Map

- Catalog flow: `catalog/index.blade.php`, `catalog/_products-partial.blade.php`,
  `catalog/_product-list-controls.blade.php`, and `product/show.blade.php`.
- Cart and checkout flow: `cart/index.blade.php`, `orders/checkout.blade.php`,
  `orders/submitted.blade.php`, `orders/show.blade.php`, and `orders/pdf.blade.php`.
- Admin operations: `admin/products/*` and `admin/orders/*`.
- Company self-service: `empresa/dashboard.blade.php`, `empresa/orders/*`, and
  `empresa/lists/index.blade.php`.
- Shared UI primitives: `components/ui/*`.
- Catalog cards and grids: `components/catalog/*`.

Auth, profile, errors, and email views are intentionally left with minimal inline
documentation unless they depend on non-obvious data.
