import { initTeamList } from '../teams/ui/team-list';

/**
 * Initialize teams functionality
 */
export function initTeams() {
    const teamsContainer = document.querySelector('.wizard-teams-container');
    if (!teamsContainer) return;

    // Initialize team list
    initTeamList();
} 