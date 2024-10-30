<?php
/**
 * Plugin Name:  ID4me
 * Plugin URI:   https://wordpress.org/plugins/id4me/
 * Description:  One ID for everything. Log in into your WordPress with your domain, through any service you like!
 * Version:      1.1.0
 * License:      MIT
 * Author:       1&1 IONOS
 * Author URI:   https://www.ionos.com
 * Requires PHP: 7.2
 * Text Domain:  id4me
 */

// Require external libraries
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

// Require dependencies/instances
require_once 'includes/class-id4me-wp-authority.php';
require_once 'includes/class-id4me-user.php';
require_once 'includes/class-id4me-env.php';
require_once 'includes/class-id4me-login.php';
require_once 'includes/class-id4me-client.php';
require_once 'includes/class-id4me-http-client.php';
require_once 'includes/class-id4me-authorization-state-model.php';
require_once 'includes/class-id4me-registration.php';

// Installation hooks
register_activation_hook( __FILE__, 'id4me_activate' );
register_uninstall_hook( __FILE__, 'id4me_uninstall' );
add_action( 'admin_init', 'id4me_plugin_update' );

// Loading hooks
add_action( 'plugins_loaded', 'id4me_init' );
add_action( 'plugins_loaded', 'id4me_cron_init' );
add_action( 'init', 'id4me_load_textdomain' );

// Register clean-up cron jobs
add_action( 'admin_enqueue_scripts', 'init_js' );
add_action( 'login_enqueue_scripts', 'init_register_js' );

// DB Update
add_action( 'admin_init', 'id4me_plugin_update' );

/**
 * Init
 */
function id4me_init() {

	// ID4me hooks on login page
	$id4me_login = new ID4me_Login();
	$id4me_registration = new ID4me_Registration();

	add_filter( 'login_form', array( $id4me_login, 'login_form' ), 9999 );
	add_filter( 'login_footer', array( $id4me_login, 'submit_login' ) );
	add_action( 'login_enqueue_scripts', array( $id4me_login, 'enqueue_scripts' ) );
	add_filter( 'registration_errors', array( $id4me_registration, 'validate_form' ), 10, 3 );
	add_filter( 'registration_redirect', 'login_registered_user' );

	// ID4me hooks to handle authority client
	$id4me_client = new ID4me_Client();
	add_filter( 'authenticate', array( $id4me_client, 'connect' ), 1, 31 );
	add_filter( 'authenticate', array( $id4me_client, 'auth' ), 2, 32 );

	// ID4me hooks to handle user <-> identifier association
	add_action( 'show_user_profile', array( 'ID4me_User', 'create_profile_fields' ) );
	add_action( 'edit_user_profile', array( 'ID4me_User', 'create_profile_fields' ) );
	add_action( 'personal_options_update', array( 'ID4me_User', 'save_profile_fields' ) );
	add_action( 'edit_user_profile_update', array( 'ID4me_User', 'save_profile_fields' ) );
	add_action( 'admin_enqueue_scripts', 'init_css' );
	add_action( 'wp_ajax_id4me_ajax_authflow', 'id4me_ajax_authflow' );
	add_action( 'wp_ajax_nopriv_id4me_ajax_register', 'id4me_ajax_register' );

	// ID4me registration
	add_action( 'user_register', 'save_new_user' );
	add_action( 'register_form', array( $id4me_registration, 'show_forms' ) );
}

/**
 * Redirects to Admin after registration
 */
function login_registered_user() {
	return admin_url();
}

/**
 * Updates user_meta_data
 */
function save_new_user( $user_id ) {
	if ( ! empty( $_POST ) ) {
		if ( isset( $_POST['id4me_sub'] ) && isset( $_POST['id4me_iss'] ) ) {
			ID4me_User::save_iss_sub_identifier( $user_id, $_POST['id4me_sub'], $_POST['id4me_iss'], $_POST['id4meIdentifier'] );
			ID4me_User::save_user_meta_registration_data( $user_id, $_POST['nickname'], $_POST['website'], $_POST['family_name'], $_POST['given_name'] );
			wp_set_current_user( $user_id );
			wp_set_auth_cookie( $user_id );
		}
	}
}

