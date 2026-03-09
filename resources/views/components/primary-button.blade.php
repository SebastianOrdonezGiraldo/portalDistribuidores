<x-ui.button variant="primary" type="{{ $attributes->get('type', 'submit') }}" {{ $attributes->except('type') }}>
    {{ $slot }}
</x-ui.button>
