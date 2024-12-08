import { initAvatarHandlers } from '../avatar/avatar-handler.js';

/**
 * Initialize account page functionality
 */
export function initAccountPage() {
    // Only initialize on account page
    const accountPage = document.getElementById('account-page-ui');
    if (!accountPage) return;

    // Initialize user avatar handling
    const userAvatarInput = document.getElementById('avatar-upload');
    const userAvatarContainer = document.getElementById('user-avatar-container');
    const deleteUserAvatarBtn = document.getElementById('delete-avatar');

    if (userAvatarInput && userAvatarContainer) {
        initAvatarHandlers({
            userAvatarInput,
            userAvatarContainer,
            deleteUserAvatarBtn,
            nonce: window.wizard?.nonce
        });
    }

    // Initialize team avatar handling
    const teamAvatarInputs = Array.from(document.querySelectorAll('input[name="team_avatar"]'));
    const teamAvatarContainers = teamAvatarInputs.map(input => 
        document.getElementById(`team-avatar-${input.dataset.teamId}-container`)
    );
    const deleteTeamAvatarBtns = Array.from(document.querySelectorAll('.delete-team-avatar'));

    if (teamAvatarInputs.length) {
        initAvatarHandlers({
            teamAvatarInputs,
            teamAvatarContainers,
            deleteTeamAvatarBtns,
            nonce: window.wizard?.nonce
        });
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAccountPage);
} else {
    initAccountPage();
} 