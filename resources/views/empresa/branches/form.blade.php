@php $isEdit = $branch->exists; @endphp
<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            :title="$isEdit ? 'Editar Sucursal' : 'Nueva Sucursal'"
            subtitle="Ingresa la información de la sucursal o punto de entrega."
        >
            <x-slot name="actions">
                <a href="{{ route('empresa.branches.index') }}" class="btn btn-secondary">Cancelar</a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <form
        method="POST"
        action="{{ $isEdit ? route('empresa.branches.update', $branch) : route('empresa.branches.store') }}"
        class="max-w-xl"
    >
        @csrf
        @if($isEdit) @method('PUT') @endif

        <x-ui.card class="p-5">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="form-label" for="branch-name">Nombre de la sucursal *</label>
                    <x-ui.input id="branch-name" name="name" :value="old('name', $branch->name)" required placeholder="Ej: Sede principal, Bodega norte" />
                    <x-input-error :messages="$errors->get('name')" />
                </div>
                <div class="sm:col-span-2">
                    <label class="form-label" for="branch-address">Dirección</label>
                    <x-ui.input id="branch-address" name="address" :value="old('address', $branch->address)" placeholder="Calle, número, barrio" />
                    <x-input-error :messages="$errors->get('address')" />
                </div>
                <div>
                    <label class="form-label" for="branch-city">Ciudad</label>
                    <x-ui.input id="branch-city" name="city" :value="old('city', $branch->city)" placeholder="Ciudad" />
                    <x-input-error :messages="$errors->get('city')" />
                </div>
                <div class="flex items-end pb-0.5">
                    <label class="flex cursor-pointer items-center gap-2">
                        <x-ui.checkbox name="is_default" value="1" :checked="old('is_default', $branch->is_default)" />
                        <span class="text-sm text-slate-700">Marcar como dirección predeterminada</span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <a href="{{ route('empresa.branches.index') }}" class="btn btn-secondary">Cancelar</a>
                <x-ui.button type="submit" variant="primary">
                    {{ $isEdit ? 'Guardar cambios' : 'Crear sucursal' }}
                </x-ui.button>
            </div>
        </x-ui.card>
    </form>
</x-app-layout>
