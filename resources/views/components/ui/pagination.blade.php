@props(['paginator'])

@php
    $onEachSide = 1;
    $paged = $paginator->onEachSide($onEachSide);
    $elements = $paged->elements();
    $currentPage = $paginator->currentPage();
    $lastPage = $paginator->lastPage();
    $from = $paginator->firstItem();
    $to = $paginator->lastItem();
    $total = $paginator->total();
@endphp

@if($paginator && $paginator->hasPages())
    <nav
        {{ $attributes->merge(['class' => 'flex flex-col items-center gap-3 sm:flex-row sm:items-center sm:justify-between']) }}
        aria-label="Paginación"
        role="navigation"
    >
        {{-- Contador de resultados --}}
        <p class="order-2 text-sm text-slate-500 sm:order-1">
            Mostrando
            <span class="font-semibold text-slate-800">{{ number_format($from, 0, ',', '.') }}</span>
            –
            <span class="font-semibold text-slate-800">{{ number_format($to, 0, ',', '.') }}</span>
            de
            <span class="font-semibold text-slate-800">{{ number_format($total, 0, ',', '.') }}</span>
            resultados
        </p>

        {{-- Botones de paginación --}}
        <div class="order-1 flex items-center gap-1 sm:order-2" role="list">

            {{-- Anterior --}}
            @if($paginator->onFirstPage())
                <span
                    class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-300"
                    aria-disabled="true"
                    aria-label="Página anterior"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                </span>
            @else
                <a
                    href="{{ $paginator->previousPageUrl() }}"
                    rel="prev"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 focus-ring"
                    aria-label="Página anterior"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                </a>
            @endif

            {{-- Números de página --}}
            @foreach($elements as $element)
                @if(is_string($element))
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-sm text-slate-400 select-none">…</span>
                @elseif(is_array($element))
                    @foreach($element as $page => $url)
                        @if($page === $currentPage)
                            <span
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-brand-primary bg-brand-primary text-sm font-semibold text-white"
                                aria-current="page"
                                aria-label="Página {{ $page }}, página actual"
                            >{{ $page }}</span>
                        @else
                            <a
                                href="{{ $url }}"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 focus-ring"
                                aria-label="Ir a página {{ $page }}"
                            >{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Siguiente --}}
            @if($paginator->hasMorePages())
                <a
                    href="{{ $paginator->nextPageUrl() }}"
                    rel="next"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 focus-ring"
                    aria-label="Página siguiente"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            @else
                <span
                    class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-300"
                    aria-disabled="true"
                    aria-label="Página siguiente"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                </span>
            @endif
        </div>
    </nav>
@endif
