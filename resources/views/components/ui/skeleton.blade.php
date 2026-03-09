@props(['class' => 'h-4'])

<div {{ $attributes->merge(['class' => 'skeleton '.$class]) }}></div>
