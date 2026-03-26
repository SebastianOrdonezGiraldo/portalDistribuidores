<x-app-layout>
    @php $isEdit = $user->exists; @endphp

    <x-slot name="header">
        <x-ui.page-header
            :title="$isEdit ? 'Editar usuario' : 'Nuevo usuario'"
            subtitle="Gestiona el acceso y el rol de este usuario dentro de tu empresa."
        >
            <x-slot name="meta">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="stat-pill">Modo: {{ $isEdit ? 'Edición' : 'Creación' }}</span>
                </div>
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('empresa.users.index') }}" class="btn btn-secondary">Volver al listado</a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <form method="POST"
          action="{{ $isEdit ? route('empresa.users.update', $user) : route('empresa.users.store') }}"
          class="grid gap-4 xl:grid-cols-[1.8fr_1fr]">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="space-y-4">
            <x-ui.card class="p-5">
                <h2 class="card-title">Datos de acceso</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="user-name">Nombre *</label>
                        <x-ui.input id="user-name" name="name" :value="old('name', $user->name)" required />
                        <x-input-error :messages="$errors->get('name')" />
                    </div>
                    <div>
                        <label class="form-label" for="user-email">Email *</label>
                        <x-ui.input id="user-email" name="email" type="email" :value="old('email', $user->email)" required />
                        <x-input-error :messages="$errors->get('email')" />
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card class="p-5">
                <h2 class="card-title">Rol en la empresa</h2>
                <p class="mt-1 text-sm text-slate-500">Define qué acciones puede realizar este usuario dentro del panel de empresa.</p>
                <div class="mt-4">
                    <label class="form-label" for="user-company-role">Rol *</label>
                    <x-ui.select id="user-company-role" name="company_role" required>
                        @foreach($companyRoles as $role)
                            <option value="{{ $role->value }}" @selected(old('company_role', $user->company_role?->value) === $role->value)>
                                {{ $role->label() }}
                            </option>
                        @endforeach
                    </x-ui.select>
                    <x-input-error :messages="$errors->get('company_role')" />

                    <div class="mt-3 space-y-2">
                        @foreach($companyRoles as $role)
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                                <span class="font-semibold text-slate-800">{{ $role->label() }}:</span>
                                @if($role->value === 'admin_empresa')
                                    Gestión completa: empresa, usuarios, historial y creación de pedidos.
                                @elseif($role->value === 'usuario_comercial')
                                    Puede crear pedidos y ver historial. No gestiona empresa ni usuarios.
                                @else
                                    Solo lectura: historial, detalle y descarga de PDFs.
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card class="p-5">
                <h2 class="card-title">Contraseña</h2>
                <div class="mt-4">
                    <label class="form-label" for="user-password">Contraseña {{ $isEdit ? '(opcional)' : '*' }}</label>
                    <x-ui.input id="user-password" name="password" type="password" :required="! $isEdit" />
                    <p class="form-help">
                        {{ $isEdit ? 'Déjala vacía para conservar la contraseña actual.' : 'Mínimo 8 caracteres.' }}
                    </p>
                    <x-input-error :messages="$errors->get('password')" />
                </div>
            </x-ui.card>

            <div class="sticky bottom-3 z-20 rounded-2xl border border-slate-200 bg-white p-3 shadow-panel">
                <div class="flex flex-wrap items-center justify-end gap-2">
                    <a href="{{ route('empresa.users.index') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary" data-loading-label="Guardando...">
                        {{ $isEdit ? 'Guardar cambios' : 'Crear usuario' }}
                    </button>
                </div>
            </div>
        </div>

        <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
            <x-ui.card class="p-5">
                <h2 class="card-title">Información</h2>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li>Este usuario quedará asociado a tu empresa automáticamente.</li>
                    <li>Podrá iniciar sesión con el email y contraseña indicados.</li>
                    <li>Puedes activar o desactivar el acceso desde el listado de usuarios.</li>
                    @if($isEdit)
                        <li class="font-semibold text-slate-800">Estado actual: {{ $user->is_active ? 'Activo' : 'Inactivo' }}</li>
                    @endif
                </ul>
            </x-ui.card>
        </aside>
    </form>
</x-app-layout>
