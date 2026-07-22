<x-app-layout>
    @php
        $isEdit = $user->exists;
        $selectedRole = old('role', $preselectedRole ?? $user->role?->value ?? 'distributor');
        $selectedDistributorId = old('distributor_id', $preselectedDistributorId ?? $user->distributor_id);
    @endphp

    <x-slot name="header">
        <x-ui.page-header :title="$isEdit ? 'Editar cuenta de acceso' : 'Nueva cuenta de acceso'" subtitle="Gestiona credenciales, roles y la vinculación con empresas distribuidoras.">
            <x-slot name="meta">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="stat-pill">Modo: {{ $isEdit ? 'Edición' : 'Creación' }}</span>
                    @if($isEdit)
                        <span class="stat-pill">Pedidos: {{ number_format((int) ($user->orders_count ?? 0)) }}</span>
                        <x-admin.verification-badge :verified-at="$user->email_verified_at" />
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
          x-data="{ role: @js($selectedRole) }"
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
                <h2 class="card-title">Rol y vinculación</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="user-role">Rol *</label>
                        <x-ui.select id="user-role" name="role" required x-model="role">
                            @foreach($roles as $role)
                                <option value="{{ $role->value }}" @selected($selectedRole === $role->value)>{{ $role->value === 'admin' ? 'Administrador' : 'Distribuidor' }}</option>
                            @endforeach
                        </x-ui.select>
                        <p class="form-help" x-show="role === 'admin'" x-cloak>Las cuentas administrativas no se vinculan con empresas distribuidoras.</p>
                        <p class="form-help" x-show="role === 'distributor'" x-cloak>La cuenta debe estar vinculada a una empresa distribuidora.</p>
                        <x-input-error :messages="$errors->get('role')" />
                    </div>

                    <div x-show="role === 'distributor'" x-cloak>
                        <label class="form-label" for="user-distributor">Empresa distribuidora</label>
                        <x-ui.select id="user-distributor" name="distributor_id">
                            <option value="">Ninguna</option>
                            @foreach($distributors as $distributor)
                                <option value="{{ $distributor->id }}" @selected((string) $selectedDistributorId === (string) $distributor->id)>{{ $distributor->name }}</option>
                            @endforeach
                        </x-ui.select>
                        <p class="form-help">Obligatorio para rol Distribuidor.</p>
                        <x-input-error :messages="$errors->get('distributor_id')" />
                    </div>
                </div>
            </x-ui.card>

            @if($isEdit && $user->role?->value === 'distributor' && $user->distributor)
                <x-ui.card class="p-5">
                    <h2 class="card-title">Datos de registro del distribuidor</h2>
                    <p class="mt-1 text-sm text-slate-500">Información ingresada por el usuario al momento del registro. Solo lectura.</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <span class="form-label">Razón social</span>
                            <p class="mt-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-800">{{ $user->distributor->name ?: '—' }}</p>
                        </div>
                        <div>
                            <span class="form-label">NIT</span>
                            <p class="mt-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-800">{{ $user->distributor->nit ?: '—' }}</p>
                        </div>
                        <div>
                            <span class="form-label">Ciudad</span>
                            <p class="mt-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-800">{{ $user->distributor->city ?: '—' }}</p>
                        </div>
                        <div>
                            <span class="form-label">Dirección</span>
                            <p class="mt-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-800">{{ $user->distributor->address ?: '—' }}</p>
                        </div>
                        <div>
                            <span class="form-label">Teléfono</span>
                            <p class="mt-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-800">{{ $user->distributor->phone ?: '—' }}</p>
                        </div>
                        <div>
                            <span class="form-label">Correo de contacto</span>
                            <p class="mt-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-800">{{ $user->distributor->contact_email ?: '—' }}</p>
                        </div>
                        <div>
                            <span class="form-label">Nombre de contacto</span>
                            <p class="mt-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-800">{{ $user->distributor->contact_name ?: '—' }}</p>
                        </div>
                        <div>
                            <span class="form-label">Fecha de registro</span>
                            <p class="mt-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-800">{{ $user->created_at?->format('d/m/Y H:i') ?? '—' }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <span class="form-label">Correo verificado</span>
                            @if($user->email_verified_at)
                                <p class="mt-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-800">
                                    Sí — {{ $user->email_verified_at->format('d/m/Y H:i') }}
                                </p>
                            @else
                                <p class="mt-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-amber-600">No verificado aún</p>
                            @endif
                        </div>
                    </div>
                </x-ui.card>
            @endif

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
                        <span x-show="role === 'admin'" x-cloak><x-admin.role-badge role="admin" /></span>
                        <span x-show="role === 'distributor'" x-cloak><x-admin.role-badge role="distributor" /></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Empresa</span>
                        <span class="font-medium text-slate-900" x-text="role === 'admin' ? 'Sin empresa' : ({{ json_encode((bool) $selectedDistributorId) }} ? 'Vinculada' : 'Sin vínculo')"></span>
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
                    <li>Vincula la empresa correcta antes de habilitar cuentas comerciales.</li>
                    <li>El estado operativo de la empresa se gestiona desde Empresas distribuidoras.</li>
                </ul>
            </x-ui.card>
        </aside>
    </form>
</x-app-layout>
