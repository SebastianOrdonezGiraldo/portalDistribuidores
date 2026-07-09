@props(['class' => 'h-4'])

{{--
Component contract:
- Props: class size/shape overrides merged into the skeleton base class.
- Slots: none.
- Use for: lightweight loading placeholders.
--}}
<div {{ $attributes->merge(['class' => 'skeleton '.$class]) }}></div>
