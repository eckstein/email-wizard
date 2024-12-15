import { handleNewTeam, handleTeamSwitch } from '../handlers/team-actions';
import { handleAjaxForm } from '../../../utils/forms/ajax-handler';
import { wizToast } from '../../../utils/ui/swal2';
import { WizModal } from '../../../utils/ui/modal';
import Swal from 'sweetalert2';

// Initialize character count for description fields
const initDescriptionCharCount = () => {
    document.querySelectorAll('.team-description-input').forEach(textarea => {
        const charCount = textarea.closest('.textarea-wrapper').querySelector('.char-count');
        const maxLength = textarea.getAttribute('maxlength');
        
        // Initial count
        const updateCount = () => {
            const remaining = maxLength - textarea.value.length;
            charCount.textContent = `${remaining}`;
        };
        
        updateCount();
        
        // Update on input
        textarea.addEventListener('input', updateCount);
    });
};

// Handle team settings modal
const handleTeamSettings = async (teamId) => {
    const button = document.querySelector(`.edit-team-trigger[data-team-id="${teamId}"]`);
    let originalContent = '';
    
    if (button) {
        originalContent = button.innerHTML;
        button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>&nbsp;&nbsp;Loading...';
        button.disabled = true;
    }

    try {
        // Fetch modal content
        const response = await fetch(wizard.ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'get_team_settings',
                team_id: teamId,
                nonce: wizard.nonce
            })
        });

        if (!response.ok) {
            throw new Error('Failed to load team settings');
        }

        const result = await response.json();
        if (!result.success) {
            throw new Error(result.data?.message || 'Failed to load team settings');
        }

        if (!result.data?.html) {
            throw new Error('No HTML content received from server');
        }

        // Show modal
        const modal = await WizModal.showModal({
            title: 'Team Settings',
            content: result.data.html,
            width: '800px',
            className: 'team-settings-modal',
            footer: `<button type="submit" form="team-settings-form" class="wizard-button button-primary">
                <i class="fa-solid fa-save"></i>&nbsp;&nbsp;Save Changes
            </button>`,
            onClose: () => {
                // Reset button state when modal is closed
                if (button) {
                    button.innerHTML = originalContent;
                    button.disabled = false;
                }
            }
        });

        // Initialize form handlers in modal
        const form = modal.element.querySelector('.team-edit-form');
        if (form) {
            // Initialize character counter
            initDescriptionCharCount();

            // Initialize avatar handlers
            const avatarInput = form.querySelector('.team-avatar-upload');
            const avatarContainer = form.querySelector(`#team-avatar-${teamId}-container`);
            const deleteAvatarBtn = form.querySelector('.delete-team-avatar');

            if (avatarInput) {
                avatarInput.addEventListener('change', async (e) => {
                    const file = e.target.files[0];
                    if (!file) return;

                    const formData = new FormData();
                    formData.append('action', 'update_team_avatar');
                    formData.append('team_avatar', file);
                    formData.append('team_id', teamId);
                    formData.append('nonce', wizard.nonce);

                    try {
                        const response = await fetch(wizard.ajaxurl, {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();
                        if (!data.success) {
                            throw new Error(data.data?.message || 'Upload failed');
                        }

                        // Update avatar preview
                        if (data.data?.avatar_html && avatarContainer) {
                            avatarContainer.innerHTML = data.data.avatar_html;
                            if (deleteAvatarBtn) {
                                deleteAvatarBtn.style.display = 'inline-flex';
                            }
                        }

                        wizToast({
                            text: 'Avatar updated successfully',
                            icon: 'success'
                        });
                    } catch (error) {
                        console.error('Avatar upload failed:', error);
                        wizToast({
                            text: error.message || 'Upload failed',
                            icon: 'error'
                        });
                    }
                });
            }

            if (deleteAvatarBtn) {
                deleteAvatarBtn.addEventListener('click', async (e) => {
                    e.preventDefault();

                    const confirmed = await Swal.fire({
                        title: 'Delete Team Avatar?',
                        text: 'Are you sure you want to remove the team profile picture?',
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
                        formData.append('action', 'delete_team_avatar');
                        formData.append('team_id', teamId);
                        formData.append('nonce', wizard.nonce);

                        try {
                            const response = await fetch(wizard.ajaxurl, {
                                method: 'POST',
                                body: formData
                            });

                            const data = await response.json();
                            if (!data.success) {
                                throw new Error(data.data?.message || 'Failed to delete team avatar');
                            }

                            // Update avatar preview
                            if (data.data?.avatar_html && avatarContainer) {
                                avatarContainer.innerHTML = data.data.avatar_html;
                                deleteAvatarBtn.style.display = 'none';
                            }

                            wizToast({
                                text: 'Team avatar deleted successfully',
                                icon: 'success'
                            });
                        } catch (error) {
                            console.error('Team avatar deletion failed:', error);
                            wizToast({
                                text: error.message || 'Failed to delete team avatar',
                                icon: 'error'
                            });
                        }
                    }
                });
            }

            // Initialize form
            handleAjaxForm(form, {
                action: 'update_team_settings',
                toast: {
                    success: { 
                        show: true,
                        text: 'Team settings updated successfully',
                        timer: 3000 // Show for 3 seconds
                    },
                    error: { 
                        show: true,
                        text: 'Failed to update team settings' 
                    }
                },
                beforeSubmit: () => {
                    const teamName = form.querySelector('[name="team_name"]');
                    if (!teamName.value.trim()) {
                        wizToast({
                            text: 'Team name is required',
                            icon: 'error'
                        });
                        teamName.focus();
                        return false;
                    }

                    // Check if any roles were changed
                    const changedRoles = form.querySelectorAll('.member-role-select.role-changed');
                    if (changedRoles.length > 0) {
                        // Add a flag to indicate role changes
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'roles_updated';
                        input.value = '1';
                        form.appendChild(input);
                    }

                    return true;
                },
                onSuccess: (response) => {
                    // Clear role-changed flags after successful save
                    form.querySelectorAll('.member-role-select.role-changed').forEach(select => {
                        select.classList.remove('role-changed');

                        // Update the UI to reflect the new role
                        const memberItem = select.closest('.team-member-item');
                        if (memberItem) {
                            const newRole = select.value;
                            
                            // If this was the current user and they removed their admin access
                            const memberId = memberItem.querySelector('[data-member-id]')?.dataset.memberId;
                            const isCurrentUser = memberId === wizard.current_user_id.toString();
                            const lostAdminAccess = isCurrentUser && newRole !== 'admin';

                            if (lostAdminAccess) {
                                // Need to reload as user lost their admin access
                                setTimeout(() => {
                                    window.location.reload();
                                }, 3000); // Wait for 3 seconds to show the success message
                            }
                        }
                    });
                }
            });

            // Initialize member management
            initMemberManagement(modal.element, teamId);
        }

        // Reset button state after modal is successfully shown and initialized
        if (button) {
            button.innerHTML = originalContent;
            button.disabled = false;
        }
    } catch (error) {
        console.error('Error loading team settings:', error);
        wizToast({
            text: error.message || 'Failed to load team settings',
            icon: 'error'
        });
        // Reset button state on error
        if (button) {
            button.innerHTML = originalContent;
            button.disabled = false;
        }
    }
};

// Initialize member management in modal
const initMemberManagement = (modal, teamId) => {
    // Handle member role changes - just track changes, don't submit immediately
    modal.querySelectorAll('.member-role-select').forEach(select => {
        select.addEventListener('change', (e) => {
            // Add a class to mark this select as changed
            select.classList.add('role-changed');
        });
    });

    // Handle member removal
    modal.querySelectorAll('.remove-member-trigger').forEach(btn => {
        btn.addEventListener('click', async () => {
            const memberId = btn.dataset.memberId;
            const memberName = btn.dataset.memberName;

            const confirmed = await Swal.fire({
                title: 'Remove Team Member?',
                text: `Are you sure you want to remove ${memberName} from the team?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, remove',
                cancelButtonText: 'Cancel',
                customClass: {
                    confirmButton: 'wizard-button red',
                    cancelButton: 'wizard-button button-text'
                }
            });

            if (confirmed.isConfirmed) {
                try {
                    const response = await fetch(wizard.ajaxurl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'remove_team_member',
                            team_id: teamId,
                            member_id: memberId,
                            nonce: wizard.nonce
                        })
                    });

                    const result = await response.json();
                    if (result.success) {
                        btn.closest('.team-member-item').remove();
                        wizToast({
                            text: 'Member removed successfully',
                            icon: 'success'
                        });
                    } else {
                        throw new Error(result.data?.message || 'Failed to remove member');
                    }
                } catch (error) {
                    console.error('Error removing member:', error);
                    wizToast({
                        text: error.message || 'Failed to remove member',
                        icon: 'error'
                    });
                }
            }
        });
    });

    // Handle invite sending
    const inviteForm = modal.querySelector('.invite-member-section');
    if (inviteForm) {
        const inviteBtn = inviteForm.querySelector('.invite-member-trigger');
        const emailInput = inviteForm.querySelector('[name="member_email"]');

        inviteBtn.addEventListener('click', async () => {
            const email = emailInput.value.trim();
            if (!email) {
                wizToast({
                    text: 'Please enter an email address',
                    icon: 'error'
                });
                emailInput.focus();
                return;
            }

            try {
                const response = await fetch(wizard.ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'invite_team_member',
                        team_id: teamId,
                        member_email: email,
                        nonce: wizard.nonce
                    })
                });

                const result = await response.json();
                if (result.success) {
                    emailInput.value = '';
                    wizToast({
                        text: 'Invitation sent successfully',
                        icon: 'success'
                    });
                    
                    // Refresh the modal content instead of reloading the page
                    const modalResponse = await fetch(wizard.ajaxurl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'load_team_settings',
                            team_id: teamId,
                            nonce: wizard.nonce
                        })
                    });

                    const modalResult = await modalResponse.json();
                    if (modalResult.success) {
                        const modalContent = modal.querySelector('.wizard-modal-content');
                        modalContent.innerHTML = modalResult.data.html;
                        // Reinitialize event handlers for the new content
                        initMemberManagement(modal, teamId);
                    }
                } else {
                    throw new Error(result.data?.message || 'Failed to send invitation');
                }
            } catch (error) {
                console.error('Error sending invitation:', error);
                wizToast({
                    text: error.message || 'Failed to send invitation',
                    icon: 'error'
                });
            }
        });
    }

    // Handle invite management
    modal.querySelectorAll('.resend-invite-trigger').forEach(btn => {
        btn.addEventListener('click', async () => {
            const inviteId = btn.dataset.inviteId;

            try {
                const response = await fetch(wizard.ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'resend_team_invite',
                        team_id: teamId,
                        invite_id: inviteId,
                        nonce: wizard.nonce
                    })
                });

                const result = await response.json();
                if (result.success) {
                    wizToast({
                        text: 'Invitation resent successfully',
                        icon: 'success'
                    });
                } else {
                    throw new Error(result.data?.message || 'Failed to resend invitation');
                }
            } catch (error) {
                console.error('Error resending invitation:', error);
                wizToast({
                    text: error.message || 'Failed to resend invitation',
                    icon: 'error'
                });
            }
        });
    });

    modal.querySelectorAll('.revoke-invite-trigger').forEach(btn => {
        btn.addEventListener('click', async () => {
            const inviteId = btn.dataset.inviteId;

            const confirmed = await Swal.fire({
                title: 'Revoke Invitation?',
                text: 'Are you sure you want to revoke this invitation?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, revoke',
                cancelButtonText: 'Cancel',
                customClass: {
                    confirmButton: 'wizard-button red',
                    cancelButton: 'wizard-button button-text'
                }
            });

            if (confirmed.isConfirmed) {
                try {
                    const response = await fetch(wizard.ajaxurl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'revoke_team_invite',
                            team_id: teamId,
                            invite_id: inviteId,
                            nonce: wizard.nonce
                        })
                    });

                    const result = await response.json();
                    if (result.success) {
                        btn.closest('.team-member-item').remove();
                        wizToast({
                            text: 'Invitation revoked successfully',
                            icon: 'success'
                        });
                    } else {
                        throw new Error(result.data?.message || 'Failed to revoke invitation');
                    }
                } catch (error) {
                    console.error('Error revoking invitation:', error);
                    wizToast({
                        text: error.message || 'Failed to revoke invitation',
                        icon: 'error'
                    });
                }
            }
        });
    });
};

// Initialize all team UI components
export function initTeamList() {
    // Initialize new team button
    const newTeamBtn = document.querySelector('.new-team');
    if (newTeamBtn) {
        newTeamBtn.addEventListener('click', handleNewTeam);
    }

    // Initialize team switching
    document.querySelectorAll('.switch-team-trigger').forEach(btn => {
        btn.addEventListener('click', handleTeamSwitch);
    });

    // Initialize team settings
    document.querySelectorAll('.edit-team-trigger').forEach(btn => {
        btn.addEventListener('click', () => handleTeamSettings(btn.dataset.teamId));
    });

    // Initialize team avatar handling
    document.querySelectorAll('input[name="team_avatar"]').forEach(input => {
        input.addEventListener('change', function() {
            const form = this.closest('form');
            if (form) {
                form.submit();
            }
        });
    });

    // Initialize team avatar deletion
    document.querySelectorAll('.delete-team-avatar').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            
            const confirmed = await Swal.fire({
                title: 'Delete Team Avatar?',
                text: 'Are you sure you want to remove the team avatar?',
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
                const form = btn.closest('form');
                if (form) {
                    form.submit();
                }
            }
        });
    });
} 