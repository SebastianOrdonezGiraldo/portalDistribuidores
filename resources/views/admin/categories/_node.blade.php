<tr>
    <td>
        <div class="flex items-center gap-2" style="padding-left: {{ $depth * 18 }}px">
            @if($depth > 0)
                <span class="h-px w-4 bg-slate-300"></span>
            @endif
            <div>
                <p class="font-semibold text-slate-900">{{ $category->name }}</p>
                @if($depth > 0)
                    <p class="text-[11px] text-slate-500">Nivel {{ $depth + 1 }}</p>
                @endif
            </div>
        </div>
    </td>
    <td class="font-medium text-slate-700">{{ $category->slug }}</td>
    <td>
        <x-ui.status-badge :status="$category->is_active ? 'active' : 'inactive'" />
    </td>
    <td>
        <x-ui.badge :variant="$category->products_count > 0 ? 'success' : 'neutral'">{{ $category->products_count }}</x-ui.badge>
    </td>
    <td>
        <x-ui.badge :variant="$category->synonyms_count > 0 ? 'info' : 'neutral'">{{ $category->synonyms_count }}</x-ui.badge>
    </td>
    <td>{{ $category->sort_order }}</td>
    <td>
        <p>{{ $category->updated_at?->format('d/m/Y') }}</p>
        <p class="text-[11px] text-slate-500">{{ $category->updated_at?->diffForHumans() }}</p>
    </td>
    <td class="text-right">
        <x-ui.action-menu>
            <a href="{{ route('admin.categories.edit', $category) }}" class="block rounded-lg px-3 py-2 hover:bg-slate-50">Editar</a>
            <form action="{{ route('admin.categories.status', $category) }}" method="POST" data-confirm="{{ $category->is_active ? '¿Desactivar '.$category->name.'?' : '¿Activar '.$category->name.'?' }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="is_active" value="{{ $category->is_active ? 0 : 1 }}">
                <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left hover:bg-slate-50">{{ $category->is_active ? 'Desactivar' : 'Activar' }}</button>
            </form>
            <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" data-confirm="¿Eliminar {{ $category->name }}? Esta acción no se puede deshacer.">
                @csrf
                @method('DELETE')
                <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left text-red-700 hover:bg-red-50">Eliminar</button>
            </form>
        </x-ui.action-menu>
    </td>
</tr>
@foreach($category->children as $child)
    @include('admin.categories._node', ['category' => $child, 'depth' => $depth + 1])
@endforeach
