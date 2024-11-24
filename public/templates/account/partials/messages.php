<?php
if (isset($_SESSION['wizard_account_messages']) && !empty($_SESSION['wizard_account_messages'])) {
    foreach ($_SESSION['wizard_account_messages'] as $message) {
        ?>
        <div class="wizard-message wizard-message-<?php echo esc_attr($message['type']); ?>">
            <?php echo esc_html($message['text']); ?>
        </div>
        <?php
    }
    unset($_SESSION['wizard_account_messages']);
}
?> 