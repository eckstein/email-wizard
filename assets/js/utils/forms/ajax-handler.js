import { wizToast } from '../ui/swal2';

/**
 * Handles AJAX form submissions with proper error handling and notifications.
 * @param {HTMLFormElement} form - The form element to handle
 * @param {Object} options - Configuration options
 * @param {string} options.action - The AJAX action to trigger (without wiz_ajax_ prefix)
 * @param {Function} [options.onSuccess] - Success callback (response) => void
 * @param {Function} [options.onError] - Error callback (error) => void
 * @param {Function} [options.beforeSubmit] - Called before submission, return false to cancel
 * @param {Function} [options.afterSubmit] - Called after submission regardless of result
 * @param {Object} [options.toast] - Toast notification options
 * @returns {Promise} - Resolves with the response data
 */
export function handleAjaxForm(form, options) {
    const defaultToast = {
        success: {
            show: true,
            text: 'Changes saved successfully'
        },
        error: {
            show: true,
            text: 'An error occurred. Please try again.'
        }
    };

    const settings = {
        toast: { ...defaultToast, ...options.toast },
        ...options
    };

    // Add submit handler
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Call beforeSubmit if provided
        if (settings.beforeSubmit && settings.beforeSubmit() === false) {
            return;
        }

        const submitButton = form.querySelector('[type="submit"]');
        const loadingOverlay = document.createElement('div');
        loadingOverlay.className = 'wizard-form-loading-overlay';

        try {
            // Show loading state
            form.classList.add('loading');
            form.appendChild(loadingOverlay);
            if (submitButton) {
                submitButton.disabled = true;
            }

            // Prepare form data
            const formData = new FormData(form);
            formData.append('action', settings.action);
            formData.append('nonce', window.wizard?.nonce);

            // Make the request
            const response = await fetch(window.wizard?.ajaxurl || '/api/wiz-ajax/', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.data?.message || data.data || settings.toast.error.text);
            }

            // Show success toast if enabled
            if (settings.toast.success.show) {
                wizToast({
                    text: data.data?.message || settings.toast.success.text,
                    icon: 'success'
                });
            }

            // Call success callback if provided
            if (settings.onSuccess) {
                settings.onSuccess(data);
            }

        } catch (error) {
            console.error('Form submission error:', error);

            // Show error toast if enabled
            if (settings.toast.error.show) {
                wizToast({
                    text: error.message || settings.toast.error.text,
                    icon: 'error'
                });
            }

            // Call error callback if provided
            if (settings.onError) {
                settings.onError(error);
            }
        } finally {
            // Remove loading state
            form.classList.remove('loading');
            loadingOverlay.remove();
            if (submitButton) {
                submitButton.disabled = false;
            }

            // Call afterSubmit if provided
            if (settings.afterSubmit) {
                settings.afterSubmit();
            }
        }
    });
}

/**
 * Handles file upload with preview functionality
 * @param {HTMLInputElement} input - File input element
 * @param {Object} options - Configuration options
 * @param {string} options.action - The AJAX action to trigger
 * @param {string} options.previewSelector - Selector for preview element
 * @param {Function} [options.onSuccess] - Success callback
 * @param {Function} [options.onError] - Error callback
 * @returns {void}
 */
export function handleFileUpload(input, options) {
    input.addEventListener('change', async function() {
        if (!this.files || !this.files[0]) return;

        const file = this.files[0];
        const preview = document.querySelector(options.previewSelector);

        // Show preview if it's an image
        if (file.type.startsWith('image/') && preview) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }

        try {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('action', options.action);
            formData.append('nonce', window.wizard?.nonce);

            const response = await fetch(window.wizard?.ajaxurl || '/api/wiz-ajax/', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.data || 'Upload failed');
            }

            wizToast({
                text: 'File uploaded successfully',
                icon: 'success'
            });

            if (options.onSuccess) {
                options.onSuccess(data);
            }

        } catch (error) {
            console.error('Upload error:', error);
            wizToast({
                text: error.message || 'Upload failed',
                icon: 'error'
            });

            if (options.onError) {
                options.onError(error);
            }

            // Reset preview on error
            if (preview) {
                preview.src = preview.dataset.defaultSrc || '';
            }
        }
    });
} 