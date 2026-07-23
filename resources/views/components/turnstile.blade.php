@php
    $enabled = (bool) config('services.turnstile.enabled');
    $siteKey = (string) config('services.turnstile.site_key', '');
@endphp

@if ($enabled && $siteKey !== '')
    <div {{ $attributes->merge(['class' => 'space-y-2']) }}>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        <div
            class="cf-turnstile"
            data-sitekey="{{ $siteKey }}"
            data-theme="light"
        ></div>
        <x-input-error :messages="$errors->get('cf-turnstile-response')" class="mt-2" />
    </div>
@endif
