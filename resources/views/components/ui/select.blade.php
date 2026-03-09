@props(['disabled' => false])

<select @disabled($disabled) {{ $attributes->merge(['class' => 'form-select']) }}>
    {{ $slot }}
</select>
