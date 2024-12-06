<?php
/**
 * Template for displaying single template
 */

get_header();

// The auth check is now handled automatically by WizardAuth
// No need for manual auth checks here anymore

$template = new WizardTemplate(get_the_ID());
?>

<div class="wrap wiz-template-single">
    <?php echo $template->render(); ?>
</div>

<?php get_footer(); ?>