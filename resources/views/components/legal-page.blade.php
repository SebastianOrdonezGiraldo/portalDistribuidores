@props([
    'title',
    'subtitle' => null,
    'policyVersion',
])

<x-app-layout>
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <p class="text-xs font-bold uppercase tracking-[0.16em] text-brand-primary">Información legal</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $subtitle }}</p>
        @endif
        <p class="mt-3 text-xs text-slate-500">
            Versión {{ $policyVersion }} · Documento operativo sujeto a revisión jurídica.
        </p>

        <article class="mt-8 space-y-5 text-sm leading-relaxed text-slate-700 [&_h2]:mt-8 [&_h2]:text-base [&_h2]:font-semibold [&_h2]:text-slate-900 [&_ul]:list-disc [&_ul]:space-y-1 [&_ul]:pl-5">
            {{ $slot }}
        </article>

        <nav class="mt-10 flex flex-wrap gap-4 border-t border-slate-200 pt-6 text-sm" aria-label="Documentos legales">
            <a href="{{ route('legal.privacy') }}" class="font-medium text-brand-primary hover:underline">Política de privacidad</a>
            <a href="{{ route('legal.treatment') }}" class="font-medium text-brand-primary hover:underline">Aviso de tratamiento</a>
            <a href="{{ route('legal.terms') }}" class="font-medium text-brand-primary hover:underline">Términos y condiciones</a>
        </nav>
    </div>
</x-app-layout>
