<?php
/**
 * Template Name: Account Page
 */

get_header();

// The auth check is now handled automatically by WizardAuth
// No need for manual auth checks here anymore

$accountManager = new WizardAccount();
$accountManager->init(); // Process any form submission
?>
<div id="primary" class="content-area">
	<main id="main" class="site-main">
		<div id="account-page-ui">
			<?php 
			include(plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/account/partials/messages.php');
			echo $accountManager->render(); 
			?>
		</div>
	</main>
</div>
<?php
get_footer();
