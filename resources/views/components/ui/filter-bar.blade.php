{{--
Component contract:
- Props: none; form attributes are passed through $attributes.
- Slots: default slot contains filter controls and submit/reset actions.
- Use for: GET/POST filter forms that should share card spacing.
--}}
<form {{ $attributes->merge(['class' => 'card p-4 sm:p-5']) }}>
    {{ $slot }}
</form>
