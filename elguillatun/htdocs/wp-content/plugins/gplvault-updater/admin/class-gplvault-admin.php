<?php

defined( 'ABSPATH' ) || exit;

class GPLVault_Admin {
	const SLUG_SETTINGS  = 'gplvault_settings';
	const SLUG_PLUGINS   = 'gplvault_plugins';
	const SLUG_SYSTEM    = 'gplvault_system';
	const SLUG_THEME     = 'gplvault_themes';
	const TAB_ACTIVATION = 'activation';
	const TAB_OTHERS     = 'extras';

	const UPDATES_KEY_PLUGINS = 'gv_plugin_updates';
	const UPDATES_KEY_THEMES  = 'gv_theme_updates';

	/**
	 * @var GPLVault_Admin|null
	 */
	protected static $singleton = null;

	private static $initiated = false;

	/**
	 * @var GPLVault_Settings_Manager
	 */
	protected $settings;

	protected $tab_settings = array();

	/**
	 * @var GPLVault_API_Manager $api
	 */
	protected $api;

	/**
	 * @return GPLVault_Admin
	 */
	public static function instance() {
		if ( is_null( self::$singleton ) ) {
			self::$singleton = new self();
		}

		return self::$singleton;
	}

	/**
	 * @return array
	 */
	private function get_ajax_bindings() {
		return array(
			'license_activation'   => array( self::instance(), 'activate_license' ),
			'license_deactivation' => array( self::instance(), 'deactivate_license' ),
			'check_license'        => array( self::instance(), 'check_license' ),
			'cleanup_settings'     => array( self::instance(), 'cleanup_settings' ),
			'plugins_exclusion'    => array( self::instance(), 'exclude_plugins' ),
			'themes_exclusion'     => array( self::instance(), 'exclude_themes' ),
		);
	}

	private function __clone() {}

	public function __wakeup() {}

	private function __construct() {
		$this->load_dep();
	}

	public function init() {
		if ( ! is_admin() ) {
			return;
		}
		$this->init_hooks();
	}

	private function init_hooks() {
		if ( true === self::$initiated ) {
			return;
		}
		self::$initiated = true;

		add_action( 'admin_notices', array( __CLASS__, 'inject_before_notices' ), -9999 );
		add_action( 'admin_notices', array( __CLASS__, 'inject_after_notices' ), PHP_INT_MAX );
		add_action( 'in_admin_header', array( $this, 'render_header' ) );
		add_action( 'in_plugin_update_message-' . GPLVault()->plugin_basename(), array( $this, 'plugin_update_message' ), 10, 2 );
		if ( is_multisite() ) {
			add_action( 'network_admin_menu', array( $this, 'admin_menu' ) );
			add_action( 'network_admin_menu', array( $this, 'admin_menu_change_name' ), 200 );
			add_filter( 'network_admin_plugin_action_links_' . GPLVault()->plugin_basename(), array( $this, 'actions_links' ) );
			if ( is_network_admin() ) {
				add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
			}
		} else {
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_action( 'admin_menu', array( $this, 'admin_menu_change_name' ), 200 );
			add_filter( 'plugin_action_links_' . GPLVault()->plugin_basename(), array( $this, 'actions_links' ) );
			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'in_admin_footer', array( $this, 'in_admin_footer' ) );

		add_action( 'admin_print_styles', array( $this, 'initial_notices' ) );
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
		add_action( 'gv_license_deactivated', array( $this, 'act_on_deactivation' ) );
		add_action( 'gv_api_license_activated', array( $this, 'license_activated' ), 10, 1 );
		add_action( 'gv_api_license_activated', array( $this, 'gv_update_plugins' ), 998 );

		add_filter( 'gv_ajax_bindings', array( $this, 'ajax_bindings' ) );
		add_action( 'admin_footer', array( $this, 'admin_js_template' ) );
		add_action( 'upgrader_process_complete', array( $this, 'upgrader_process_handler' ), 10, 2 );
		add_action( 'current_screen', array( $this, 'add_help_tabs' ) );
	}

	public function upgrader_process_handler( $upgrader, $options ) {
		if ( ! isset( $options['type'] ) ) {
			self::gv_update_plugins();
		} else {
			if ( 'plugin' === $options['type'] ) {
				self::gv_update_plugins();
			}
		}
	}

	private function load_dep() {
		if ( ! class_exists( 'GPLVault_API_Manager' ) ) {
			require_once GPLVault()->includes_path( '/api/class-gplvault-api-manager.php' );
		}

		$this->settings = gv_settings_manager();
		$this->api      = GPLVault_API_Manager::instance();
	}

