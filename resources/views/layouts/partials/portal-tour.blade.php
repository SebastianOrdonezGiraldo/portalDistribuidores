@php
    $shouldStartAutomatically = $user->distributor?->portal_tour_completed_at === null;
    $shouldStartFromQuery = request()->boolean('tutorial');
@endphp

<div
    data-portal-tour-root
    data-auto-start="{{ $shouldStartAutomatically || $shouldStartFromQuery ? 'true' : 'false' }}"
    data-replay-requested="{{ $shouldStartFromQuery ? 'true' : 'false' }}"
    data-complete-url="{{ route('empresa.portal-tour.complete') }}"
    class="hidden"
    aria-hidden="true"
>
    <div data-portal-tour-highlight class="portal-tour-highlight"></div>

    <section
        data-portal-tour-dialog
        class="portal-tour-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="portal-tour-title"
        aria-describedby="portal-tour-description"
        tabindex="-1"
    >
        <span data-portal-tour-arrow class="portal-tour-arrow" aria-hidden="true"></span>
        <button type="button" data-portal-tour-skip class="portal-tour-close" aria-label="Omitir tutorial">×</button>

        <div class="portal-tour-heading">
            <span data-portal-tour-icon class="portal-tour-icon" aria-hidden="true"></span>
            <div class="min-w-0">
                <p data-portal-tour-progress class="portal-tour-progress">1 de 4</p>
                <h2 id="portal-tour-title" data-portal-tour-title class="portal-tour-title"></h2>
            </div>
        </div>

        <p id="portal-tour-description" data-portal-tour-description class="portal-tour-description"></p>

        <div data-portal-tour-dots class="portal-tour-dots" aria-hidden="true"></div>

        <div class="portal-tour-actions">
            <button type="button" data-portal-tour-skip class="portal-tour-skip">Omitir</button>
            <div class="flex items-center gap-2">
                <button type="button" data-portal-tour-previous class="btn btn-secondary !min-h-9 !px-3">Anterior</button>
                <button type="button" data-portal-tour-next class="btn btn-primary !min-h-9 !px-3">Siguiente</button>
            </div>
        </div>
    </section>
</div>
