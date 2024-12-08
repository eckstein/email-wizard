import { showSuccessToast, showErrorToast } from "../../utils/notification-utils";

const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB in bytes
const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif'];

/**
 * Validate file before upload
 * @param {File} file - The file to validate
 * @returns {boolean} - Whether the file is valid
 */
function validateFile(file) {
    if (file.size > MAX_FILE_SIZE) {
        showErrorToast(`File size must be less than ${MAX_FILE_SIZE / (1024 * 1024)}MB`);
        return false;
    }

    if (!ALLOWED_TYPES.includes(file.type)) {
        showErrorToast('Only JPG, PNG and GIF files are allowed');
        return false;
    }

    return true;
}

/**
 * Upload avatar using FormData
 * @param {FormData} formData - The form data to send
 * @returns {Promise} - The fetch promise
 */
async function uploadAvatar(formData) {
    try {
        const response = await fetch(wizard.ajaxurl, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.data || 'Upload failed');
        }

        showSuccessToast(data.data.message);
        return data.data;
    } catch (error) {
        showErrorToast(error.message);
        throw error;
    }
}

/**
 * Initialize avatar upload handlers
 * @param {Object} options - Configuration options
 */
export function initAvatarHandlers({ 
    userAvatarInput, 
    teamAvatarInputs, 
    userAvatarContainer, 
    teamAvatarContainers,
    deleteUserAvatarBtn,
    deleteTeamAvatarBtns,
    nonce
}) {
    // Handle user avatar upload
    if (userAvatarInput) {
        userAvatarInput.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file || !validateFile(file)) return;

            const formData = new FormData();
            formData.append('action', 'update_user_avatar');
            formData.append('avatar', file);
            formData.append('nonce', nonce);
            formData.append('user_id', wizard.current_user_id);

            try {
                const data = await uploadAvatar(formData);
                if (data.avatar_html && userAvatarContainer) {
                    userAvatarContainer.innerHTML = data.avatar_html;
                }
            } catch (error) {
                console.error('Avatar upload failed:', error);
            }
        });
    }

    // Handle user avatar deletion
    if (deleteUserAvatarBtn) {
        deleteUserAvatarBtn.addEventListener('click', async (e) => {
            e.preventDefault();

            const formData = new FormData();
            formData.append('action', 'delete_user_avatar');
            formData.append('nonce', nonce);
            formData.append('user_id', wizard.current_user_id);

            try {
                const data = await uploadAvatar(formData);
                if (data.avatar_html && userAvatarContainer) {
                    userAvatarContainer.innerHTML = data.avatar_html;
                }
                deleteUserAvatarBtn.disabled = true;
            } catch (error) {
                console.error('Avatar deletion failed:', error);
            }
        });
    }

    // Handle team avatar uploads
    if (teamAvatarInputs) {
        teamAvatarInputs.forEach((input, index) => {
            input.addEventListener('change', async (e) => {
                const file = e.target.files[0];
                if (!file || !validateFile(file)) return;

                const teamId = input.dataset.teamId;
                const formData = new FormData();
                formData.append('action', 'update_team_avatar');
                formData.append('team_avatar', file);
                formData.append('team_id', teamId);
                formData.append('nonce', nonce);
                formData.append('user_id', wizard.current_user_id);

                try {
                    const data = await uploadAvatar(formData);
                    if (data.avatar_html && teamAvatarContainers[index]) {
                        teamAvatarContainers[index].innerHTML = data.avatar_html;
                    }
                } catch (error) {
                    console.error('Team avatar upload failed:', error);
                }
            });
        });
    }

    // Handle team avatar deletions
    if (deleteTeamAvatarBtns) {
        deleteTeamAvatarBtns.forEach((btn, index) => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();

                const teamId = btn.dataset.teamId;
                const formData = new FormData();
                formData.append('action', 'delete_team_avatar');
                formData.append('team_id', teamId);
                formData.append('nonce', nonce);
                formData.append('user_id', wizard.current_user_id);

                try {
                    const data = await uploadAvatar(formData);
                    if (data.avatar_html && teamAvatarContainers[index]) {
                        teamAvatarContainers[index].innerHTML = data.avatar_html;
                    }
                    btn.style.display = 'none';
                } catch (error) {
                    console.error('Team avatar deletion failed:', error);
                }
            });
        });
    }
} 