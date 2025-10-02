<?php
/**
 * Plugin Name: GPLVault Update Manager
 * Plugin URI: https://www.gplvault.com
 * Description: Keep your site in sync with all WordPress plugins and themes from <a href="https://www.gplvault.com" target="_blank">www.gplvault.com</a>
 * Version: 5.0.1
 * Requires at least: 5.2
 * Tested up to: 6.2.1
 * Requires PHP: 7.2
 * Author: GPL Vault
 * Author URI: https://www.gplvault.com
 * Text Domain: gplvault
 * License: GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Network: True
 */

defined( 'ABSPATH' ) || exit;

/** Adding Overrides */
require_once __DIR__ . '/includes/overrides/includes.php';

define( 'GV_UPDATER_FILE', __FILE__ );
$gv_plugin_meta = get_file_data( GV_UPDATER_FILE, array( 'Version' => 'Version' ) );
define( 'GV_UPDATER_VERSION', $gv_plugin_meta['Version'] );

define( 'GV_UPDATER_NAME', 'gplvault-updater' );

define( 'GV_UPDATER_PATH', plugin_dir_path( __FILE__ ) );
define( 'GV_UPDATER_SLUG', basename( plugin_dir_path( __FILE__ ) ) );
define( 'GV_UPDATER_STATIC_PATH', plugin_dir_path( __FILE__ ) . 'static/' );
define( 'GV_UPDATER_STATIC_URL', plugin_dir_url( __FILE__ ) . 'static/' );
defined( 'GV_UPDATER_API_URL' ) || define( 'GV_UPDATER_API_URL', 'https://www.gplvault.com/' );
defined( 'GPLVAULT_RENAME_PLUGINS' ) || define( 'GPLVAULT_RENAME_PLUGINS', true );
defined( 'GPLVAULT_DISABLE_LOG' ) || define( 'GPLVAULT_DISABLE_LOG', false );
defined( 'GPLVAULT_LOG_STORAGE_DURATION' ) || define( 'GPLVAULT_LOG_STORAGE_DURATION', 7 * DAY_IN_SECONDS );

$gv_up_dir = wp_upload_dir();
defined( 'GV_UPDATER_LOG_DIR' ) || define( 'GV_UPDATER_LOG_DIR', $gv_up_dir['basedir'] . '/gplvault-logs/' );

if ( ! function_exists( 'is_network_only_plugin' ) ) {
	require_once untrailingslashit( ABSPATH ) . '/wp-admin/includes/plugin.php';
}

define( 'GV_UPDATER_NETWORK_ENABLED', is_network_only_plugin( plugin_basename( __FILE__ ) ) );


register_activation_hook( __FILE__, 'gplvault_updater_activate' );

register_deactivation_hook( __FILE__, 'gplvault_updater_deactivate' );

function gplvault_updater_activate() {
	require_once GPLVault()->includes_path( '/class-gplvault-activation.php' );
	GPLVault_Updater_Activator::activate();
}

function gplvault_updater_deactivate() {
	require_once GPLVault()->includes_path( '/class-gplvault-deactivation.php' );
	GPLVault_Updater_Deactivator::deactivate();
}

/**
 * Starts the excution of the plugin here
 */
require_once GV_UPDATER_PATH . 'includes/class-gplvault-client.php';

function gv_is_rest_reuquest() {
	if ( empty( $_SERVER['REQUEST_URI'] ) ) {
		return false;
	}

	$rest_prefix = trailingslashit( rest_get_url_prefix() );
	return ( false !== strpos( $_SERVER['REQUEST_URI'], $rest_prefix ) );
}

function gv_deprecated_function( $function, $version, $replacement = '' ) {
	if ( wp_doing_ajax() || gv_is_rest_reuquest() ) {
		do_action( 'deprecated_function_run', $function, $replacement, $version );
		$log_string  = "The {$function} function is deprecated since version {$version}.";
		$log_string .= $replacement ? " Replace with {$replacement}." : '';
		error_log( $log_string ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		_deprecated_function( $function, $version, $replacement ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}


/**
 * @return GPLVault_Client
 */
function gv_updater_main() {
	gv_deprecated_function( __FUNCTION__, '4.0.0-beta', 'GPLVault' );

	return GPLVault();
}

function GPLVault() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return GPLVault_Client::instance();
}

function gplvault_updater_init() {
	GPLVault()->run();
}

if ( ! version_compare( PHP_VERSION, '7.2', '>=' ) ) {
	add_action( 'admin_notices', 'gplvault_php_version_notice' );
} elseif ( ! version_compare( get_bloginfo( 'version' ), '5.2', '>=' ) ) {
	add_action( 'admin_notices', 'gplvault_wp_version_notice' );
} else {
	gplvault_updater_init();
}

function gplvault_wp_version_notice() {
	/* translators: %s: WordPress version. */
	$message      = sprintf( esc_html__( 'GPLVault Update Manager requires WordPress version %s+. Because you are using an earlier version, the plugin is NOT WORKING right now.', 'gplvault' ), '5.2' );
	$html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
	echo wp_kses_post( $html_message );
}

function gplvault_php_version_notice() {
	/* translators: %s: PHP version. */
	$message      = sprintf( esc_html__( 'GPLVault Update Manager requires PHP version %s+, the plugin is NOT WORKING right now.', 'gplvault' ), '7.2' );
	$html_message = sprintf( '<div class="error">%s</div>', wpautop( $message ) );
	echo wp_kses_post( $html_message );
}
