<?php
function wizard_account_form_redirects() {
    global $wizard_account_page;
    // Only run on account page
    if (!$wizard_account_page) {
        return;
    }

    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
} 