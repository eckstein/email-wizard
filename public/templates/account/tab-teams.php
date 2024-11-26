<?php
$teamsManager = new WizardTeams();
$userTeams = $teamsManager->get_user_teams(get_current_user_id());
$currentTeam = $teamsManager->get_active_team(get_current_user_id());
?>

<div class="wizard-teams-container">
    <div class="wizard-teams-header">
        <button class="wizard-button button-primary new-team">
            <i class="fa-solid fa-plus"></i>&nbsp;&nbsp;Create New Team
        </button>
    </div>

    <?php if (empty($userTeams)): ?>
        <div class="wizard-teams-empty">
            <p>You are not a member of any teams yet.</p>
        </div>
    <?php else: ?>
        <div class="wizard-teams-list">
            <?php foreach ($userTeams as $team): ?>
                <div class="wizard-team-card <?php echo ($team->id == $currentTeam) ? 'active' : ''; ?>">
                    <div class="wizard-team-card-header">
                        <h3 class="wizard-team-name"><?php echo esc_html($team->name); ?></h3>
                        <?php if ($team->role === 'admin'): ?>
                            <span class="wizard-team-role admin">Admin</span>
                        <?php else: ?>
                            <span class="wizard-team-role member">Member</span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($team->description)): ?>
                        <p class="wizard-team-description"><?php echo esc_html($team->description); ?></p>
                    <?php endif; ?>

                    <div class="wizard-team-actions">
                        <?php if ($team->id != $currentTeam): ?>
                            <button class="wizard-button small button-secondary switch-team-trigger"
                                data-team-id="<?php echo esc_attr($team->id); ?>">
                                <i class="fa-solid fa-shuffle"></i>&nbsp;&nbsp;Switch to Team
                            </button>
                        <?php else: ?>
                            <span class="current-team-badge">
                                <i class="fa-solid fa-check"></i>&nbsp;&nbsp;Current Team
                            </span>
                        <?php endif; ?>

                        <?php if ($team->role === 'admin'): ?>
                            <a href="#" class="wizard-button small button-text manage-team-trigger">
                                <i class="fa-solid fa-gear"></i>&nbsp;&nbsp;Manage Team
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>