<tr class="border-b border-slate-100 text-sm">
    <td class="py-2 pr-3 font-semibold text-slate-800">{{ str_repeat('— ', $depth) }}{{ $category->name }}</td>
    <td class="py-2 pr-3">{{ $category->slug }}</td>
    <td class="py-2 pr-3">{{ $category->is_active ? 'Activo' : 'Inactivo' }}</td>
    <td class="py-2 pr-3">{{ $category->sort_order }}</td>
    <td class="py-2 text-right">
        <a href="{{ route('admin.categories.edit', $category) }}" class="text-[#BC2983] underline">Editar</a>
        <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="ml-2 text-slate-500 underline" onclick="return confirm('¿Eliminar categoría?')">Eliminar</button>
        </form>
    </td>
</tr>
@foreach($category->children as $child)
    @include('admin.categories._node', ['category' => $child, 'depth' => $depth + 1])
@endforeach

