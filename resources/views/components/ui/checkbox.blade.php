@props(['label' => null, 'help' => null, 'checked' => false])

{{--
Component contract:
- Props: label, optional help text, and checked state.
- Slots: default label fallback.
- Use for: single checkbox controls with inline helper copy.
--}}
<label class="inline-flex items-start gap-2 text-sm text-slate-700">
    <input type="checkbox" @checked($checked) {{ $attributes->merge(['class' => 'form-checkbox mt-0.5']) }}>
    <span>
        <span class="font-medium">{{ $label ?? $slot }}</span>
        @if($help)
            <span class="form-help block">{{ $help }}</span>
        @endif
    </span>
</label>
