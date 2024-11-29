/**
 * HTTP request handling utilities
 */

import { showErrorToast } from './notification-utils';

/**
 * Handles HTTP response and throws error if response is not ok
 * @param {Response} response - Fetch API Response object
 * @returns {Promise<any>} Parsed JSON response
 * @throws {Error} If response is not ok
 */
export function handleHTTPResponse(response) {
    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }
    return response.json();
}

/**
 * Generic error handler for fetch requests
 * @param {Error} error - Error object
 */
export function handleFetchError(error) {
    console.error("Error:", error);
    showErrorToast(error.message);
}

/**
 * Removes a parameter from a URL
 * @param {string} url - URL to modify
 * @param {string} parameter - Parameter to remove
 * @returns {string} Modified URL
 */
export function removeUrlParameter(url, parameter) {
    const urlParts = url.split("?");
    if (urlParts.length < 2) return url;

    const prefix = encodeURIComponent(parameter) + "=";
    const pars = urlParts[1].split(/[&;]/g);

    // Reverse iteration as we're removing elements
    for (let i = pars.length; i-- > 0;) {
        if (pars[i].lastIndexOf(prefix, 0) !== -1) {
            pars.splice(i, 1);
        }
    }

    return urlParts[0] + (pars.length > 0 ? "?" + pars.join("&") : "");
} 