<?php
$teamsManager = new WizardTeams();
$userTeams = $teamsManager->get_user_teams(get_current_user_id());
$currentTeam = $teamsManager->get_active_team(get_current_user_id());
$showMemberManagement = isset($_GET['manage_members']) && $_GET['manage_members'] === '1';

// Include messages partial
include(plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/account/partials/messages.php');
?>

<div class="wizard-teams-container">
    <?php if ($showMemberManagement): ?>
        <div class="wizard-teams-header">
            <a href="<?php echo remove_query_arg('manage_members'); ?>" class="wizard-button">
                <i class="fa-solid fa-arrow-left"></i>&nbsp;&nbsp;Back to Teams
            </a>
        </div>

        <div class="team-members-management">
            <h2>Manage Team Members</h2>
            
            <?php 
            $teamMembers = $teamsManager->get_team_members($currentTeam);
            $pendingInvites = $teamsManager->get_team_invites($currentTeam);
            
            if (!empty($teamMembers) || !empty($pendingInvites)): ?>
                <div class="team-members-list">
                    <?php 
                    // Show active members
                    foreach ($teamMembers as $member): ?>
                        <div class="team-member-item">
                            <div class="member-info">
                                <?php echo get_avatar($member->ID, 40); ?>
                                <div class="member-details">
                                    <span class="member-name"><?php echo esc_html($member->display_name); ?></span>
                                    <span class="member-email"><?php echo esc_html($member->user_email); ?></span>
                                </div>
                            </div>
                            
                            <?php if ($member->ID !== get_current_user_id()): ?>
                                <div class="member-actions">
                                    <form class="member-role-form">
                                        <?php wp_nonce_field('wizard_update_member_role', 'wizard_update_member_role_nonce'); ?>
                                        <input type="hidden" name="team_id" value="<?php echo esc_attr($currentTeam); ?>">
                                        <input type="hidden" name="user_id" value="<?php echo esc_attr($member->ID); ?>">
                                        
                                        <select name="member_role" class="member-role-select">
                                            <option value="member" <?php selected($member->role, 'member'); ?>>Member</option>
                                            <option value="admin" <?php selected($member->role, 'admin'); ?>>Admin</option>
                                        </select>
                                        
                                        <button type="submit" class="wizard-button small">Update Role</button>
                                    </form>

                                    <form class="member-remove-form">
                                        <?php wp_nonce_field('wizard_remove_team_member', 'wizard_remove_team_member_nonce'); ?>
                                        <input type="hidden" name="team_id" value="<?php echo esc_attr($currentTeam); ?>">
                                        <input type="hidden" name="user_id" value="<?php echo esc_attr($member->ID); ?>">
                                        
                                        <button type="submit" class="wizard-button small red" 
                                            onclick="return confirm('Are you sure you want to remove this member from the team?');">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="member-role admin">Team Owner</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <?php 
                    // Show pending invites
                    foreach ($pendingInvites as $invite): ?>
                        <div class="team-member-item pending">
                            <div class="member-info">
                                <div class="member-avatar pending">
                                    <i class="fa-solid fa-envelope"></i>
                                </div>
                                <div class="member-details">
                                    <span class="member-email"><?php echo esc_html($invite->email); ?></span>
                                    <span class="member-status pending">Pending Invite</span>
                                    <span class="member-invite-expires">Expires: <?php echo human_time_diff(strtotime($invite->expires_at)); ?></span>
                                </div>
                            </div>
                            
                            <div class="member-actions">
                                <form class="resend-invite-form">
                                    <?php wp_nonce_field('wizard_resend_invite', 'wizard_resend_invite_nonce'); ?>
                                    <input type="hidden" name="team_id" value="<?php echo esc_attr($currentTeam); ?>">
                                    <input type="hidden" name="invite_id" value="<?php echo esc_attr($invite->id); ?>">
                                    
                                    <button type="submit" class="wizard-button small">
                                        <i class="fa-solid fa-paper-plane"></i>&nbsp;&nbsp;Resend
                                    </button>
                                </form>

                                <form class="revoke-invite-form">
                                    <?php wp_nonce_field('wizard_revoke_team_invite', 'wizard_revoke_team_invite_nonce'); ?>
                                    <input type="hidden" name="team_id" value="<?php echo esc_attr($currentTeam); ?>">
                                    <input type="hidden" name="invite_id" value="<?php echo esc_attr($invite->id); ?>">
                                    
                                    <button type="submit" class="wizard-button small red">
                                        <i class="fa-solid fa-xmark"></i>&nbsp;&nbsp;Revoke
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No team members found.</p>
            <?php endif; ?>

            <div class="invite-member-section">
                <h3>Invite New Member</h3>
                <form class="invite-member-form wizard-form">
                    <?php wp_nonce_field('wizard_invite_team_member', 'wizard_invite_team_member_nonce'); ?>
                    <input type="hidden" name="team_id" value="<?php echo esc_attr($currentTeam); ?>">
                    
                    <div class="wizard-form-fieldgroup">
                        <div class="wizard-form-fieldgroup-value">
                            <input type="email" 
                                name="member_email" 
                                placeholder="Enter email address" 
                                required>
                            <button type="submit" class="wizard-button button-primary">
                                <i class="fa-solid fa-paper-plane"></i>&nbsp;&nbsp;Send Invite
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    <?php else: ?>
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
                        <div class="wizard-team-card-content">
                            <div class="wizard-team-avatar">
                                <?php echo $teamsManager->get_team_avatar($team->id, 64); ?>
                            </div>
                            
                            <div class="wizard-team-info">
                                <div class="wizard-team-card-header">
                                    <h3 class="wizard-team-name"><?php echo esc_html($team->name); ?></h3>
                                    <span class="wizard-team-role <?php echo $team->role; ?>">
                                        <?php echo ucfirst($team->role); ?>
                                    </span>
                                </div>

                                <?php if (!empty($team->description)): ?>
                                    <p class="wizard-team-description"><?php echo esc_html($team->description); ?></p>
                                <?php endif; ?>

                                <div class="wizard-team-actions">
                                    <?php if ($team->id == $currentTeam): ?>
                                        <span class="current-team-badge">
                                            <i class="fa-solid fa-check"></i>&nbsp;&nbsp;Current Team
                                        </span>
                                        <?php if ($team->role === 'admin'): ?>
                                            <button type="button" class="wizard-button small button-text edit-team-trigger"
                                                data-team-id="<?php echo esc_attr($team->id); ?>">
                                                <i class="fa-solid fa-gear"></i>&nbsp;&nbsp;Team Settings
                                            </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <button type="button" class="wizard-button small button-secondary switch-team-trigger"
                                            data-team-id="<?php echo esc_attr($team->id); ?>">
                                            <i class="fa-solid fa-shuffle"></i>&nbsp;&nbsp;Switch to Team
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>