import { closeModal, openModal } from '../lib/modal';

/**
 * Modal asking guests to log in before adding to the cart.
 */
export function initLoginRequiredModal() {
    const loginRequiredModal = document.querySelector('[data-login-required-modal]');
    const loginRequiredLink = loginRequiredModal?.querySelector('[data-login-required-link]');
    const showLoginRequiredModal = (payload = {}) => {
        if (!loginRequiredModal) {
            window.location.href = payload.login_url || '/login';
            return;
        }

        if (loginRequiredLink && payload.login_url) {
            loginRequiredLink.setAttribute('href', payload.login_url);
        }

        openModal(loginRequiredModal);
    };

    loginRequiredModal?.querySelectorAll('[data-login-required-close]').forEach((button) => {
        button.addEventListener('click', () => closeModal(loginRequiredModal));
    });

    loginRequiredModal?.addEventListener('click', (event) => {
        if (event.target === loginRequiredModal) {
            closeModal(loginRequiredModal);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && loginRequiredModal && !loginRequiredModal.classList.contains('hidden')) {
            closeModal(loginRequiredModal);
        }
    });

    return showLoginRequiredModal;
}
