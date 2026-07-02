@props(['value', 'required' => false, 'optional' => false])

<label {{ $attributes->merge(['class' => 'form-label']) }}>
    <span>{{ $value ?? $slot }}</span>
    @if($required)
        <span class="form-label-marker is-required">Obligatorio</span>
    @elseif($optional)
        <span class="form-label-marker is-optional">Opcional</span>
    @endif
</label>
