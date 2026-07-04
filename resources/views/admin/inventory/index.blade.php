@php
    $configOk = config('services.inventree.base_url') && config('services.inventree.api_token');
@endphp

<x-app-layout>
    <x-slot name="header">
        <section class="admin-exec-hero">
            <div class="admin-exec-hero-main">
                <p class="admin-exec-hero-eyebrow">Integración</p>
                <h1 class="admin-exec-hero-title">InvenTree Sync</h1>
                <p class="admin-exec-hero-subtitle">Sincroniza precios y stock de productos simples desde InvenTree API.</p>
                <div class="admin-exec-hero-meta">
                    <span class="stat-pill">Endpoint: {{ config('services.inventree.base_url') ?: 'No configurado' }}</span>
                    <span class="stat-pill">Estado: {{ $configOk ? ($connectionStatus['success'] ? 'Conectado' : 'Error') : 'Sin configurar' }}</span>
                    @if($configOk && isset($connectionStatus['total_parts']))
                        <span class="stat-pill">Partes detectadas: {{ $connectionStatus['total_parts'] ?? 'N/D' }}</span>
                    @endif
                </div>
            </div>
            <div class="admin-exec-hero-actions">
                <a href="{{ route('admin.inventory.export') }}" class="btn btn-secondary">Descargar CSV InvenTree</a>
                <form action="{{ route('admin.inventory.test') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="btn btn-secondary">Probar conexión</button>
                </form>
            </div>
        </section>
    </x-slot>

    @if(session('success'))
        <x-ui.alert variant="success" title="Operación exitosa" class="mb-4">
            <p>{{ session('success') }}</p>
        </x-ui.alert>
    @endif

    @if(session('error'))
        <x-ui.alert variant="danger" title="Error" class="mb-4">
            <p>{{ session('error') }}</p>
        </x-ui.alert>
    @endif

    @if($syncResults = session('syncResults'))
        <x-ui.card class="mb-6 p-5">
            <h2 class="card-title">Resultados de la última sincronización</h2>
            <p class="mt-1 text-xs text-slate-500">
            Completada en {{ $syncResults['duration_ms'] ?? 0 }} ms.
            @if(isset($syncResults['started_at']))
                Iniciada: {{ $syncResults['started_at'] }}
            @endif
            </p>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-slate-200 p-4">
                    <h3 class="text-sm font-semibold text-slate-900">Precios</h3>
                    <dl class="mt-2 space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">Total en InvenTree:</dt><dd class="font-medium">{{ $syncResults['prices']['total'] ?? 0 }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Coincidencias:</dt><dd class="font-medium">{{ $syncResults['prices']['matched'] ?? 0 }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Precios actualizados:</dt><dd class="font-medium text-amber-600">{{ $syncResults['prices']['updated_price'] ?? 0 }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Variantes omitidas:</dt><dd class="font-medium text-slate-400">{{ $syncResults['prices']['skipped_variants'] ?? 0 }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Sin mapeo en portal:</dt><dd class="font-medium text-slate-400">{{ $syncResults['prices']['unmatched'] ?? 0 }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Errores:</dt><dd class="font-medium text-red-600">{{ $syncResults['prices']['errors'] ?? 0 }}</dd></div>
                    </dl>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <h3 class="text-sm font-semibold text-slate-900">Stock</h3>
                    <dl class="mt-2 space-y-1 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">Total en InvenTree:</dt><dd class="font-medium">{{ $syncResults['stock']['total'] ?? 0 }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Coincidencias:</dt><dd class="font-medium">{{ $syncResults['stock']['matched'] ?? 0 }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Actualizados:</dt><dd class="font-medium text-amber-600">{{ $syncResults['stock']['updated'] ?? 0 }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Variantes omitidas:</dt><dd class="font-medium text-slate-400">{{ $syncResults['stock']['skipped_variants'] ?? 0 }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Sin mapeo en portal:</dt><dd class="font-medium text-slate-400">{{ $syncResults['stock']['unmatched'] ?? 0 }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Errores:</dt><dd class="font-medium text-red-600">{{ $syncResults['stock']['errors'] ?? 0 }}</dd></div>
                    </dl>
                </div>
            </div>
        </x-ui.card>
    @endif

    <div class="grid gap-4 md:grid-cols-2">
        <x-ui.card class="p-5">
            <h2 class="card-title">Sincronizar precios</h2>
            <p class="mt-1 text-sm text-slate-500">Actualiza el precio de productos simples existentes usando pricing_min de InvenTree y el SKU como identificador.</p>
            <form action="{{ route('admin.inventory.sync') }}" method="POST" class="mt-4">
                @csrf
                <input type="hidden" name="type" value="prices">
                <button type="submit" class="btn btn-primary" {{ $configOk ? '' : 'disabled' }}>
                    Sincronizar precios
                </button>
            </form>
        </x-ui.card>

        <x-ui.card class="p-5">
            <h2 class="card-title">Sincronizar stock</h2>
            <p class="mt-1 text-sm text-slate-500">Actualiza los niveles de stock de todos los productos desde InvenTree. Busca por SKU en el portal.</p>
            <form action="{{ route('admin.inventory.sync') }}" method="POST" class="mt-4">
                @csrf
                <input type="hidden" name="type" value="stock">
                <button type="submit" class="btn btn-primary" {{ $configOk ? '' : 'disabled' }}>
                    Sincronizar stock
                </button>
            </form>
        </x-ui.card>

        <x-ui.card class="p-5 md:col-span-2">
            <h2 class="card-title">Sincronización completa</h2>
            <p class="mt-1 text-sm text-slate-500">Ejecuta precios y stock en una sola operación. Las variantes no se sincronizan en esta versión.</p>
            <form action="{{ route('admin.inventory.sync') }}" method="POST" class="mt-4">
                @csrf
                <input type="hidden" name="type" value="all">
                <button type="submit" class="btn btn-primary" {{ $configOk ? '' : 'disabled' }}>
                    Sincronizar todo
                </button>
            </form>
        </x-ui.card>
    </div>
</x-app-layout>
