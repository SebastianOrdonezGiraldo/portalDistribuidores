@props(['label' => null, 'help' => null, 'checked' => false])

<label class="inline-flex items-start gap-2 text-sm text-slate-700">
    <input type="checkbox" @checked($checked) {{ $attributes->merge(['class' => 'form-checkbox mt-0.5']) }}>
    <span>
        <span class="font-medium">{{ $label ?? $slot }}</span>
        @if($help)
            <span class="form-help block">{{ $help }}</span>
        @endif
    </span>
</label>
