<?php
function create_wizard_user_role() {
	$capabilities = array(
		'read' => true,
		'edit_posts' => true,
		'delete_posts' => true,
		'edit_published_posts' => true,
		'delete_published_posts' => true,
		'upload_files' => true,
		'edit_attachments' => true,
		'delete_attachments' => true,
	);

	$role = add_role( 'wizard_user', 'Wizard User', $capabilities );

	if ( ! $role instanceof WP_Role ) {
		return false;
	}

	return true;
}
add_action( 'init', 'create_wizard_user_role' );

function set_new_user_as_wizard( $user_id ) {
	$user = new WP_User( $user_id );
	$user->set_role( 'wizard_user' );
}
add_action( 'user_register', 'set_new_user_as_wizard' );

function remove_admin_bar_for_wizard_user() {
	if ( current_user_can( 'wizard_user' ) && ! current_user_can( 'administrator' ) ) {
		show_admin_bar( false );
	}
}
add_action( 'after_setup_theme', 'remove_admin_bar_for_wizard_user' );

function redirect_wizard_user_from_admin() {
	if ( current_user_can( 'wizard_user' ) && ! current_user_can( 'administrator' ) && is_admin() ) {
		wp_redirect( home_url() );
		exit;
	}
}
add_action( 'admin_init', 'redirect_wizard_user_from_admin' );

function set_default_wizard_user_meta( $user_id ) {
	$default_subscription_meta = array(
		'subscription_level' => 'free',
		'subscription_price' => 0,
		'next_subscription_price' => 0,
		'subscription_length' => 'lifetime',
		'subscription_start_date' => current_time( 'mysql' ),
		'subscription_end_date' => null,
		'subscription_status' => 'active',
		'template_limit' => 5,
		'templates_used' => 0,
	);
	$subscription_updated = update_user_meta( $user_id, 'wizard_user_subscription', $default_subscription_meta );

	$default_profile_meta = array(
		'first_name' => '',
		'last_name' => '',
	);
	$profile_updated = update_user_meta( $user_id, 'wizard_user_profile', $default_profile_meta );

	return $subscription_updated && $profile_updated;
}

function update_wizard_subscription_level( $user_id, $subscription_level, $subscription_length ) {
	$subscription_levels = array( 'free', 'pro' );
	if ( ! in_array( $subscription_level, $subscription_levels, true ) ) {
		return false;
	}

	$subscription_meta = get_user_meta( $user_id, 'wizard_user_subscription', true );
	$template_limit = get_wizard_user_level_template_limit( $subscription_level );
	$current_time = current_time( 'mysql' );

	$subscription_meta['subscription_level'] = $subscription_level;
	$subscription_meta['subscription_price'] = get_wizard_subscription_price( $subscription_level, $subscription_length );
	$subscription_meta['subscription_start_date'] = $current_time;
	$subscription_meta['subscription_length'] = $subscription_length;
	$subscription_meta['template_limit'] = $template_limit;
	$subscription_meta['subscription_end_date'] = get_new_wizard_subscription_end_date( $subscription_length, $current_time );

	$updated = update_user_meta( $user_id, 'wizard_user_subscription', $subscription_meta );

	return $updated;
}

function get_wizard_subscription_price( $subscription_level, $subscription_length ) {
	$subscription_prices = array(
		'free' => array(
			'lifetime' => 0,
			'monthly' => 0,
			'quarterly' => 0,
			'yearly' => 0,
		),
		'pro' => array(
			'monthly' => 8,
			'quarterly' => 20,
			'yearly' => 60,
		),
	);

	if ( ! isset( $subscription_prices[ $subscription_level ][ $subscription_length ] ) ) {
		return false;
	}

	return $subscription_prices[ $subscription_level ][ $subscription_length ];
}

function get_wizard_user_level_template_limit( $subscription_level ) {
	$user_level_template_limits = array(
		'free' => 5,
		'pro' => 5000,
	);

	if ( ! isset( $user_level_template_limits[ $subscription_level ] ) ) {
		return false;
	}

	return $user_level_template_limits[ $subscription_level ];
}

function get_new_wizard_subscription_end_date( $subscription_length, $subscription_start_date ) {
	if ( 'lifetime' === $subscription_length ) {
		return null;
	}

	$end_date_formats = array(
		'monthly' => '+ 1 month',
		'quarterly' => '+ 3 months',
		'yearly' => '+ 1 year',
	);

	if ( ! isset( $end_date_formats[ $subscription_length ] ) ) {
		return false;
	}

	return date( 'Y-m-d', strtotime( $subscription_start_date . $end_date_formats[ $subscription_length ] ) );
}

function cancel_wizard_subscription( $user_id ) {
	$subscription_meta = get_user_meta( $user_id, 'wizard_user_subscription', true );
	$subscription_meta['subscription_status'] = 'cancelled';
	$subscription_meta['subscription_cancel_date'] = current_time( 'mysql' );

	return update_user_meta( $user_id, 'wizard_user_subscription', $subscription_meta );
}

function reactivate_wizard_subscription( $user_id, $subscription_meta ) {
	$updated = update_wizard_subscription_level( $user_id, $subscription_meta['subscription_level'], $subscription_meta['subscription_length'] );

	if ( $updated ) {
		$subscription_meta['subscription_status'] = 'active';
		return update_user_meta( $user_id, 'wizard_user_subscription', $subscription_meta );
	}

	return false;
}

function wizard_subscription_expired( $user_id ) {
	$subscription_meta = get_user_meta( $user_id, 'wizard_user_subscription', true );
	$subscription_status = $subscription_meta['subscription_status'];
	$subscription_end_date = $subscription_meta['subscription_end_date'];
	$today = current_time( 'Y-m-d' );

	if ( 'cancelled' === $subscription_status || 'expired' === $subscription_status ) {
		return true;
	}

	if ( 'active' === $subscription_status && $subscription_end_date && $subscription_end_date < $today ) {
		return expire_wizard_subscription( $user_id );
	}

	return false;
}

function expire_wizard_subscription( $user_id ) {
	$subscription_meta = get_user_meta( $user_id, 'wizard_user_subscription', true );
	$subscription_meta['subscription_status'] = 'expired';
	$subscription_meta['subscription_end_date'] = current_time( 'mysql' );

	return update_user_meta( $user_id, 'wizard_user_subscription', $subscription_meta );
}