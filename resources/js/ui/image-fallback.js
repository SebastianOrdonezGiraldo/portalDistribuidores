/**
 * Hide broken images and reveal their fallback sibling.
 */
export function initImageFallback() {
    document.querySelectorAll('[data-image-fallback]').forEach((image) => {
        image.addEventListener('error', () => {
            image.classList.add('hidden');
            image.nextElementSibling?.removeAttribute('hidden');
            image.nextElementSibling?.classList.remove('hidden');
        }, { once: true });
    });
}
