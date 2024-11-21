<?php
/**
 * Template Name: Template Archive
 */

get_header();

// Get current folder ID from URL using consistent parameter
$current_folder_id = isset($_GET['folder_id']) ? sanitize_text_field($_GET['folder_id']) : 'root';
$current_user_id = get_current_user_id();

error_log('Archive Template - Current Folder ID: ' . $current_folder_id);
error_log('Archive Template - Current User ID: ' . $current_user_id);

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