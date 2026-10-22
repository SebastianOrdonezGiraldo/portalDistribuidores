import './bootstrap';
import Alpine from 'alpinejs';
import { initBulkTable } from './admin/bulk-table';
import { initProductForm } from './admin/product-form';
import { initProductLists } from './admin/product-list';
import { initCartForms } from './cart/cart-forms';
import { initCartFeedback } from './cart/feedback';
import { initCartQtyControlsOnPage } from './cart/qty-controls';
import { initPortalTour } from './features/portal-tour';
import { initCookieBanner } from './layout/cookie-banner';
import { initSidebar } from './layout/sidebar';
import { initReceiptQrCodes } from './payments/receipt-qr';
import { initPaymentStatusPolling } from './payments/status-poll';
import { initActionMenus } from './ui/action-menus';
import { initConfirmAndLoadingForms } from './ui/confirm-forms';
import { initDialogsAndToasts } from './ui/dialogs';
import { initImageFallback } from './ui/image-fallback';
import { initLoginRequiredModal } from './ui/login-required-modal';

window.Alpine = Alpine;
Alpine.start();

// Each module only binds to elements present on the current page; keep the
// order, since later modules reuse helpers returned by earlier ones.
document.addEventListener('DOMContentLoaded', () => {
    initImageFallback();

    const sidebar = initSidebar();
    initCookieBanner();
    initPortalTour(sidebar);

    initDialogsAndToasts();
    const showLoginRequiredModal = initLoginRequiredModal();
    const cartFeedback = initCartFeedback();
    initActionMenus();
    initConfirmAndLoadingForms();
    initCartForms({ ...cartFeedback, showLoginRequiredModal });
    initCartQtyControlsOnPage();

    initBulkTable();
    initProductLists();
    initProductForm({ showInlineToast: cartFeedback.showInlineToast });

    initReceiptQrCodes();
    initPaymentStatusPolling();
});
