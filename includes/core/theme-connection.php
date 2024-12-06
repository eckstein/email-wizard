<?php

// Hide main nav menu items if user is not logged in
add_filter('wp_nav_menu_items', 'hide_main_nav_menu_items_if_not_logged_in', 10, 2);
function hide_main_nav_menu_items_if_not_logged_in($items, $args)
{
    if (!is_user_logged_in()) {
        $items = '';
    }
    return $items;
}

// Theme header wizard and account menu
add_action('wizard_header_secondary_nav', 'generate_wizard_app_nav_menu');

function generate_wizard_app_nav_menu()
{
?>
    <nav id="user-menu" class="header-menu" role="navigation" itemscope
        itemtype="https://schema.org/SiteNavigationElement">
        <?php if (is_user_logged_in()) : ?>
            <ul>
                <li id="user-dropdown" class="wizard-slideover-trigger">
                    <div class="wizard-avatar">
                        <?php 
                        $avatar = new WizardAvatar();
                        echo $avatar->get_avatar(32);
                        ?>
                    </div>
                    <div class="user-team-name">
                        <?php
                        $teamsManager = new WizardTeams();
                        $currentTeamID = $teamsManager->get_active_team(get_current_user_id());
                        $currentTeam = $teamsManager->get_team($currentTeamID);
                        echo $currentTeam->name;
                        ?>
                    </div>
                </li>
            </ul>
        <?php else : ?>
            <ul>
                <li class="nav-button"><a href="<?php echo esc_url(home_url('/get-started/')); ?>">Get
                        Started</a></li>
                <li><a href="<?php echo esc_url(wp_login_url()); ?>">Sign In</a></li>
            </ul>
        <?php endif; ?>
    </nav>
<?php
}


add_action('wizard_inside_container_end', 'generate_wizard_slideover');


function generate_wizard_slideover()
{
    $currentUser = wp_get_current_user();
    $teamsManager = new WizardTeams();
    $userTeams = $teamsManager->get_user_teams($currentUser->ID);
    $currentTeam = $teamsManager->get_active_team($currentUser->ID);
    $accountPageUrl = get_bloginfo('url') . '/account';
    $wizardAvatar = new WizardAvatar();
    $slideOverHtml = '
    <div class="slideover-wrapper wizard-slideover">
        <div class="slideover-header">
            <div class="slideover-title">Account and Teams</div>
            <div class="slideover-close">✕</div>
        </div>
    
        <div class="slideover-account">
            <div class="wizard-avatar">' . $wizardAvatar->get_avatar() . '</div>
            <div class="slideover-user-info">
                <div class="slideover-username">' . $currentUser->display_name . '</div>
                <div class="slideover-email">' . $currentUser->user_email . '</div>
            </div>
            <div class="slideover-settings"><a href="' . esc_url($accountPageUrl) . '"><i class="fa-solid fa-gear"></i></a></div>
        </div>
    
        <div class="slideover-teams">
            <div class="slideover-teams-header">
                <div class="slideover-teams-title">Teams</div>
                <button class="wizard-button small new-team"><i class="fa-solid fa-plus"></i>&nbsp;&nbsp;New Team</button>
            </div>
        
            <div class="slideover-teams-list">';
    foreach ($userTeams as $team) {
        $itemActive = ($team->id == $currentTeam) ? '--active' : '';
        $slideOverHtml .= '<div class="slideover-team-item switch-team-trigger '.$itemActive. '" data-team-id="' . $team->id . '">' . $team->name . '</div>';
    }
    $slideOverHtml .= '
            </div>
        </div>
    
        <div class="slideover-footer">
            <a href="' . esc_url(wp_logout_url()) . '" class="slideover-signout wizard-button small red">Sign out</a>
            <div class="slideover-footer-links">
                <a href="#" class="slideover-link">Terms</a>
                <a href="#" class="slideover-link">Privacy</a>
                <a href="' . esc_url(home_url('/templates?folder_id=trash')) . '" class="slideover-link">Template Trash</a>
            </div>
        </div>
    </div>

    ';
    echo $slideOverHtml;
}
