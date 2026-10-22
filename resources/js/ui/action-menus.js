/**
 * Close open <details data-action-menu> when clicking outside.
 */
export function initActionMenus() {
    document.addEventListener('click', (event) => {
        document.querySelectorAll('details[data-action-menu][open]').forEach((details) => {
            if (!details.contains(event.target)) {
                details.open = false;
            }
        });
    });
}
