@props([
    'action',
    'confirm' => '¿Eliminar este elemento?',
    'label'   => 'Eliminar',
])

<button
    type="submit"
    formaction="{{ $action }}"
    formmethod="POST"
    name="_method"
    value="DELETE"
    formnovalidate
    onclick="return confirm('{{ $confirm }}');"
    {{ $attributes->merge(['class' => 'text-xs font-semibold text-red-700 hover:text-red-800']) }}
>
    {{ $label }}
</button>
