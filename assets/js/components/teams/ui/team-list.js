import { handleNewTeam, handleTeamSwitch } from '../handlers/team-actions';
import { handleAjaxForm } from '../../../utils/forms/ajax-handler';
import { wizToast } from '../../../utils/ui/swal2';

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

// Initialize all team UI components
export function initTeamList() {
    initDescriptionCharCount();
    // Initialize new team button
    const newTeamBtn = document.querySelector('.new-team');
    if (newTeamBtn) {
        newTeamBtn.addEventListener('click', handleNewTeam);
    }

    // Initialize team switching
    document.querySelectorAll('.switch-team-trigger').forEach(btn => {
        btn.addEventListener('click', handleTeamSwitch);
    });

    // Initialize team settings forms
    document.querySelectorAll('.team-edit-form').forEach(form => {
        handleAjaxForm(form, {
            action: 'update_team_settings',
            toast: {
                success: { 
                    show: true,
                    text: 'Team settings updated successfully' 
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
                return true;
            }
        });
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