	public function act_on_deactivation() {
		delete_site_transient( self::UPDATES_KEY_PLUGINS );
		delete_site_transient( self::UPDATES_KEY_THEMES );

		gv_settings_manager()->disable_activation_status();
	}

	public function license_activated( $server_response ) {
		$status_data              = $server_response['data'] ?? array();
		$status_data['activated'] = $server_response['activated'];
		gv_settings_manager()->save_license_status( $status_data );
	}

	public function admin_js_template() {
		if ( self::is_admin_page() ) {
			require_once GPLVault()->path( '/views/js-templates.php' );
		}
	}

	public function admin_body_class( $admin_body_class = '' ) {
		if ( ! self::is_admin_page() ) {
			return $admin_body_class;
		}
		$classes = explode( ' ', trim( $admin_body_class ) );

		$classes[]        = 'gv-admin-page';
		$admin_body_class = implode( ' ', $classes );
		return " $admin_body_class ";
	}

	public function plugin_row_meta( $links, $file ) {
		if ( GPLVault()->plugin_basename() === $file ) {
			$row_meta = array(
				'docs'    => '<a href="' . esc_url( apply_filters( 'gplvault_docs_url', 'https://www.gplvault.com/faq' ) ) . '" aria-label="' . esc_attr__( 'View GPL Vault documentation', 'gplvault' ) . '">' . esc_html__( 'Docs', 'gplvault' ) . '</a>',
				'support' => '<a href="' . esc_url( apply_filters( 'gplvault_support_url', 'https://www.gplvault.com/contact' ) ) . '" aria-label="' . esc_attr__( 'Visit customer support', 'gplvault' ) . '">' . esc_html__( 'Support', 'gplvault' ) . '</a>',
			);

			return array_merge( $links, $row_meta );
		}

		return (array) $links;
	}

	public function actions_links( $links ) {
		$admin_path    = 'admin.php?page=' . self::SLUG_SETTINGS;
		$settings_link = '<a href="' . self_admin_url( $admin_path ) . '">' . __( 'Settings', 'gplvault' ) . '</a>';

		array_unshift( $links, $settings_link );

		return $links;
	}

