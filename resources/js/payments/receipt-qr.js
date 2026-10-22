/**
 * Render magic-link QR codes for uploading payment receipts from a phone.
 */
export function initReceiptQrCodes() {
    // Magic-link QR canvases for payment receipt upload.
    document.querySelectorAll('canvas[data-qr-url]').forEach(async (canvas) => {
        const url = canvas.getAttribute('data-qr-url');
        if (!url) {
            return;
        }

        try {
            const QRCode = (await import('qrcode')).default;
            await QRCode.toCanvas(canvas, url, {
                width: 160,
                margin: 1,
                color: { dark: '#0f172a', light: '#ffffff' },
            });
        } catch (error) {
            canvas.replaceWith(Object.assign(document.createElement('p'), {
                className: 'text-xs text-slate-500',
                textContent: 'No se pudo generar el QR. Usa el enlace de texto.',
            }));
        }
    });
}
