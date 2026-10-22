/**
 * Poll payment status while waiting for a cross-device receipt upload.
 */
export function initPaymentStatusPolling() {
    // Poll payment status while waiting for cross-device receipt upload.
    document.querySelectorAll('[data-payment-panel]').forEach((panel) => {
        const statusUrl = panel.getAttribute('data-payment-status-url');
        let currentStatus = panel.getAttribute('data-payment-status') || '';
        const expiresAtRaw = panel.getAttribute('data-payment-expires-at');
        const expiresAt = expiresAtRaw ? Date.parse(expiresAtRaw) : null;
        const badge = panel.querySelector('[data-payment-status-badge]');
        const message = panel.querySelector('[data-payment-status-message]');
        const uploadZone = panel.querySelector('[data-payment-upload-zone]');
        const pollable = ['pending_upload', 'rejected'];
        const badgeClassByStatus = {
            not_applicable: 'payment-status-na',
            pending_upload: 'payment-status-pending',
            confirming: 'payment-status-confirming',
            validated: 'payment-status-validated',
            rejected: 'payment-status-rejected',
            expired: 'payment-status-expired',
        };
        const statusMessages = {
            confirming: 'Comprobante recibido. Estamos confirmando tu pago.',
            validated: 'Pago validado. Continuamos con la gestión de tu pedido.',
            expired: 'El plazo de pago venció y la reserva se liberó. Puedes armar un nuevo pedido desde el catálogo.',
            rejected: 'Tu comprobante fue rechazado. Sube uno nuevo antes de que venza la reserva.',
        };

        if (!statusUrl || !pollable.includes(currentStatus)) {
            return;
        }

        let failures = 0;
        let timer = null;
        let celebrating = false;
        let stopped = false;
        let inFlight = false;
        const baseIntervalMs = 8000;
        const maxIntervalMs = 60000;
        const maxFailures = 8;

        const clearTimer = () => {
            if (timer) {
                window.clearTimeout(timer);
                timer = null;
            }
        };

        const stop = () => {
            stopped = true;
            clearTimer();
        };

        const nextDelayMs = () => {
            if (failures <= 0) {
                return baseIntervalMs;
            }

            // Exponential backoff on consecutive network/5xx failures: 8s → 16s → 32s → 60s.
            return Math.min(maxIntervalMs, baseIntervalMs * (2 ** Math.min(failures, 3)));
        };

        const scheduleNext = () => {
            if (stopped || celebrating) {
                return;
            }

            if (document.visibilityState === 'hidden') {
                clearTimer();
                return;
            }

            clearTimer();
            timer = window.setTimeout(() => {
                tick();
            }, nextDelayMs());
        };

        const shouldStopForTtl = () => {
            if (!expiresAt || Number.isNaN(expiresAt)) {
                return false;
            }

            // Stop a bit after reservation TTL (+2 min margin).
            return Date.now() > (expiresAt + 120000);
        };

        const syncBadgeClass = (status) => {
            if (!badge) {
                return;
            }

            Object.values(badgeClassByStatus).forEach((cls) => badge.classList.remove(cls));
            const next = badgeClassByStatus[status];
            if (next) {
                badge.classList.add(next);
            }
        };

        const renderConfirmingUi = (payload) => {
            currentStatus = payload.payment_status || currentStatus;
            panel.setAttribute('data-payment-status', currentStatus);

            if (badge) {
                badge.textContent = payload.payment_status_label || 'Confirmando pago';
            }

            syncBadgeClass(currentStatus);

            if (message) {
                message.textContent = statusMessages.confirming;
            }

            if (uploadZone) {
                uploadZone.hidden = true;
            }
        };

        const celebrateReceiptReceived = () => {
            const modal = document.querySelector('[data-payment-success-modal]');

            if (celebrating || !modal || modal.classList.contains('is-active')) {
                return;
            }

            celebrating = true;
            clearTimer();

            const previousFocus = document.activeElement instanceof HTMLElement
                ? document.activeElement
                : null;
            const dialog = modal.querySelector('[data-payment-success-dialog]');
            const closeButton = modal.querySelector('[data-payment-success-close]');
            const label = modal.querySelector('[data-payment-success-label]');
            const previousOverflow = document.body.style.overflow;

            modal.hidden = false;
            modal.classList.add('is-active');
            document.body.style.overflow = 'hidden';

            // Re-set label so aria-live announces once the dialog is shown.
            if (label) {
                label.textContent = '';
                label.textContent = 'Comprobante recibido';
            }

            let closed = false;
            const close = () => {
                if (closed) {
                    return;
                }

                closed = true;
                modal.classList.remove('is-active');
                modal.hidden = true;
                document.body.style.overflow = previousOverflow;
                celebrating = false;
                document.removeEventListener('keydown', onKeydown);

                modal.querySelectorAll('[data-payment-success-dismiss]').forEach((control) => {
                    control.removeEventListener('click', close);
                });

                if (previousFocus && document.contains(previousFocus)) {
                    previousFocus.focus({ preventScroll: true });
                }
            };

            const onKeydown = (event) => {
                if (event.key === 'Escape') {
                    event.preventDefault();
                    close();
                }
            };

            modal.querySelectorAll('[data-payment-success-dismiss]').forEach((control) => {
                control.addEventListener('click', close, { once: true });
            });
            document.addEventListener('keydown', onKeydown);
            (closeButton || dialog)?.focus({ preventScroll: true });
        };

        const applyStatus = (payload) => {
            const previousStatus = currentStatus;
            const nextStatus = payload.payment_status || currentStatus;

            if (nextStatus === currentStatus) {
                return;
            }

            // Terminal / non-upload states: update from payload, stop polling. No full reload.
            if (!pollable.includes(nextStatus)) {
                stop();

                // Positive poll response: receipt landed in BD → confirming.
                if (nextStatus === 'confirming' && pollable.includes(previousStatus)) {
                    renderConfirmingUi(payload);
                    celebrateReceiptReceived();
                    return;
                }

                currentStatus = nextStatus;
                panel.setAttribute('data-payment-status', currentStatus);

                if (badge && payload.payment_status_label) {
                    badge.textContent = payload.payment_status_label;
                }

                syncBadgeClass(currentStatus);

                if (message && statusMessages[currentStatus]) {
                    message.textContent = statusMessages[currentStatus];
                }

                if (uploadZone && !payload.allows_upload) {
                    uploadZone.hidden = true;
                }

                return;
            }

            currentStatus = nextStatus;
            panel.setAttribute('data-payment-status', currentStatus);

            if (badge && payload.payment_status_label) {
                badge.textContent = payload.payment_status_label;
            }

            syncBadgeClass(currentStatus);

            if (message && statusMessages[currentStatus]) {
                message.textContent = statusMessages[currentStatus];
            }

            if (uploadZone) {
                uploadZone.hidden = !payload.allows_upload;
            }
        };

        const tick = async () => {
            if (stopped || celebrating || inFlight) {
                return;
            }

            if (document.visibilityState === 'hidden') {
                clearTimer();
                return;
            }

            if (shouldStopForTtl()) {
                stop();
                if (message) {
                    message.textContent = `${message.textContent} (El plazo de reserva venció; recarga si necesitás el estado final.)`;
                }
                return;
            }

            inFlight = true;

            try {
                const response = await fetch(statusUrl, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    throw new Error(`poll failed: ${response.status}`);
                }

                const payload = await response.json();
                failures = 0;
                await applyStatus(payload);
            } catch (error) {
                failures += 1;
                if (failures >= maxFailures) {
                    stop();
                    if (message) {
                        message.textContent = `${message.textContent} (No pudimos actualizar el estado automáticamente; recarga la página.)`;
                    }
                    return;
                }
            } finally {
                inFlight = false;
            }

            if (!stopped && pollable.includes(currentStatus)) {
                scheduleNext();
            }
        };

        const onVisibilityChange = () => {
            if (stopped) {
                return;
            }

            if (document.visibilityState === 'hidden') {
                // Pause for real: clear the timer so we don't hit the API in background.
                clearTimer();
                return;
            }

            // Resume with an immediate poll when the tab becomes visible again.
            tick();
        };

        document.addEventListener('visibilitychange', onVisibilityChange);

        if (document.visibilityState === 'visible') {
            scheduleNext();
        }
    });
}