/**
 * Returns id4me plugin version
 */
function get_id4me_plugin_version() {
	if ( is_admin() ) {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugin_file = plugin_dir_path( __FILE__ ) . '/id4me.php';
		$plugin_data = get_plugin_data( $plugin_file );
		$plugin_version = $plugin_data['Version'];

		return $plugin_version;
	}
}

/**
 * Starts the whole ajaxauthflow
 */
function id4me_ajax_authflow() {
	$id4me_client = new ID4me_Client();
	$id4me_client->run_auth_flow();
	wp_die();
}

/**
 * returns user_info via ajax
 */
function id4me_ajax_register() {
	$id4me_client = new ID4me_Client();
	$id4me_client->register_id4me();
	wp_die();
}

/**
 * Register ajax functions and js files
 */
function init_register_js() {
	wp_enqueue_script("jquery");
	wp_register_script( 'id4me-js', plugin_dir_url( __FILE__ ) . 'assets/js/id4me_script.js' );
	wp_enqueue_script( 'id4me-js', plugins_url( __FILE__ ) . 'assets/js/id4me_script.js', array( 'jquery' ) );
	wp_localize_script( 'id4me-js', 'responseObject', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
}

/**
 * Loads required Js files at profile.php
 *
 * @param string $hook
 */
function init_js( $hook ) {
	if ( 'profile.php' !== $hook && 'user-edit.php' !== $hook ) {
		return;
	}
	wp_register_script( 'id4me-js', plugin_dir_url( __FILE__ ) . 'assets/js/id4me_script.js' );
	wp_enqueue_script( 'id4me-js', plugins_url( __FILE__ ) . 'assets/js/id4me_script.js', array( 'jquery' ) );
	wp_localize_script( 'id4me-js', 'responseObject', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
}

/**
 * Register CSS files
 *
 * @param string $hook
 */
function init_css( $hook ) {
	if ( 'profile.php' !== $hook && 'user-edit.php' !== $hook ) {
		return;
	}
	wp_enqueue_style( 'id4me-admin-css', plugin_dir_url( __FILE__ ) . 'assets/css/admin.less' );
}

/**
 * Init/Schedule Cron
 */
function id4me_cron_init() {

	if ( ! wp_next_scheduled( 'id4me_cron_cleanup' ) ) {
		wp_schedule_event( time(), 'daily', 'id4me_cron_cleanup' );
	}
}

/**
 * Init translations
 */
function id4me_load_textdomain() {

	load_plugin_textdomain(
		'id4me',
		false,
		basename( dirname( __FILE__ ) ) . '/languages'
	);
}

/**
 * Activation
 */
function id4me_activate() {

	// Check if ext-openssl is loaded
	if ( ! extension_loaded( 'openssl' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );

		wp_die(
			esc_html__(
				'The requested PHP extension "openssl" is missing from your system, please contact your hosting provider.',
				'id4me'
			)
		);
	}

	// Create authority table
	ID4me_WP_Authority::create_table();
	// save version in the WP DB for future DB upgrades

	add_option( 'id4me_version', get_id4me_plugin_version() );
}

/**
 * Uninstall (clean-up)
 */
function id4me_uninstall() {

	// Remove authority table
	ID4me_WP_Authority::delete_table();
}

/**
 * Plugin update
 */
function id4me_plugin_update() {

	// check if DB schema upgrade is needed. Option not stored in DB prior to 1.0.3
	if ( is_admin() && version_compare( get_option( 'id4me_version', '1.0.3' ), '1.0.4', '<' ) ) {
		ID4me_WP_Authority::id4me_database_update_from_1_0_3_to_1_0_4();
		update_option( 'id4me_version', '1.0.4' );
	}
}
