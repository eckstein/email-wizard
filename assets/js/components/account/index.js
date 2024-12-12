import { initAccountSettings } from './settings';
import { initTeams } from './teams';

/**
 * Initialize account page functionality
 */
export function initAccountPage() {
    // Only initialize on account page
    const accountPage = document.getElementById('account-page-ui');
    if (!accountPage) return;

    // Initialize account settings
    initAccountSettings();

    // Initialize teams functionality
    initTeams();
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAccountPage);
} else {
    initAccountPage();
} 