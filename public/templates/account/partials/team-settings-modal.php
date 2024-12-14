<?php
/**
 * Team Settings Modal Template
 * 
 * This template is loaded via AJAX when clicking the Team Settings button
 */

if (!defined('ABSPATH')) exit;

$team = $args['team'] ?? null;
if (!$team) return;

$teamsManager = new WizardTeams();
?>

<form id="team-settings-form" class="team-edit-form" enctype="multipart/form-data">
    <?php wp_nonce_field('wizard_update_team_settings', 'wizard_update_team_settings_nonce'); ?>
    <input type="hidden" name="team_id" value="<?php echo esc_attr($team->id); ?>">
    <input type="hidden" name="MAX_FILE_SIZE" value="5242880">

    <div class="wizard-form-content">
        <div class="wizard-form-section">
            <h3 class="wizard-form-section-title">Team Profile</h3>
            
            <div class="wizard-form-fieldgroup">
                <div class="wizard-form-fieldgroup-label">Team Avatar</div>
                <div class="wizard-form-fieldgroup-value">
                    <div class="avatar-section">
                        <div class="current-avatar" id="team-avatar-<?php echo $team->id; ?>-container">
                            <?php echo $teamsManager->get_team_avatar($team->id, 96); ?>
                        </div>
                        <div class="avatar-controls">
                            <label for="team-avatar-<?php echo $team->id; ?>" class="wizard-button button-secondary">
                                <i class="fa-solid fa-upload"></i>&nbsp;&nbsp;Upload new
                                <input type="file" 
                                    name="team_avatar" 
                                    id="team-avatar-<?php echo $team->id; ?>" 
                                    data-team-id="<?php echo esc_attr($team->id); ?>"
                                    accept="image/jpeg,image/png,image/gif"
                                    data-max-size="5242880"
                                    class="team-avatar-upload">
                            </label>
                            <?php if ($teamsManager->has_team_avatar($team->id)): ?>
                                <button type="button" 
                                    class="wizard-button small red button-text delete-team-avatar"
                                    data-team-id="<?php echo esc_attr($team->id); ?>"
                                    title="Remove team avatar">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            <?php endif; ?>
                            <p class="field-description">Maximum file size: 5MB. Supported formats: JPEG, PNG, GIF.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="wizard-form-fieldgroup">
                <div class="wizard-form-fieldgroup-label">Team Name</div>
                <div class="wizard-form-fieldgroup-value">
                    <input type="text" 
                        name="team_name" 
                        class="team-name-input" 
                        value="<?php echo esc_attr($team->name); ?>"
                        required>
                </div>
            </div>

            <div class="wizard-form-fieldgroup">
                <div class="wizard-form-fieldgroup-label">Description</div>
                <div class="wizard-form-fieldgroup-value">
                    <div class="textarea-wrapper">
                        <textarea name="team_description" 
                            class="team-description-input" 
                            maxlength="500"
                            placeholder="Add a team description..."><?php echo esc_textarea($team->description); ?></textarea>
                        <div class="char-count"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="wizard-form-section">
            <h3 class="wizard-form-section-title">Team Members</h3>
            <?php 
            $teamMembers = $teamsManager->get_team_members($team->id);
            $pendingInvites = $teamsManager->get_team_invites($team->id);
            
            if (!empty($teamMembers) || !empty($pendingInvites)): ?>
                <div class="team-members-list">
                    <?php foreach ($teamMembers as $member): ?>
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
                                    <select name="member_role[<?php echo $member->ID; ?>]" class="member-role-select">
                                        <option value="member" <?php selected($member->role, 'member'); ?>>Member</option>
                                        <option value="admin" <?php selected($member->role, 'admin'); ?>>Admin</option>
                                    </select>
                                    
                                    <button type="button" 
                                        class="wizard-button small red button-text remove-member-trigger"
                                        data-member-id="<?php echo esc_attr($member->ID); ?>"
                                        data-member-name="<?php echo esc_attr($member->display_name); ?>"
                                        title="Remove member">
                                        <i class="fa-solid fa-user-minus"></i>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <?php foreach ($pendingInvites as $invite): ?>
                        <div class="team-member-item pending">
                            <div class="member-info">
                                <div class="member-avatar pending">
                                    <i class="fa-solid fa-envelope"></i>
                                </div>
                                <div class="member-details">
                                    <span class="member-email"><?php echo esc_html($invite->email); ?></span>
                                    <span class="member-status">Invitation pending</span>
                                </div>
                            </div>
                            <div class="member-actions">
                                <button type="button" 
                                    class="wizard-button small button-text resend-invite-trigger"
                                    data-invite-id="<?php echo esc_attr($invite->id); ?>"
                                    title="Resend invitation">
                                    <i class="fa-solid fa-paper-plane"></i>
                                </button>
                                <button type="button" 
                                    class="wizard-button small red button-text revoke-invite-trigger"
                                    data-invite-id="<?php echo esc_attr($invite->id); ?>"
                                    title="Revoke invitation">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-members-message">No team members found.</p>
            <?php endif; ?>

            <div class="invite-member-section">
                <div class="wizard-form-fieldgroup">
                    <div class="wizard-form-fieldgroup-label">Invite New Member</div>
                    <div class="wizard-form-fieldgroup-value">
                        <input type="email" 
                            name="member_email" 
                            placeholder="Enter email address">
                        <button type="button" class="wizard-button invite-member-trigger">
                            <i class="fa-solid fa-paper-plane"></i>&nbsp;&nbsp;Send Invite
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form> 