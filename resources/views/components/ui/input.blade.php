@props(['disabled' => false])

{{--
Component contract:
- Props: disabled flag; all other input attributes pass through $attributes.
- Slots: none.
- Use for: standard text-like inputs styled with form-input.
--}}
<input @disabled($disabled) {{ $attributes->merge(['class' => 'form-input']) }}>
