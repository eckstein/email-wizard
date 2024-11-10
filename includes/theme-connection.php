<?php
// Theme header wizard and account menu
add_action('wizard_header_secondary_nav', 'generate_wizard_app_nav_menu');

function generate_wizard_app_nav_menu() {
?>
    <nav id="user-menu" class="header-menu" role="navigation" itemscope
        itemtype="https://schema.org/SiteNavigationElement">
        <?php if ( is_user_logged_in() ) : ?>
            <ul>
                <li><button class="wizard-button new-template"><i class="fa-solid fa-plus"></i>&nbsp;&nbsp;New
                        Template</button>
                </li>
                <li id="user-dropdown" class="wizard-dropdown">
                    <i class="fa-2x fa-regular fa-circle-user"></i>
                    <div class="wizard-dropdown-panel" id="user-dropdown-panel">
                        <ul class="wizard-dropdown-menu">
                            <li><a href="<?php echo esc_url( home_url( '/account/' ) ); ?>"><i class="fa-solid fa-user"></i>&nbsp;&nbsp;Account</a></li>
                            <li><a href="<?php echo esc_url( home_url( '/settings/' ) ); ?>"><i class="fa-solid fa-sliders"></i>&nbsp;&nbsp;Settings</a></li>
                            <li><a href="<?php echo esc_url( wp_logout_url() ); ?>"><i class="fa-solid fa-right-from-bracket"></i>&nbsp;&nbsp;Sign Out</a></li>
                        </ul>
                    </div>
                </li>
            </ul>
        <?php else : ?>
            <ul>
                <li class="nav-button"><a href="<?php echo esc_url( home_url( '/get-started/' ) ); ?>">Get
                        Started</a></li>
                <li><a href="<?php echo esc_url( wp_login_url() ); ?>">Sign In</a></li>
            </ul>
        <?php endif; ?>
    </nav>
    <?php
}

