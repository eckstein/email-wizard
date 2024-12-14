<?php
// Display any stored messages
if (isset($_SESSION['wizard_account_messages'])) {
    foreach ($_SESSION['wizard_account_messages'] as $message) {
        echo '<div class="wizard-message ' . esc_attr($message['type']) . '">' .
            esc_html($message['text']) .
            '</div>';
    }
    // Clear messages after displaying
    unset($_SESSION['wizard_account_messages']);
}
?>

<div class="wizard-form-content">
    <form class="wizard-form account-settings-form">
        <?php wp_nonce_field('wizard_update_account', 'wizard_update_account_nonce'); ?>
        <input type="hidden" name="MAX_FILE_SIZE" value="5242880">

        <div class="wizard-form-fieldgroup">
            <div class="wizard-form-fieldgroup-label">Profile Picture</div>
            <div class="wizard-form-fieldgroup-value">
                <div class="avatar-section">
                    <div class="current-avatar" id="user-avatar-container">
                        <?php
                        $avatar = new WizardAvatar();
                        echo $avatar->get_avatar(96);
                        ?>
                    </div>
                    <div class="avatar-controls">
                        <label for="avatar-upload" class="wizard-button button-secondary">
                            <i class="fa-solid fa-upload"></i>&nbsp;&nbsp;Upload new
                            <input type="file"
                                name="avatar"
                                id="avatar-upload"
                                accept="image/jpeg,image/png,image/gif"
                                data-max-size="5242880">
                        </label>
                        <button type="submit"
                            name="delete_avatar"
                            value="1"
                            id="delete-avatar"
                            class="wizard-button small red button-text delete-avatar"
                            title="Remove avatar"
                            <?php echo !$avatar->has_custom_avatar() ? 'disabled' : ''; ?>>
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                        <p class="field-description">Maximum file size: 5MB. Supported formats: JPEG, PNG, GIF.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="wizard-form-fieldgroup">
            <div class="wizard-form-fieldgroup-label">First Name</div>
            <div class="wizard-form-fieldgroup-value">
                <input type="text" id="first_name" name="first_name"
                    value="<?php echo esc_attr(get_user_meta(get_current_user_id(), 'first_name', true)); ?>">
            </div>
        </div>

        <div class="wizard-form-fieldgroup">
            <div class="wizard-form-fieldgroup-label">Last Name</div>
            <div class="wizard-form-fieldgroup-value">
                <input type="text" id="last_name" name="last_name"
                    value="<?php echo esc_attr(get_user_meta(get_current_user_id(), 'last_name', true)); ?>">
            </div>
        </div>

        <div class="wizard-form-fieldgroup">
            <div class="wizard-form-fieldgroup-label">Display Name</div>
            <div class="wizard-form-fieldgroup-value">
                <input type="text" id="display_name" name="display_name"
                    value="<?php echo esc_attr(wp_get_current_user()->display_name); ?>">
            </div>
        </div>

        <div class="wizard-form-fieldgroup">
            <div class="wizard-form-fieldgroup-label">Email Address</div>
            <div class="wizard-form-fieldgroup-value">
                <input type="email" id="user_email" name="user_email"
                    value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>">
            </div>
        </div>

        <h3 class="wizard-form-section-title">Change Password</h3>

        <div class="wizard-form-fieldgroup">
            <div class="wizard-form-fieldgroup-label">Current Password</div>
            <div class="wizard-form-fieldgroup-value">
                <input type="password" id="current_password" name="current_password" autocomplete="current-password">
            </div>
        </div>

        <div class="wizard-form-fieldgroup">
            <div class="wizard-form-fieldgroup-label">New Password</div>
            <div class="wizard-form-fieldgroup-value">
                <input type="password" id="new_password" name="new_password" autocomplete="new-password">
            </div>
        </div>

        <div class="wizard-form-fieldgroup">
            <div class="wizard-form-fieldgroup-label">Confirm Password</div>
            <div class="wizard-form-fieldgroup-value">
                <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password">
            </div>
        </div>

        <div class="wizard-form-fieldgroup">
            <div class="wizard-form-fieldgroup-label"></div>
            <div class="wizard-form-fieldgroup-value">
                <button type="submit" class="wizard-button button-primary">Save Changes</button>
            </div>
        </div>
    </form>
</div>