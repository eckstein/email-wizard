import { handleAjaxForm, handleFileUpload } from '../../utils/forms/ajax-handler';
import { wizToast } from '../../utils/ui/swal2';
import Swal from 'sweetalert2';

/**
 * Initialize account settings functionality
 */
export function initAccountSettings() {
    const accountForm = document.querySelector('.account-settings-form');
    if (!accountForm) return;

    // Handle main account form submission
    handleAjaxForm(accountForm, {
        action: 'update_account',
        toast: {
            success: { 
                show: true,
                text: 'Account information updated successfully' 
            },
            error: { 
                show: true,
                text: 'Failed to update account information' 
            }
        },
        beforeSubmit: () => {
            // Validate password fields if attempting to change password
            const currentPassword = accountForm.querySelector('#current_password');
            const newPassword = accountForm.querySelector('#new_password');
            const confirmPassword = accountForm.querySelector('#confirm_password');

            if (newPassword.value || confirmPassword.value || currentPassword.value) {
                if (!currentPassword.value) {
                    wizToast({
                        text: 'Please enter your current password',
                        icon: 'error'
                    });
                    currentPassword.focus();
                    return false;
                }

                if (newPassword.value !== confirmPassword.value) {
                    wizToast({
                        text: 'New passwords do not match',
                        icon: 'error'
                    });
                    newPassword.focus();
                    return false;
                }

                if (newPassword.value.length < 8) {
                    wizToast({
                        text: 'Password must be at least 8 characters long',
                        icon: 'error'
                    });
                    newPassword.focus();
                    return false;
                }
            }

            return true;
        }
    });

    // Handle avatar upload
    const avatarInput = document.querySelector('#avatar-upload');
    if (avatarInput) {
        handleFileUpload(avatarInput, {
            action: 'update_avatar',
            previewSelector: '#user-avatar-container img',
            onSuccess: (data) => {
                // Enable delete button after successful upload
                const deleteBtn = document.querySelector('#delete-avatar');
                if (deleteBtn) {
                    deleteBtn.disabled = false;
                }
            }
        });
    }

    // Handle avatar deletion
    const deleteAvatarBtn = document.querySelector('#delete-avatar');
    if (deleteAvatarBtn) {
        deleteAvatarBtn.addEventListener('click', async (e) => {
            e.preventDefault();

            const confirmed = await Swal.fire({
                title: 'Delete Avatar?',
                text: 'Are you sure you want to remove your profile picture?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel',
                customClass: {
                    confirmButton: 'wizard-button red',
                    cancelButton: 'wizard-button button-text'
                }
            });

            if (confirmed.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'delete_avatar');
                formData.append('nonce', window.wizard?.nonce);

                try {
                    const response = await fetch(window.wizard?.ajaxurl || '/api/wiz-ajax/', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();
                    if (!data.success) {
                        throw new Error(data.data || 'Failed to delete avatar');
                    }

                    // Update avatar preview
                    const preview = document.querySelector('#user-avatar-container img');
                    if (preview && data.data.default_avatar) {
                        preview.src = data.data.default_avatar;
                    }

                    // Disable delete button
                    deleteAvatarBtn.disabled = true;

                    wizToast({
                        text: 'Avatar deleted successfully',
                        icon: 'success'
                    });

                } catch (error) {
                    console.error('Delete avatar error:', error);
                    wizToast({
                        text: error.message || 'Failed to delete avatar',
                        icon: 'error'
                    });
                }
            }
        });
    }

    // Add real-time validation
    const emailInput = accountForm.querySelector('#user_email');
    if (emailInput) {
        emailInput.addEventListener('change', () => {
            if (!emailInput.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                wizToast({
                    text: 'Please enter a valid email address',
                    icon: 'error'
                });
                emailInput.focus();
            }
        });
    }
} 