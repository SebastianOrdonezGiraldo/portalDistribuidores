<x-app-layout>
    @php($statusValue = $distributor->status?->value)

    <x-slot name="header">
        <x-ui.page-header title="Datos de Empresa" subtitle="Información maestra de tu empresa. Se usa para autocompletar pedidos y como referencia comercial.">
            <x-slot name="meta">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="stat-pill">Estado: {{ $statusValue === 'active' ? 'Activo' : 'Suspendido' }}</span>
                    @if($distributor->nit)
                        <span class="stat-pill">NIT: {{ $distributor->nit }}</span>
                    @endif
                </div>
            </x-slot>
            <x-slot name="actions">
                <a href="{{ route('empresa.dashboard') }}" class="btn btn-secondary">Dashboard</a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <form method="POST" action="{{ route('empresa.profile.update') }}" class="grid gap-4 xl:grid-cols-[1.8fr_1fr]">
        @csrf
        @method('PUT')

        <div class="space-y-4">
            {{-- Datos principales --}}
            <x-ui.card class="p-5">
                <h2 class="card-title">Identificación Empresarial</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="form-label" for="company-name">Razón Social *</label>
                        <x-ui.input id="company-name" name="name" :value="old('name', $distributor->name)" required />
                        <x-input-error :messages="$errors->get('name')" />
                    </div>
                    <div>
                        <label class="form-label" for="company-nit">NIT / Identificación</label>
                        <x-ui.input id="company-nit" name="nit" :value="old('nit', $distributor->nit)" placeholder="900.000.000-1" />
                        <x-input-error :messages="$errors->get('nit')" />
                    </div>
                    <div>
                        <label class="form-label" for="company-city">Ciudad</label>
                        <x-ui.input id="company-city" name="city" :value="old('city', $distributor->city)" placeholder="Bogotá" />
                        <x-input-error :messages="$errors->get('city')" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="form-label" for="company-address">Dirección Principal</label>
                        <x-ui.input id="company-address" name="address" :value="old('address', $distributor->address)" placeholder="Calle 80 # 30-15" />
                        <x-input-error :messages="$errors->get('address')" />
                    </div>
                </div>
            </x-ui.card>

            {{-- Contacto --}}
            <x-ui.card class="p-5">
                <h2 class="card-title">Información de Contacto</h2>
                <p class="mt-1 text-sm text-slate-500">Datos del contacto principal de la empresa.</p>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="company-contact-name">Nombre del contacto</label>
                        <x-ui.input id="company-contact-name" name="contact_name" :value="old('contact_name', $distributor->contact_name)" placeholder="Juan Pérez" />
                        <x-input-error :messages="$errors->get('contact_name')" />
                    </div>
                    <div>
                        <label class="form-label" for="company-contact-email">Correo de contacto</label>
                        <x-ui.input id="company-contact-email" name="contact_email" type="email" :value="old('contact_email', $distributor->contact_email)" placeholder="contacto@empresa.com" />
                        <x-input-error :messages="$errors->get('contact_email')" />
                    </div>
                    <div>
                        <label class="form-label" for="company-phone">Teléfono</label>
                        <x-ui.input id="company-phone" name="phone" :value="old('phone', $distributor->phone)" placeholder="+57 300 000 0000" />
                        <x-input-error :messages="$errors->get('phone')" />
                    </div>
                </div>
            </x-ui.card>

            <div class="sticky bottom-3 z-20 rounded-2xl border border-slate-200 bg-white p-3 shadow-panel">
                <div class="flex flex-wrap items-center justify-end gap-2">
                    <a href="{{ route('empresa.dashboard') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary" data-loading-label="Guardando...">Guardar cambios</button>
                </div>
            </div>
        </div>

        <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
            <x-ui.card class="p-5">
                <h2 class="card-title">Resumen</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">Estado</dt>
                        <dd>
                            <x-ui.badge :variant="$statusValue === 'active' ? 'success' : 'neutral'">
                                {{ $statusValue === 'active' ? 'Activo' : 'Suspendido' }}
                            </x-ui.badge>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">ID interno</dt>
                        <dd class="font-medium text-slate-900">#{{ $distributor->id }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">Creado</dt>
                        <dd class="font-medium text-slate-900">{{ $distributor->created_at?->format('d/m/Y') }}</dd>
                    </div>
                </dl>
            </x-ui.card>

            <x-ui.card class="p-5">
                <h2 class="card-title">Nota importante</h2>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    <li>Los datos aquí guardados se usan como referencia, pero <strong>no modifican pedidos históricos</strong>.</li>
                    <li>Cada pedido registra un snapshot de los datos al momento de su creación.</li>
                    <li>El estado (activo/suspendido) solo puede ser modificado por un administrador global.</li>
                </ul>
            </x-ui.card>
        </aside>
    </form>
</x-app-layout>
