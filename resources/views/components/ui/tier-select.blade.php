@props([
    'name' => 'tier',
    'value' => null,
    'options' => null,
    'id' => null,
    'disabled' => false,
])

{{--
Component contract:
- Props: name, value, options (value=>label map), id, disabled.
- Slots: none.
- Use for: Plata/Oro selects in admin forms; compatible with old().
--}}
@php
    use App\Modules\Shared\Enums\DistributorTier;

    $selectId = $id ?? $name;
    $selected = old($name, $value ?? DistributorTier::Silver->value);

    /** @var array<string, string> $tierOptions */
    $tierOptions = $options ?? collect(DistributorTier::cases())
        ->mapWithKeys(fn (DistributorTier $tier) => [$tier->value => $tier->label()])
        ->all();
@endphp

<x-ui.select
    :id="$selectId"
    :name="$name"
    :disabled="$disabled"
    {{ $attributes }}
>
    @foreach($tierOptions as $optionValue => $optionLabel)
        <option value="{{ $optionValue }}" @selected((string) $selected === (string) $optionValue)>{{ $optionLabel }}</option>
    @endforeach
</x-ui.select>
