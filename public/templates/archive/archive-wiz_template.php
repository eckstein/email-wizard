<?php
/**
 * Template Name: Template Archive
 */

get_header();

// The auth check is now handled automatically by WizardAuth
// No need for manual auth checks here anymore

// Continue with existing code for logged-in users
$current_folder_id = isset($_GET['folder_id']) ? sanitize_text_field($_GET['folder_id']) : 'root';
$current_user_id = get_current_user_id();

// Initialize the template table
$template_table = new WizardTemplateTable(
    $current_user_id, 
    $current_folder_id,
    array(
        'orderby' => isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'last_updated',
        'order' => isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC',
        'per_page' => 10
    )
);
?>

<div class="wrap wiz-template-archive">
    <?php echo $template_table->render('full'); ?>
</div>

<?php get_footer(); ?>