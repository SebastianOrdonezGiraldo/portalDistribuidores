{{-- Escritorio: tarjetas completas y discretas en la esquina. --}}
<aside x-data="{ distributorOpen: true, helpOpen: true }" class="catalog-floating-cards hidden sm:grid" aria-label="Ayuda y beneficios del portal">
    <section x-show="distributorOpen" x-transition class="catalog-support-card catalog-support-card--distributor">
        <button type="button" class="catalog-support-close" @click="distributorOpen = false" aria-label="Cerrar información para distribuidores">×</button>
        <a href="{{ auth()->check() ? route('dashboard') : route('register') }}" class="catalog-support-card-link">
            <span class="catalog-support-card-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="7" r="3"/><circle cx="17" cy="9" r="2.2"/><path d="M3 20v-1.4A4.6 4.6 0 0 1 7.6 14h2.8a4.6 4.6 0 0 1 4.6 4.6V20"/><path d="M15 14.2a3.7 3.7 0 0 1 5.2 3.4V20"/></svg></span>
            <span><strong>¿Quieres ser distribuidor?</strong><small>Accede a beneficios exclusivos y precios especiales.</small><em>{{ auth()->check() ? 'Ir a mi portal' : 'Crear cuenta distribuidor' }}</em></span>
        </a>
    </section>
    <section x-show="helpOpen" x-transition class="catalog-support-card catalog-support-card--whatsapp">
        <button type="button" class="catalog-support-close" @click="helpOpen = false" aria-label="Cerrar ayuda por WhatsApp">×</button>
        <a href="https://wa.me/573117479607?text=Hola%20vengo%20desde%20el%20portal" target="_blank" rel="noopener noreferrer" class="catalog-support-card-link">
            <span class="catalog-support-card-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.5 11.7a8.5 8.5 0 0 1-12.56 7.5L3.5 20.5l1.3-4.2A8.5 8.5 0 1 1 20.5 11.7Z"/><path d="M8.1 7.8c.2-.5.5-.5.8-.5h.5c.2 0 .4.1.5.4l.7 1.7c.1.2.1.4 0 .6l-.5.7c-.1.2-.1.3 0 .5.6 1.1 1.5 2 2.6 2.6.2.1.3.1.5 0l.7-.5c.2-.1.4-.1.6 0l1.7.7c.3.1.4.3.4.5v.5c0 .3 0 .6-.5.8-.5.2-1.6.5-3-.1-1-.4-2.2-1.1-3.4-2.3-1-1-1.8-2.1-2.2-3.1-.6-1.4-.3-2.5-.1-3Z"/></svg></span>
            <span><strong>¿Necesitas ayuda?</strong><small>Nuestro equipo está listo para asesorarte.</small><em>Escríbenos por WhatsApp</em></span>
        </a>
    </section>
</aside>

{{-- Móvil: accesos reducidos para no competir con la compra fija. --}}
<aside x-data="{ supportOpen: true }" x-show="supportOpen" x-transition class="catalog-support-bubbles {{ request()->routeIs('products.show') ? 'catalog-support-bubbles--product' : '' }} sm:hidden" aria-label="Ayuda y beneficios del portal">
    <button type="button" class="catalog-support-bubbles-close" @click="supportOpen = false" aria-label="Ocultar accesos de ayuda">×</button>
    <a href="{{ auth()->check() ? route('dashboard') : route('register') }}" class="catalog-support-bubble catalog-support-bubble--distributor" aria-label="{{ auth()->check() ? 'Ir a mi portal' : 'Crear cuenta distribuidor' }}" title="{{ auth()->check() ? 'Ir a mi portal' : 'Crear cuenta distribuidor' }}"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="7" r="3"/><circle cx="17" cy="9" r="2.2"/><path d="M3 20v-1.4A4.6 4.6 0 0 1 7.6 14h2.8a4.6 4.6 0 0 1 4.6 4.6V20"/><path d="M15 14.2a3.7 3.7 0 0 1 5.2 3.4V20"/></svg></a>
    <a href="https://wa.me/573117479607?text=Hola%20vengo%20desde%20el%20portal" target="_blank" rel="noopener noreferrer" class="catalog-support-bubble catalog-support-bubble--whatsapp" aria-label="Escríbenos por WhatsApp" title="Escríbenos por WhatsApp"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.5 11.7a8.5 8.5 0 0 1-12.56 7.5L3.5 20.5l1.3-4.2A8.5 8.5 0 1 1 20.5 11.7Z"/><path d="M8.1 7.8c.2-.5.5-.5.8-.5h.5c.2 0 .4.1.5.4l.7 1.7c.1.2.1.4 0 .6l-.5.7c-.1.2-.1.3 0 .5.6 1.1 1.5 2 2.6 2.6.2.1.3.1.5 0l.7-.5c.2-.1.4-.1.6 0l1.7.7c.3.1.4.3.4.5v.5c0 .3 0 .6-.5.8-.5.2-1.6.5-3-.1-1-.4-2.2-1.1-3.4-2.3-1-1-1.8-2.1-2.2-3.1-.6-1.4-.3-2.5-.1-3Z"/></svg></a>
</aside>
