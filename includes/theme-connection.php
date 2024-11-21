<?php
// Theme header wizard and account menu
add_action('wizard_header_secondary_nav', 'generate_wizard_app_nav_menu');

function generate_wizard_app_nav_menu()
{
?>
    <nav id="user-menu" class="header-menu" role="navigation" itemscope
        itemtype="https://schema.org/SiteNavigationElement">
        <?php if (is_user_logged_in()) : ?>
            <ul>
                <li><button class="wizard-button new-template"><i class="fa-solid fa-plus"></i>&nbsp;&nbsp;New
                        Template</button>
                </li>
                <li><button class="wizard-button new-team"><i class="fa-solid fa-plus"></i>&nbsp;&nbsp;New
                        Team</button>
                </li>
                <li id="user-dropdown" class="wizard-slideover-trigger">
                    <div class="wizard-avatar"><?php echo get_first_letter_of_nicename(); ?></div>
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

function get_first_letter_of_nicename()
{
    $currentUser = wp_get_current_user();
    $firstLetter = strtoupper(substr($currentUser->display_name, 0, 1));
    return $firstLetter;
}

add_action('wizard_inside_container_end', 'generate_wizard_slideover');


function generate_wizard_slideover()
{
    $currentUser = wp_get_current_user();
    $teamsManager = new WizardTeams();
    $userTeams = $teamsManager->get_user_teams($currentUser->ID);
    $currentTeam = $teamsManager->get_active_team($currentUser->ID);
    $slideOverHtml = '
    <div class="slideover-wrapper wizard-slideover">
        <div class="slideover-header">
            <div class="slideover-title">Account and Teams</div>
            <div class="slideover-close">✕</div>
        </div>
    
        <div class="slideover-account">
            <div class="wizard-avatar">' . get_first_letter_of_nicename() . '</div>
            <div class="slideover-user-info">
                <div class="slideover-username">' . $currentUser->display_name . '</div>
                <div class="slideover-email">' . $currentUser->user_email . '</div>
            </div>
            <div class="slideover-settings"><i class="fa-solid fa-gear"></i></div>
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
            <button class="slideover-signout wizard-button small red">Sign out</button>
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
