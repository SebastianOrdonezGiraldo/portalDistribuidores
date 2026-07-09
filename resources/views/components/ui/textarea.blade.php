@props(['disabled' => false, 'rows' => 4])

{{--
Component contract:
- Props: disabled flag and row count; all other textarea attributes pass through $attributes.
- Slots: textarea content.
- Use for: standard multiline inputs styled with form-textarea.
--}}
<textarea rows="{{ $rows }}" @disabled($disabled) {{ $attributes->merge(['class' => 'form-textarea']) }}>{{ $slot }}</textarea>
