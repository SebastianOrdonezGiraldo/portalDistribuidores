<x-ui.button variant="danger" type="{{ $attributes->get('type', 'submit') }}" {{ $attributes->except('type') }}>
    {{ $slot }}
</x-ui.button>
