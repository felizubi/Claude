<?php

defined( 'ABSPATH' ) || exit;

/**
 * Fired during plugin activation
 *
 * @since      1.0.0
 *
 * @package    GPLVault_Updater
 * @subpackage GPLVault_Updater/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    GPLVault_Updater
 * @subpackage GPLVault_Updater/includes
 * @author     GPL Vault <support@gplvault.com>
 */
class GPLVault_Updater_Activator {

	/**
	 * Run this method during plugin activation
	 *
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		if ( ! class_exists( 'GPLVault_Settings_Manager', false ) ) {
			require_once GPLVault()->includes_path( '/settings/class-gplvault-settings-manager.php' );
		}

		if ( ! function_exists( 'gv_api_manager' ) ) {
			require_once GPLVault()->includes_path( '/gplvault-functions.php' );
		}

		$settings_manager = GPLVault_Settings_Manager::instance();

		$settings_manager->set_initial();
	}

}
