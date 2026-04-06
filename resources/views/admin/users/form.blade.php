<x-app-layout>
    @php
        $isEdit = $user->exists;
        $selectedRole = old('role', $user->role?->value ?? 'distributor');
        $selectedDistributorId = old('distributor_id', $user->distributor_id);
    @endphp

    <x-slot name="header">
        <x-ui.page-header :title="$isEdit ? 'Editar usuario' : 'Nuevo usuario'" subtitle="Gestiona accesos administrativos y cuentas de distribuidores.">
            <x-slot name="meta">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="stat-pill">Modo: {{ $isEdit ? 'Edición' : 'Creación' }}</span>
                    @if($isEdit)
                        <span class="stat-pill">Pedidos: {{ number_format((int) ($user->orders_count ?? 0)) }}</span>
                        <span class="stat-pill">Verificado: {{ $user->email_verified_at ? 'Sí' : 'No' }}</span>
                    @endif
                </div>
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Volver al listado</a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <form method="POST"
          action="{{ $isEdit ? route('admin.users.update', $user) : route('admin.users.store') }}"
          data-loading-form
          data-unsaved-guard
          class="grid gap-4 xl:grid-cols-[1.8fr_1fr]">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="space-y-4">
            <x-ui.card class="p-5">
                <h2 class="card-title">Identidad de acceso</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="user-name">Nombre *</label>
                        <x-ui.input id="user-name" name="name" :value="old('name', $user->name)" required />
                        <x-input-error :messages="$errors->get('name')" />
                    </div>

                    <div>
                        <label class="form-label" for="user-email">Correo electrónico *</label>
                        <x-ui.input id="user-email" name="email" type="email" :value="old('email', $user->email)" required />
                        <x-input-error :messages="$errors->get('email')" />
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card class="p-5">
                <h2 class="card-title">Rol y alcance</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="user-role">Rol *</label>
                        <x-ui.select id="user-role" name="role" required>
                            @foreach($roles as $role)
                                <option value="{{ $role->value }}" @selected($selectedRole === $role->value)>{{ $role->value === 'admin' ? 'Administrador' : 'Distribuidor' }}</option>
                            @endforeach
                        </x-ui.select>
                        <p class="form-help">Si seleccionas Administrador, el distribuidor se ignora automáticamente.</p>
                        <x-input-error :messages="$errors->get('role')" />
                    </div>

                    <div>
                        <label class="form-label" for="user-distributor">Distribuidor</label>
                        <x-ui.select id="user-distributor" name="distributor_id">
                            <option value="">Ninguno</option>
                            @foreach($distributors as $distributor)
                                <option value="{{ $distributor->id }}" @selected((string) $selectedDistributorId === (string) $distributor->id)>{{ $distributor->name }}</option>
                            @endforeach
                        </x-ui.select>
                        <p class="form-help">Obligatorio para rol Distribuidor.</p>
                        <x-input-error :messages="$errors->get('distributor_id')" />
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card class="p-5">
                <h2 class="card-title">Seguridad</h2>
                <div class="mt-4">
                    <label class="form-label" for="user-password">Contraseña {{ $isEdit ? '(opcional)' : '*' }}</label>
                    <x-ui.input id="user-password" name="password" type="password" :required="! $isEdit" />
                    <p class="form-help">{{ $isEdit ? 'Déjala vacía para conservar la contraseña actual.' : 'Mínimo 8 caracteres.' }}</p>
                    <x-input-error :messages="$errors->get('password')" />
                </div>
            </x-ui.card>

            <div class="sticky bottom-3 z-20 rounded-2xl border border-slate-200 bg-white p-3 shadow-panel">
                <div class="flex flex-wrap items-center justify-end gap-2">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" name="after_save" value="stay" class="btn btn-secondary" data-loading-label="Guardando...">Guardar y seguir</button>
                    <button type="submit" name="after_save" value="index" class="btn btn-primary" data-loading-label="Guardando...">Guardar y volver</button>
                    <button type="submit" name="after_save" value="new" class="btn btn-secondary" data-loading-label="Guardando...">Guardar y nuevo</button>
                </div>
            </div>
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
                        <span class="text-slate-500">Rol</span>
                        <x-ui.badge :variant="$selectedRole === 'admin' ? 'brand' : 'info'">{{ $selectedRole === 'admin' ? 'Administrador' : 'Distribuidor' }}</x-ui.badge>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Distribuidor</span>
                        <span class="font-medium text-slate-900">{{ $selectedDistributorId ? 'Vinculado' : 'Sin vínculo' }}</span>
                    </div>
                    @if($isEdit)
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500">Pedidos</span>
                            <span class="font-medium text-slate-900">{{ number_format((int) ($user->orders_count ?? 0)) }}</span>
                        </div>
                    @endif
                </div>
            </x-ui.card>

            <x-ui.card class="p-5">
                <h2 class="card-title">Recomendaciones</h2>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li>Usa cuentas admin solo para personal interno.</li>
                    <li>Asigna distribuidor correcto antes de habilitar usuarios comerciales.</li>
                    <li>No compartas usuarios entre empresas distintas.</li>
                </ul>
            </x-ui.card>
        </aside>
    </form>
</x-app-layout>
