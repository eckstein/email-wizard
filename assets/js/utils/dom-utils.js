/**
 * DOM manipulation and event handling utilities
 */

/**
 * Safely adds an event listener to elements matching a selector
 * @param {string|'document'} selector - CSS selector or 'document'
 * @param {string} eventType - Type of event to listen for
 * @param {Function} callback - Event handler function
 * @param {boolean} [passive=false] - Whether to use passive event listening
 */
export function addEventListenerIfExists(selector, eventType, callback, passive = false) {
    if (selector === "document") {
        document.addEventListener(eventType, callback, { passive });
        return;
    }
    
    const elements = document.querySelectorAll(selector);
    elements.forEach((el) => el.addEventListener(eventType, callback, { passive }));
}

/**
 * Highlights an element temporarily with a fade effect
 * @param {string} selector - CSS selector for the element
 * @param {number} [duration=1000] - Duration of the highlight in milliseconds
 * @param {string} [highlightColor='#f4f0dc'] - Color to use for highlighting
 */
export function highlightElement(selector, duration = 1000, highlightColor = '#f4f0dc') {
    const element = document.querySelector(selector);
    if (!element) return;

    element.classList.add("highlight-fade");
    element.style.backgroundColor = highlightColor;

    setTimeout(() => {
        element.style.backgroundColor = "";
    }, duration);

    setTimeout(() => {
        element.classList.remove("highlight-fade");
    }, duration + 1000);
}

/**
 * Highlights an element and then removes it with a fade effect
 * @param {HTMLElement} element - Element to highlight and remove
 * @param {string} [highlightColor='#ffebee'] - Color to use for highlighting
 */
export function highlightAndRemove(element, highlightColor = '#ffebee') {
    if (!element) return;

    element.style.transition = "background-color 0.5s, opacity 0.5s";
    element.style.backgroundColor = highlightColor;
    
    setTimeout(() => {
        element.style.opacity = "0";
        setTimeout(() => {
            element.remove();
        }, 500);
    }, 1000);
} 