	public function in_admin_footer() {
		if ( GPLVault_Util::is_gplvault_area() ) { ?>
			<div class="gv_admin_note">
				<p><strong><?php esc_html_e( 'Help &amp; Support', 'gplvault' ); ?></strong>:
					<?php
					printf(
						__( 'If you have any question, issue with Updater Plugin or feedback, please submit a support ticket by logging into your account: <a href="%1$s" target="_blank">My Account</a>', 'gplvault' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'https://www.gplvault.com/my-account'
					);
					?>
				</p>
				<p><strong><?php esc_html_e( 'Do you want to grow with us?', 'gplvault' ); ?></strong> <?php printf( __( 'Join our <strong><a href="%1$s" target="_blank">Affiliate network</a></strong>', 'gplvault' ), 'https://www.gplvault.com/affiliate-area/' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
			</div>

			<?php
		}
	}

	public function plugin_update_message( $plugin_data, $update_response ) {
		$this->update_message( GPLVault()->version(), $plugin_data['new_version'] );
	}

	private function update_message( $current_version, $update_version ) {
		$current_version_parts  = explode( '.', $current_version );
		$update_version_parts   = explode( '.', $update_version );
		$current_version_majors = $current_version_parts[0] . '.' . $current_version_parts[1];
		$update_version_majors  = $update_version_parts[0] . '.' . $update_version_parts[1];

		if ( ! version_compare( $update_version_majors, $current_version_majors, '>' ) ) {
			return;
		}
		?>
		<hr class="gv-update-info__separator" />
		<div class="gv-update-info">
			<div class="gv-update-info__icon">
				<i class="dashicons dashicons-megaphone"></i>
			</div>
			<div>
				<div class="gv-update-info__title">
					<?php esc_html_e( 'Heads Up! Please take backup before update.', 'gplvault' ); ?>
				</div>
				<div class="gv-update-info__message">
					<?php printf( __( 'The latest update includes substantial changes across different areas of the plugin. We highly recommend you backup your sites before upgrades and take a look at the "Help" tab on the <a href="%1$s" target="_blank">Settings</a> page after upgrading the plugin.', 'gplvault' ), self_admin_url( 'admin.php?page=' . self::SLUG_SETTINGS ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</div>
		<?php
	}

	public function admin_scripts( $hook ) {

		wp_register_script( 'gv-select2', GPLVault()->admin_assets_url( '/scripts/select2.min.js' ), array( 'jquery' ), '4.1.0-rc.0', true );
		wp_register_script( 'gv-popper', GPLVault()->admin_assets_url( '/scripts/popper.min.js' ), array(), '2.9.2', true );
		wp_register_script( 'gv-tippy', GPLVault()->admin_assets_url( '/scripts/tippy-bundle.umd.min.js' ), array( 'gv-popper' ), '6.3.1', true );
		wp_register_script( 'gv-polipop', GPLVault()->admin_assets_url( '/scripts/polipop.min.js' ), array( 'jquery', 'wp-util', 'wp-sanitize', 'wp-i18n', 'wp-a11y', 'wp-sanitize', 'gv-tippy', 'gv-select2' ), GPLVault()->version(), true );
		wp_register_script( 'gv-common', GPLVault()->admin_assets_url( '/scripts/gv-common.js' ), array( 'jquery', 'wp-util', 'wp-sanitize', 'wp-i18n', 'wp-a11y', 'wp-sanitize', 'gv-polipop', 'gv-tippy', 'gv-select2' ), GPLVault()->version(), true );
		wp_register_script( 'gv-settings', GPLVault()->admin_assets_url( '/scripts/gv-settings.js' ), array( 'gv-common' ), GPLVault()->version(), true );

		wp_register_style( 'gv-polipop-core', GPLVault()->admin_assets_url( '/styles/polipop.core.min.css' ), array(), '1.0.0-master' );
		wp_register_style( 'gv-polipop-default', GPLVault()->admin_assets_url( '/styles/polipop.default.min.css' ), array( 'gv-polipop-core' ), '1.0.0-master' );
		wp_register_style( 'gv-polipop-compact', GPLVault()->admin_assets_url( '/styles/polipop.compact.min.css' ), array( 'gv-polipop-core' ), '1.0.0-master' );
		wp_register_style( 'gv-polipop-minimal', GPLVault()->admin_assets_url( '/styles/polipop.minimal.min.css' ), array( 'gv-polipop-core' ), '1.0.0-master' );
		wp_register_style( 'gv-select2', GPLVault()->admin_assets_url( '/styles/select2.min.css' ), array(), '4.1.0-rc.0' );
		wp_register_style( 'gv-tippy', GPLVault()->admin_assets_url( '/styles/tippy.min.css' ), array(), '6.3.1' );
		wp_register_style( 'gv-admin', GPLVault()->admin_assets_url( '/styles/gv-admin.css' ), array( 'dashicons', 'gv-polipop-default', 'gv-polipop-compact', 'gv-polipop-minimal', 'gv-tippy', 'gv-select2' ), GPLVault()->version() );
		wp_register_style( 'gv-global', GPLVault()->admin_assets_url( '/styles/gv-global.css' ), array( 'dashicons' ), GPLVault()->version() );

		wp_enqueue_style( 'gv-global' );

		wp_localize_script(
			'gv-common',
			'_gvCommonSettings',
			array(
				'ajax_url'    => admin_url( 'admin-ajax.php' ),
				'ajax_nonce'  => wp_create_nonce( GPLVault_Ajax::NONCE_KEY ),
				'ajax_action' => GPLVault_Ajax::ACTION,
				'pagenow'     => self::get_admin_page(),
				'popup'       => array(
					'layout'       => 'popups',
					'sticky'       => false,
					'life'         => 8000,
					'position'     => 'top-right',
					'theme'        => 'default',
					'pauseOnHover' => true,
					'pool'         => 0,
					'spacing'      => 5,
					'progressbar'  => true,
					'closer'       => false,
					'effect'       => 'slide',
					'insert'       => 'before',
					'easing'       => 'ease-in-out',
				),
			)
		);

		if ( self::is_admin_page() ) {
			wp_enqueue_style( 'gv-admin' );
		}

		if ( static::SLUG_SETTINGS === self::get_admin_page() ) {
			wp_enqueue_script( 'gv-settings' );
			wp_localize_script(
				'gv-settings',
				'_gvAdminSettings',
				array(
					'selectors' => array(
						'page_wrapper' => 'gv_settings_wrapper',
						'license'      => array(
							'section_id' => 'api_settings_section',
							'api'        => array(
								'block_id'      => 'api_settings_column',
								'input_key'     => 'api_master_key',
								'input_product' => 'api_product_id',
								'button_id'     => 'gv_activate_api',
							),
							'actions'    => array(
								'status_btn'       => 'check_license',
								'deactivation_btn' => 'license_deactivation',
								'cleanup_btn'      => 'cleanup_settings',
							),
						),
						'exclusion'    => array(
							'section_id' => 'gv_items_exclusion',
							'plugins'    => array(
								'input_id'  => 'gv_blocked_plugins',
								'button_id' => 'plugins_exclusion_btn',
							),
							'themes'     => array(
								'input_id'  => 'gv_blocked_themes',
								'button_id' => 'themes_exclusion_btn',
							),
						),
					),
				)
			);
		}
	}

	public function initial_notices() {
		$screen          = get_current_screen();
		$screen_id       = $screen ? $screen->id : '';
		$show_on_screens = array(
			'dashboard',
			'dashboard-network',
			'themes-network',
			'plugins-network',
			'plugins',
			'themes',
		);

		// Notices should only show on WooCommerce screens, the main dashboard, and on the plugins screen.
		if ( ! in_array( $screen_id, $show_on_screens, true ) && ! self::is_admin_page() ) {
			return;
		}

		if ( ! gv_settings_manager()->api_key_exists() && ! gv_settings_manager()->license_is_activated() ) {
			if ( is_multisite() ) {
				add_action( 'network_admin_notices', array( $this, 'activation_notice' ) );

			} else {
				add_action( 'admin_notices', array( $this, 'activation_notice' ) );
			}
		}

		if ( defined( 'WP_HTTP_BLOCK_EXTERNAL' ) && WP_HTTP_BLOCK_EXTERNAL ) {
			$host = wp_parse_url( GV_UPDATER_API_URL, PHP_URL_HOST );

			if ( ! defined( 'WP_ACCESSIBLE_HOSTS' ) || stristr( WP_ACCESSIBLE_HOSTS, $host ) === false ) {
				if ( is_multisite() ) {
					add_action( 'network_admin_notices', array( $this, 'external_block_notice' ) );
				} else {
					add_action( 'admin_notices', array( $this, 'external_block_notice' ) );
				}
			}
		}
	}

	public function admin_menu() {
		$capability = is_multisite() ? 'manage_network' : 'manage_options';

		add_menu_page(
			__( 'GPLVault Plugin Manager', 'gplvault' ),
			__( 'GPLVault', 'gplvault' ),
			$capability,
			static::SLUG_SETTINGS,
			array( $this, 'page_settings' ),
			'data:image/svg+xml;base64,' . base64_encode( // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				'<svg width="20" height="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 84 88">
  <path fill="currentColor" class="cls-1" d="M5 0v19H0v40h5v29h16V76h46v12h17V0zm0 54V24h4v30zm9 5V19h-4V5h69v66H10V59zm2 24h-6v-7h6zm63 0h-7v-7h7z"/>
  <path fill="currentColor" class="cls-1" d="M73 11H18v5h50v44H18v5h55V11z"/>
  <path fill="currentColor" class="cls-1" d="M39 27.41l-2.24-4.47a17 17 0 00-9.39 16.12l5-.26A12 12 0 0139 27.41z"/>
  <path fill="currentColor" class="cls-1" d="M27.77 49.46l2.73-1.39a17 17 0 0027.5.32l1.88 1L62.29 45l-1.87-1A17 17 0 0047 21.34V19h-5v15.16a5.78 5.78 0 00-2.28 3.63L25.53 45zM35 45.77l7-3.53a4.63 4.63 0 004.81.07L53.58 46A12 12 0 0135 45.77zm21-4.18l-6.87-3.69A4.31 4.31 0 0047 34.18V26.4c5.77 1.33 11.18 7.71 9 15.19z"/>
</svg>'
			)
		);
	}

	public function admin_menu_change_name() {
		global $submenu;

		if ( isset( $submenu[ static::SLUG_SETTINGS ] ) ) {
			$submenu[ static::SLUG_SETTINGS ][0][0] = __( 'Settings', 'gplvault' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	public function activate_license( $request_params ) {
		$api_key    = ! empty( $request_params['api_key'] ) ? gv_clean( $request_params['api_key'] ) : null;
		$product_id = ! empty( $request_params['product_id'] ) ? gv_clean( $request_params['product_id'] ) : null;

		if ( empty( $api_key ) && empty( $product_id ) ) {
			return new WP_Error(
				'gv_missing_fields',
				__( 'Both Product ID and API Key field is required', 'gplvault' ),
				array(
					'http_status' => WP_Http::BAD_REQUEST,
				)
			);
		}
		$api_obj = gv_api_manager()->set_api_key( $api_key )->set_product_id( $product_id );
		$status  = $api_obj->status();
		if ( is_wp_error( $status ) ) {
			$error_data                = $status->get_error_data();
			$error_data                = empty( $error_data ) ? array() : $error_data;
			$error_data['http_status'] = WP_Http::OK;
			$error_data['title']       = GPLVault_Helper::request_error_title( $status );

			return new WP_Error( $status->get_error_code(), $status->get_error_message(), $error_data );
		}

		$status_data = $status['data'];
		if ( ! $status_data['activated'] ) {
			$activation_response = $api_obj->activate();

			if ( is_wp_error( $activation_response ) ) {
				$error_data                = $activation_response->get_error_data();
				$error_data                = empty( $error_data ) ? array() : $error_data;
				$error_data['http_status'] = WP_Http::OK;
				$error_data['title']       = GPLVault_Helper::request_error_title( $activation_response );

				return new WP_Error( $activation_response->get_error_code(), $activation_response->get_error_message(), $error_data );
			}

			gv_settings_manager()->save_api_settings(
				array(
					'api_key'    => $api_key,
					'product_id' => $product_id,
				)
			);

			gv_settings_manager()->enable_activation_status();
			$message                        = sprintf( __( 'License activated successfully. %s', 'gplvault' ), $activation_response['message'] );
			$activation_response['message'] = $message;

			do_action( 'gv_api_license_activated', $activation_response );

			return array(
				'title'                       => __( 'License activated!', 'gplvault' ),
				'message'                     => $activation_response['message'],
				'activated'                   => $activation_response['activated'],
				'total_activations_purchased' => $activation_response['data']['total_activations_purchased'],
				'total_activations'           => $activation_response['data']['total_activations'],
				'activations_remaining'       => $activation_response['data']['activations_remaining'],
			);
		}

		gv_settings_manager()->save_api_settings(
			array(
				'api_key'    => $api_key,
				'product_id' => $product_id,
			)
		);

		gv_settings_manager()->enable_activation_status();

		return array(
			'title'                       => __( 'Already Active', 'gplvault' ),
			'message'                     => __( 'Your license is already activated!', 'gplvault' ),
			'activated'                   => $status_data['activated'],
			'total_activations_purchased' => $status_data['total_activations_purchased'],
			'total_activations'           => $status_data['total_activations'],
			'activations_remaining'       => $status_data['activations_remaining'],
		);
	}

	public function deactivate_license( $request_params ) {
		$response = gv_api_manager()->set_initials()->deactivate();

		if ( is_wp_error( $response ) ) {
			$error_data                = $response->get_error_data();
			$error_data                = empty( $error_data ) ? array() : $error_data;
			$error_data['http_status'] = WP_Http::OK;
			$error_data['title']       = GPLVault_Helper::request_error_title( $response );

			return new WP_Error( $response->get_error_code(), $response->get_error_message(), $error_data );
		}

		if ( isset( $response['deactivated'] ) && $response['deactivated'] ) {
			$payload = array(
				'status'      => $response['deactivated'] ? 'deactivated' : 'not deactivated',
				'activations' => $response['data']['total_activations_purchased'],
				'used'        => $response['data']['total_activations'],
				'remaining'   => $response['data']['activations_remaining'],
			);

			do_action( 'gv_license_deactivated', $response );

			$message             = sprintf( __( 'License deactivated successfully. %s', 'gplvault' ), $response['activations_remaining'] );
			$response['message'] = $message;

			return $response;
		}
	}

	public function check_license( $request_params ) {
		$response = gv_api_manager()->status();

		if ( is_wp_error( $response ) ) {
			$error_data                = $response->get_error_data();
			$error_data                = empty( $error_data ) ? array() : $error_data;
			$error_data['http_status'] = WP_Http::OK;
			$error_data['title']       = GPLVault_Helper::request_error_title( $response );

			return new WP_Error( $response->get_error_code(), $response->get_error_message(), $error_data );
		}

		gv_settings_manager()->save_license_status( $response['data'] );

		$state = $response['data']['activated'] ? __( 'active', 'gplvault' ) : __( 'not active', 'gplvault' );
		return array(
			'title'                       => __( 'License Status', 'gplvault' ),
			'message'                     => sprintf( __( 'License for the site is %s on GPLVault server', 'gplvault' ), $state ),
			'activated'                   => $response['data']['activated'] ?? false,
			'total_activations_purchased' => $response['data']['total_activations_purchased'] ?? 31,
			'total_activations'           => $response['data']['total_activations'] ?? 0,
			'activations_remaining'       => $response['data']['activations_remaining'] ?? 31,
		);
	}

	public function exclude_plugins( $request_params ) {
		$plugins = $request_params['plugins'] ?? array();
		$plugins = gv_clean( $plugins );

		$updated = gv_settings_manager()->save_blocked_plugins( $plugins );

		if ( ! $updated ) {
			return new WP_Error(
				'plugin_exclusion_failed',
				__( 'Plugin exclusion list was not updated.', 'gplvault' ),
				array(
					'title' => __( 'Not Updated', 'gplvault' ),
				)
			);
		}

		// regenerate update transient
		self::gv_update_plugins();

		return array(
			'title'   => __( 'Updated', 'gplvault' ),
			'message' => __( 'Plugin exclusion list updated successfully.', 'gplvault' ),
		);
	}

	public function exclude_themes( $request_params ) {
		$themes  = $request_params['themes'] ?? array();
		$themes  = array_map( 'wp_unslash', $themes );
		$updated = gv_settings_manager()->save_blocked_themes( $themes );

		if ( ! $updated ) {
			return new WP_Error(
				'theme_exclusion_failed',
				__( 'Theme exclusion list was not updated.', 'gplvault' ),
				array(
					'title' => __( 'Not Updated', 'gplvault' ),
				)
			);
		}

		// regenerate themes update transient
		// self::gv_update_themes();

		return array(
			'title'   => __( 'Updated', 'gplvault' ),
			'message' => __( 'Theme exclusion list updated successfully.', 'gplvault' ),
		);
	}


	public function cleanup_settings( $request_params ) {
		delete_site_transient( self::UPDATES_KEY_PLUGINS );
		delete_site_transient( self::UPDATES_KEY_THEMES );

		gv_settings_manager()->remove_all_schema();
		gv_settings_manager()->disable_activation_status();
		gv_settings_manager()->remove_api_key();
		gv_settings_manager()->remove_license_status();

		return array(
			'title'   => __( 'Settings Removed', 'gplvault' ),
			'message' => __( 'All license related settings removed successfully.', 'gplvault' ),
		);
	}

	//
	//  Callbacks for Settings API ends
	//

	public function page_settings() {
		self::load_view(
			'/settings',
			array(
				'admin_manager'    => $this,
				'settings_manager' => gv_settings_manager(),
			)
		);
	}
	// TODO: remove the line below
	//
	//  public function page_plugins() {
	//      self::load_view(
	//          '/plugins',
	//          array(
	//              'admin_manager'    => $this,
	//              'settings_manager' => gv_settings_manager(),
	//          )
	//      );
	//  }

	public function activation_notice() {
		include GV_UPDATER_STATIC_PATH . 'notices/html-notice-activate.php';
	}

	public function cron_notice() {
		include GV_UPDATER_STATIC_PATH . 'notices/html-notice-cron.php';
	}

	public function external_block_notice() {
		include GV_UPDATER_STATIC_PATH . 'notices/html-notice-external-block.php';
	}

	public static function load_view( $view, $imported_variables = array(), $path = false ) {
		if ( $imported_variables && is_array( $imported_variables ) ) {
			extract( $imported_variables, EXTR_OVERWRITE ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		}

		if ( ! $path ) {
			$path = GPLVault()->path( '/views' );
		}

		include $path . rtrim( $view, '.php' ) . '.php';
	}

	public static function load_partial( $partial, $imported_variables = array(), $path = false ) {
		$view = '/partials/' . ltrim( $partial, '/\\' );
		self::load_view( $view, $imported_variables, $path );
	}

	/**
	 * @return bool
	 */
	public static function is_admin_page() {
		$vault_page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return in_array( $vault_page, static::admin_pages(), true );
	}

	public static function admin_pages() {
		return array(
			static::SLUG_SETTINGS,
			static::SLUG_PLUGINS,
			static::SLUG_THEME,
			static::SLUG_SYSTEM,
		);
	}

	public function render_header() {
		if ( ! self::is_admin_page() ) {
			return;
		}

		$screen_id = self::get_admin_page();
		$titles    = array(
			static::SLUG_SETTINGS => __( 'GPLVault Settings', 'gplvault' ),
			static::SLUG_PLUGINS  => __( 'GPLVault Plugins', 'gplvault' ),
			static::SLUG_THEME    => __( 'GPLVault Themes', 'gplvault' ),
			static::SLUG_SYSTEM   => __( 'GPLVault System Info', 'gplvault' ),
		);

		?>
		<div class="gv-layout__header">
			<div class="gv-layout__header-wrapper">
				<h1 class="gv-layout__header-heading"><?php echo esc_html( $titles[ $screen_id ] ); ?></h1>
			</div>
		</div>
		<?php
	}

	public static function inject_before_notices() {
		if ( ! self::is_admin_page() ) {
			return;
		}

		echo '<div class="gv-layout__notice-list-hide" id="wp__notice-list">';
		echo '<div class="gv-layout__noticelist"> <div id="gv_notice"></div> </div>';
		echo '<div class="wp-header-end" id="gv-layout__notice-catcher"></div>';
	}

	public static function inject_after_notices() {
		if ( ! self::is_admin_page() ) {
			return;
		}

		echo '</div>';
	}

	public function admin_links( $type = '' ) {
		$links = array(
			'settings' => self_admin_url( 'admin.php?page=' . self::SLUG_SETTINGS ),
			'plugins'  => self_admin_url( 'admin.php?page=' . self::SLUG_PLUGINS ),
			'themes'   => self_admin_url( 'admin.php?page=' . self::SLUG_THEME ),
		);

		if ( empty( $links ) ) {
			return $links;
		}

		return $links[ $type ] ?? '';
	}

	public static function get_admin_page() {
		return isset( $_GET['page'] ) ? esc_attr( $_GET['page'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	public function ajax_bindings( $bindings ) {
		return wp_parse_args( $this->get_ajax_bindings(), $bindings );
	}


	/**
	 * @param WP_Screen $screen
	 */
	public function add_help_tabs( $screen ) {
		if ( ! self::is_admin_page() ) {
			return;
		}

		// phpcs:ignore Squiz.PHP.CommentedOutCode.Found
		//      $screen->set_help_sidebar(
		//          '<p><strong>' . __( 'For more information:' ) . '</strong></p>'
		//      );

		$screen->add_help_tab(
			array(
				'id'      => 'gplvault_tab_about',
				'title'   => __( 'About', 'gplvault' ),
				'content' =>
					'<h2>' . sprintf( __( 'About GPLVault Update Manager %s', 'gplvault' ), GPLVault()->version() ) . '</h2>' .
					'<p>' .
					__( 'The GPLVault Update Manager is the most advanced and flexible plugin to manage GPLVault plugins and themes upgrade process.', 'gplvault' ) .
					'</p>' .
					'<p>' .
					__( 'The plugin is used to upgrade GPLVault items via not only the WP Native Upgrade system but also the custom Plugins upgrade system built into this plugin.', 'gplvault' ) .
					'</p>' .
					'<p>' .
					__( 'You can use either of these ways to upgrade plugins - and if the WP regular upgrade system fails to upgrade any plugin, you can use the custom GPLVault Plugins page to upgrade instead.', 'gplvault' ) .
					'</p>' .
					'<p>' .
					__( 'For your information, themes are still upgraded using the WP regular upgrade system.', 'gplvault' ) .
					'</p>',
			)
		);

		$screen->add_help_tab(
			array(
				'id'      => 'gplvault_tab_settings',
				'title'   => __( 'Settings', 'gplvault' ),
				'content' =>
				'<h2>' . __( 'GPLVault Settings Sections', 'gplvault' ) . '</h2>' .
				'<h3>' . __( 'License Activation', 'gplvault' ) . '</h3>' .
				'<ul>' .
				sprintf(
					'<li><strong>%1$s</strong>: %2$s',
					__( 'API Settings', 'gplvault' ),
					__( 'This section manages license activation with GPLVault server to keep client plugin functional. Once activated successfully, the submission button is disabled to prevent re-submission of the form area. If you Deactivate the license with the plugin or clear local license settings, the button is enabled again. Both input fields are required in this section.', 'gplvault' )
				) .
					sprintf( '<p><strong><em>%1$s</em></strong>: %2$s</p>', __( 'Master Key', 'gplvault' ), __( 'When you purchase any subscription on GPLVault main site, you will be provided a Master API Key. You have to use that key here to activate your license for client plugin and only on successful activation, the client plugin will be able to do its works and pull various information from www.gplvault.com.', 'gplvault' ) ) .
					sprintf( '<p><strong><em>%1$s</em></strong>: %2$s</p>', __( 'Product ID', 'gplvault' ), __( 'Product ID is the unique number of subscription plan you purchased on GPLVault main server.', 'gplvault' ) ) .
				'</li>' .
					sprintf( '<li><strong>%1$s</strong>: %2$s', __( 'License Actions', 'gplvault' ), __( 'This section helps to manage API Settings - mainly, deactivation, checking license status from the main server, and cleaning up local saved license related options from this site (the site client plugin installed on). All buttons are disabled if license is not activated successfully.', 'gplvault' ) ) .
					sprintf( '<p><strong><em>%1$s</em></strong>: %2$s</p>', __( 'Deactivate License Key', 'gplvault' ), __( 'Use this button to deactivate current site from GPLVault site activation list. For your kind information, deactivation occurs automatically when you disable the plugin or uninstall it. It is important to deactivate the site before manually delete, replace plugin files on this server.', 'gplvault' ) ) .
					sprintf( '<p><strong><em>%1$s</em></strong>: %2$s</p>', __( 'Check License', 'gplvault' ), __( 'Sometimes, it is necessary to check communication with the server and status of the License Activation information with main server. You can use this button to check the status of your license on Main Server.', 'gplvault' ) ) .
					sprintf( '<p><strong><em>%1$s</em></strong>: %2$s</p>', __( 'Clear Local Settings', 'gplvault' ), __( 'Please, use this with caution. For strange or unknown reason, there might be a situation where this site entry maybe missing in the Main Server\'s activated sites list. In that case, to activate again, you have to erase locally stored information first. In this kind of special situation, use this button to cleanup local license settings.', 'gplvault' ) ) .
				'</li>' .
				'</ul>' .
				'<h3>' . __( 'Updater Item Exclusion', 'gplvault' ) . '</h3>' .
				'<p>' .
				__( 'The Item Exclusion section is used to exclude plugins or themes from GPLVault Update Manager upgrade coverage. This is important when you have purchased the original copy of an item and you want to upgrade that item from the main vendor.', 'gplvault' ) .
				'<br>' .
					sprintf( '<strong>%1$s</strong>: %2$s', __( 'Notes', 'gplvault' ), __( 'If you want to remove the "Restriction" for all previous blacklisted items, you have to empty the input field and hit "Save" button, this will remove all previous entries.', 'gplvault' ) ) .
				'</p>' .
				'<ul>' .
				sprintf(
					'<li><strong>%1$s</strong>: %2$s</li>',
					__( 'Plugins', 'gplvault' ),
					__( 'Here you will see the list of all installed plugins on this WP installation and you have to pick the plugin you want to exclude from GPLVault Update Manager upgrade process. You can add or remove multiple entries from the select box.', 'gplvault' )
				) .
				sprintf(
					'<li><strong>%1$s</strong>: %2$s</li>',
					__( 'Themes', 'gplvault' ),
					__( 'Select the theme you want from listed entries to exclude from GPLVault Update Manager upgrade process. You can add or remove multiple entries from the select box.', 'gplvault' )
				) .
				'</ul>',
			)
		);

		$screen->add_help_tab(
			array(
				'id'      => 'gplvault_tab_plugins',
				'title'   => __( 'Plugins', 'gplvault' ),
				'content' =>
				'<h2>' . __( 'GPLVault Plugins Upgrade', 'gplvault' ) . '</h2>' .
				sprintf(
					'<p><strong>%1$s</strong>: %2$s</p>',
					__( 'WP Native Upgrade System', 'gplvault' ),
					__( 'From version 4.1.0, we have added WP Regular update system for plugins again. So, you will be able to upgrade GPLVault items from plugins from Dashboard > Updates or Plugins page. Please use the custom update system if you are not able to upgrade any plugin using regular upgrade system.', 'gplvault' )
				),
			)
		);

		$screen->add_help_tab(
			array(
				'id'      => 'gplvault_tab_forbidden',
				'title'   => __( 'Forbidden Error', 'gplvault' ),
				'content' =>
					'<h2>' . __( 'Forbidden Error', 'gplvault' ) . '</h2>' .
					'<p>' .
					__( 'If you are experiencing "Forbidden" error during connecting to our server, most probably, your IP or domain is blocked by our Firewall system.<br> Please consider the following reasons first.', 'gplvault' ) .
					'</p>' .
					'<ul>' .
						'<li>' .
						__( 'The IP is found in any IP Blacklist Database.', 'gplvault' ) .
						'</li>' .
						'<li>' .
						__( 'Our edge server or application server is getting unusual amount of requests from your end that either of them ', 'gplvault' ) .
						'</li>' .
					'</ul><br>' .
					'<p><strong>' . __( 'What you can do!', 'gplvault' ) . '</strong></p>' .
					'<p>' .
					sprintf(
						__(
							'Check your website IP with blacklist IP checker, or use a prominent tool like <a href="%1$s">%2$s</a>.<br>
					But in our experience in many occasions a client\'s claimed IP and the requesting IP are not the same. So please ask your web service provider to know IPs they use to send outbound traffics from their server.',
							'gplvault'
						),
						'https://www.abuseipdb.com/',
						'AbuseIPDB.com'
					) .
					'<br>' .
					'<p><strong>' . __( 'Note:', 'gplvault' ) . '</strong></p>' .
					'<span>' .
					__( 'For security concern, we are not able to resolve this type of issue. It will make our total system vulnerable to severe attacks and we are continually facing DDoS attacks.', 'gplvault' ) .
					'</span><br><br>',

			)
		);
	}

	public static function gv_update_plugins() {
		GPLVault_Helper::update_plugins_data();
	}
}
