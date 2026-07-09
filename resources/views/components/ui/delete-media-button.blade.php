@props([
    'action',
    'confirm' => '¿Eliminar este elemento?',
    'label'   => 'Eliminar',
])

{{--
Component contract:
- Props: action URL, confirm prompt, and label.
- Slots: none.
- Use for: DELETE submit buttons inside media-management forms.
--}}
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
