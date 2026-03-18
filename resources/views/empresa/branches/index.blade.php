<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Sucursales y Direcciones" subtitle="Gestiona las direcciones de entrega de tu empresa. Al cotizar podrás seleccionar una.">
            <x-slot name="actions">
                <a href="{{ route('empresa.branches.create') }}" class="btn btn-primary">Nueva sucursal</a>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if($branches->isEmpty())
        <x-ui.empty-state
            title="Sin sucursales registradas"
            description="Crea una sucursal para agilizar el llenado de dirección en tus pedidos."
        >
            <a href="{{ route('empresa.branches.create') }}" class="btn btn-primary mt-4">Crear primera sucursal</a>
        </x-ui.empty-state>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($branches as $branch)
                <x-ui.card class="flex flex-col p-5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="truncate font-semibold text-slate-900">{{ $branch->name }}</p>
                                @if($branch->is_default)
                                    <span class="badge badge-violet flex-shrink-0 text-xs">Predeterminada</span>
                                @endif
                            </div>
                            @if($branch->address)
                                <p class="mt-1 text-sm text-slate-600">{{ $branch->address }}</p>
                            @endif
                            @if($branch->city)
                                <p class="text-sm text-slate-500">{{ $branch->city }}</p>
                            @endif
                        </div>
                        <x-ui.action-menu>
                            <a href="{{ route('empresa.branches.edit', $branch) }}" class="action-item">Editar</a>
                            @if(!$branch->is_default)
                                <form method="POST" action="{{ route('empresa.branches.set-default', $branch) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="action-item">Marcar como predeterminada</button>
                                </form>
                                <form method="POST" action="{{ route('empresa.branches.destroy', $branch) }}" data-confirm="¿Eliminar la sucursal &laquo;{{ $branch->name }}&raquo;?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="action-item action-item--danger">Eliminar</button>
                                </form>
                            @endif
                        </x-ui.action-menu>
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    @endif
</x-app-layout>
