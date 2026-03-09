@props(['disabled' => false, 'rows' => 4])

<textarea rows="{{ $rows }}" @disabled($disabled) {{ $attributes->merge(['class' => 'form-textarea']) }}>{{ $slot }}</textarea>
