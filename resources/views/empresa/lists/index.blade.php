{{--
View contract:
- Source: App\Modules\Company\Http\Controllers\CompanyListController::index.
- Expects: $lists for the authenticated distributor and optional global $cartCount from AppServiceProvider.
- Owns: frequent-list cards, save-from-cart modal, rename modal, and apply-to-cart actions.
- Notes: list permissions and cart/list mutations stay in CompanyListController/CartService.
--}}
<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Listas Frecuentes" subtitle="Guarda combinaciones de productos para reutilizarlas rápidamente en futuros pedidos.">
            <x-slot name="actions">
                @if(($cartCount ?? 0) > 0)
                    <button type="button"
                        onclick="document.getElementById('save-from-cart-modal').showModal()"
                        class="btn btn-primary">
                        Guardar carrito como lista
                    </button>
                @endif
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if($lists->isEmpty())
        <x-ui.empty-state
            title="Sin listas guardadas"
            description="Agrega productos al carrito y guárdalos como lista, o guarda los ítems de un pedido anterior."
        />
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($lists as $list)
                <x-ui.card class="flex flex-col p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-900" title="{{ $list->name }}">{{ $list->name }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">
                                {{ $list->items_count }} producto(s)
                                @if($list->creator)
                                    · por {{ $list->creator->name }}
                                @endif
                            </p>
                        </div>
                        <x-ui.action-menu>
                            <button type="button"
                                onclick="openRenameModal({{ $list->id }}, '{{ addslashes($list->name) }}')"
                                class="action-item">
                                Renombrar
                            </button>
                            <form method="POST" action="{{ route('empresa.lists.destroy', $list) }}" data-confirm="¿Eliminar la lista &laquo;{{ $list->name }}&raquo;? Esta acción no se puede deshacer.">
                                @csrf @method('DELETE')
                                <button type="submit" class="action-item action-item--danger">Eliminar</button>
                            </form>
                        </x-ui.action-menu>
                    </div>

                    <div class="mt-auto pt-4">
                        <form method="POST" action="{{ route('empresa.lists.apply', $list) }}">
                            @csrf
                            <button type="submit" class="btn btn-secondary w-full justify-center text-sm">
                                Agregar al carrito
                            </button>
                        </form>
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    @endif

    {{-- Modal: Guardar carrito como lista --}}
    @if(($cartCount ?? 0) > 0)
        <dialog id="save-from-cart-modal" class="modal-dialog">
            <div class="modal-dialog-panel w-full max-w-md">
                <h2 class="card-title">Guardar carrito como lista</h2>
                <p class="mt-1 text-sm text-slate-500">Dale un nombre a esta lista para encontrarla fácilmente.</p>
                <form method="POST" action="{{ route('empresa.lists.store-from-cart') }}" class="mt-4">
                    @csrf
                    <div>
                        <label class="form-label" for="list-name-cart">Nombre de la lista *</label>
                        <x-ui.input id="list-name-cart" name="name" placeholder="Ej: Insumos mensuales, Reposición Q1" required />
                        <x-input-error :messages="$errors->get('name')" />
                    </div>
                    <div class="modal-actions">
                        <button type="button" data-dialog-close="save-from-cart-modal" class="btn btn-secondary">Cancelar</button>
                        <x-ui.button type="submit" variant="primary">Guardar lista</x-ui.button>
                    </div>
                </form>
            </div>
        </dialog>
    @endif

    {{-- Modal: Renombrar lista --}}
    <dialog id="rename-list-modal" class="modal-dialog">
        <div class="modal-dialog-panel w-full max-w-md">
            <h2 class="card-title">Renombrar lista</h2>
            <form id="rename-list-form" method="POST" class="mt-4">
                @csrf @method('PATCH')
                <div>
                    <label class="form-label" for="rename-input">Nuevo nombre *</label>
                    <x-ui.input id="rename-input" name="name" required />
                </div>
                <div class="modal-actions">
                    <button type="button" data-dialog-close="rename-list-modal" class="btn btn-secondary">Cancelar</button>
                    <x-ui.button type="submit" variant="primary">Guardar</x-ui.button>
                </div>
            </form>
        </div>
    </dialog>

    @push('scripts')
        <script>
            function openRenameModal(listId, currentName) {
                const modal = document.getElementById('rename-list-modal');
                const form  = document.getElementById('rename-list-form');
                const input = document.getElementById('rename-input');
                form.action = `/empresa/listas/${listId}/nombre`;
                input.value = currentName;
                modal.showModal();
            }
        </script>
    @endpush
</x-app-layout>
