/**
 * Notification and toast utilities
 */

import { wizToast } from "./ui/swal2";

/**
 * Shows a toast notification
 * @param {Object} options - Toast options
 * @param {string} options.text - Toast message
 * @param {string} [options.icon='info'] - Toast icon (success, error, warning, info)
 * @param {number} [options.timer=10000] - Auto-close timer in milliseconds
 * @param {boolean} [options.asHtml=false] - Whether to render text as HTML
 */
export function showToast({ text, icon = 'info', timer = 10000, asHtml = false }) {
    const args = {
        timer,
        icon,
        ...(asHtml ? { html: text } : { text })
    };
    wizToast(args);
}

/**
 * Shows a success toast notification
 * @param {string} text - Toast message
 * @param {number} [timer=10000] - Auto-close timer in milliseconds
 * @param {boolean} [asHtml=false] - Whether to render text as HTML
 */
export function showSuccessToast(text = "Success!", timer = 10000, asHtml = false) {
    showToast({ text, icon: 'success', timer, asHtml });
}

/**
 * Shows an error toast notification
 * @param {string} text - Toast message
 * @param {number} [timer=10000] - Auto-close timer in milliseconds
 * @param {boolean} [asHtml=false] - Whether to render text as HTML
 */
export function showErrorToast(text = "Error!", timer = 10000, asHtml = false) {
    showToast({ text, icon: 'error', timer, asHtml });
} 