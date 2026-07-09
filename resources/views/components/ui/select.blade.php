@props(['disabled' => false])

{{--
Component contract:
- Props: disabled flag; all other select attributes pass through $attributes.
- Slots: option elements.
- Use for: standard selects styled with form-select.
--}}
<select @disabled($disabled) {{ $attributes->merge(['class' => 'form-select']) }}>
    {{ $slot }}
</select>
