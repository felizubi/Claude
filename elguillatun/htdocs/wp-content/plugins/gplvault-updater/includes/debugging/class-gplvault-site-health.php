<?php
/**
 * This file integrates plugin health report to WP Site Health api.
 *
 * @since 4.3.0
 */


/**
 * GPLVault_Site_Health class shows Plugin Health related reports on Site Health
 *
 * @since 4.3.0
 */
class GPLVault_Site_Health {
	const BADGE_COLOR     = 'blue';
	const DEBUG_INFO_SLUG = 'gplvault_updater';

	/**
	 * @var GPLVault_Site_Health
	 */
	private static $instance;

	/**
	 * Singleton method for the class
	 *
	 * @since 4.3.0
	 *
	 * @return GPLVault_Site_Health
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->hooks();
		}

		return self::$instance;
	}

	private function __construct() {
	}

	private function __clone(){}

	public function __wakeup(){}

	/**
	 * Initialize necessary hooks and mount during Singleton Object creation
	 *
	 * @return void
	 */
	private function hooks() {
		add_filter( 'site_status_tests', array( $this, 'register_status_tests' ) );
		add_filter( 'debug_information', array( $this, 'register_debug_information' ) );
	}

	/**
	 * Translatable label
	 *
	 * @return string
	 */
	public function get_label() {
		return esc_html__( 'GPLVault Update Manager', 'gplvault' );
	}

	public function register_status_tests( $tests ) {
		return $tests;
	}

	public function register_debug_information( $debug_info ) {
		return $debug_info;
	}
}
