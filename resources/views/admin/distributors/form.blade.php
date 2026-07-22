<x-app-layout>
    @php
        $isEdit = $distributor->exists;
        $statusValue = old('status', $distributor->status?->value ?? 'active');
    @endphp

    <x-slot name="header">
        <x-ui.page-header :title="$isEdit ? 'Editar distribuidor' : 'Nuevo distribuidor'" subtitle="Configura el estado operativo del cliente y su perfil base en el portal.">
            <x-slot name="meta">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="stat-pill">Modo: {{ $isEdit ? 'Edición' : 'Creación' }}</span>
                    @if($isEdit)
                        <span class="stat-pill">Usuario: {{ number_format((int) ($distributor->user_count ?? 0)) }}</span>
                        <span class="stat-pill">Pedidos: {{ number_format((int) ($distributor->orders_count ?? 0)) }}</span>
                        <x-ui.tier-badge :tier="$distributor->tier" size="sm" />
                    @endif
                </div>
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('admin.distributors.index') }}" class="btn btn-secondary">Volver al listado</a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="grid gap-4 xl:grid-cols-[1.8fr_1fr]">
        <div class="space-y-4">
            <form method="POST"
                  action="{{ $isEdit ? route('admin.distributors.update', $distributor) : route('admin.distributors.store') }}"
                  data-loading-form
                  data-unsaved-guard>
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                <x-ui.card class="p-5">
                    <h2 class="card-title">Información general</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="form-label" for="distributor-name">Nombre *</label>
                            <x-ui.input id="distributor-name" name="name" :value="old('name', $distributor->name)" required />
                            <p class="form-help">Nombre comercial visible para usuarios y pedidos.</p>
                            <x-input-error :messages="$errors->get('name')" />
                        </div>

                        <div>
                            <label class="form-label" for="distributor-status">Estado</label>
                            <x-ui.select id="distributor-status" name="status">
                                @foreach($statusOptions as $statusKey => $statusLabel)
                                    <option value="{{ $statusKey }}" @selected($statusValue === $statusKey)>{{ $statusLabel }}</option>
                                @endforeach
                            </x-ui.select>
                            <p class="form-help">Solo los distribuidores en estado activo pueden operar en el portal.</p>
                            <x-input-error :messages="$errors->get('status')" />
                        </div>
                    </div>
                </x-ui.card>

                @unless($isEdit)
                    <x-ui.alert variant="info" :dismissible="false" class="mt-4">
                        El distribuidor será creado inicialmente como ICM Plata.
                    </x-ui.alert>
                @endunless

                <div class="sticky bottom-3 z-20 mt-4 rounded-2xl border border-slate-200 bg-white p-3 shadow-panel">
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <a href="{{ route('admin.distributors.index') }}" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" name="after_save" value="stay" class="btn btn-secondary" data-loading-label="Guardando...">Guardar y seguir</button>
                        <button type="submit" name="after_save" value="index" class="btn btn-primary" data-loading-label="Guardando...">Guardar y volver</button>
                        <button type="submit" name="after_save" value="new" class="btn btn-secondary" data-loading-label="Guardando...">Guardar y nuevo</button>
                    </div>
                </div>
            </form>

            @if($isEdit)
                <x-admin.distributors.tier-card :distributor="$distributor">
                    <x-admin.distributors.tier-change-form :distributor="$distributor" variant="card" selectable />
                    <x-input-error :messages="$errors->get('tier')" class="mt-2" />
                </x-admin.distributors.tier-card>
            @endif
        </div>

        <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
            <x-ui.card class="p-5">
                <h2 class="card-title">Resumen</h2>
                <div class="mt-3 space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Modo</span>
                        <span class="font-medium text-slate-900">{{ $isEdit ? 'Edición' : 'Creación' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Estado</span>
                        <x-ui.status-badge :status="$statusValue" />
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Nivel comercial</span>
                        <x-ui.tier-badge :tier="$isEdit ? $distributor->tier : 'plata'" size="sm" />
                    </div>
                    @if($isEdit)
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Usuario vinculado</span>
                            <span class="font-medium text-slate-900">{{ ($distributor->user_count ?? 0) ? 'Sí' : 'No' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Pedidos registrados</span>
                            <span class="font-medium text-slate-900">{{ number_format((int) ($distributor->orders_count ?? 0)) }}</span>
                        </div>
                    @else
                        <p class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                            El nivel inicial será ICM Plata. Podrás cambiarlo después desde la edición del distribuidor.
                        </p>
                    @endif
                </div>
            </x-ui.card>

            @if($isEdit)
                <x-ui.card class="p-5">
                    <h2 class="card-title">Estado operativo</h2>
                    <p class="mt-2 text-sm text-slate-600">Activa o suspende el acceso comercial del distribuidor en el portal.</p>
                    <form method="POST"
                          action="{{ route('admin.distributors.status', $distributor) }}"
                          class="mt-4"
                          data-confirm="{{ $distributor->status?->value === 'active' ? '¿Suspender este distribuidor?' : '¿Activar este distribuidor?' }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ $distributor->status?->value === 'active' ? 'suspended' : 'active' }}">
                        <button type="submit" class="btn {{ $distributor->status?->value === 'active' ? 'btn-secondary' : 'btn-primary' }} w-full justify-center">
                            {{ $distributor->status?->value === 'active' ? 'Suspender distribuidor' : 'Activar distribuidor' }}
                        </button>
                    </form>
                </x-ui.card>
            @endif

            <x-ui.card class="p-5">
                <h2 class="card-title">Recomendaciones</h2>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li>Usa nombres comerciales únicos para facilitar búsquedas.</li>
                    <li>Desactiva en lugar de eliminar cuando exista historial de pedidos.</li>
                    <li>Verifica usuarios vinculados antes de bloquear una cuenta.</li>
                    @if($isEdit)
                        <li>Los pedidos confirmados conservan el nivel comercial con el que fueron creados.</li>
                    @endif
                </ul>
            </x-ui.card>
        </aside>
    </div>
</x-app-layout>
