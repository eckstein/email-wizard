<?php

// Redirect non-logged in users to login page
if (!is_user_logged_in()) {
    wp_safe_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header();

$accountManager = new WizardAccount();
?>
<div id="primary" class="content-area">
	<main id="main" class="site-main">
		<div id="account-page-ui">
			<?php echo $accountManager->render(); ?>
		</div>
	</main>
</div>
<?php
get_footer();
