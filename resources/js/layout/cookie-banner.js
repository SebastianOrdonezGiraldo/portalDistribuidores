/**
 * Cookie notice shown until the visitor accepts it (localStorage with cookie fallback).
 */
export function initCookieBanner() {
    const cookieBanner = document.querySelector('[data-cookie-banner]');
    const cookieAcceptButton = cookieBanner?.querySelector('[data-cookie-accept]');
    const cookieConsentKey = 'portal_cookie_consent_v1';

    const hasCookieConsent = () => {
        try {
            if (window.localStorage.getItem(cookieConsentKey) === 'accepted') {
                return true;
            }
        } catch {
            // localStorage may be blocked in some browsers or private modes.
        }

        return document.cookie.split('; ').some((entry) => entry.startsWith(`${cookieConsentKey}=accepted`));
    };

    const persistCookieConsent = () => {
        try {
            window.localStorage.setItem(cookieConsentKey, 'accepted');
        } catch {
            // Keep working with cookie fallback if localStorage is not available.
        }

        const maxAge = 60 * 60 * 24 * 365;
        document.cookie = `${cookieConsentKey}=accepted; Path=/; Max-Age=${maxAge}; SameSite=Lax`;
    };

    if (cookieBanner) {
        if (!hasCookieConsent()) {
            cookieBanner.classList.remove('hidden');
        }

        cookieAcceptButton?.addEventListener('click', () => {
            persistCookieConsent();
            cookieBanner.classList.add('hidden');
        });
    }
}
