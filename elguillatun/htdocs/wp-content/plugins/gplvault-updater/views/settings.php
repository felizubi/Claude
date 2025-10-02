<?php
defined( 'ABSPATH' ) || exit;

/** @var GPLVault_Settings_Manager $settings_manager */
$api_settings         = $settings_manager->get_api_settings();
$blocked_plugins      = $settings_manager->blocked_plugins();
$blocked_themes       = $settings_manager->blocked_themes();
$is_license_activated = $settings_manager->license_is_activated();
$gv_license_key       = $api_settings[ GPLVault_Settings_Manager::API_KEY ] ?? '';
$gv_product_id        = $api_settings[ GPLVault_Settings_Manager::PRODUCT_KEY ] ?? '';
$license_status_class = $is_license_activated ? 'gv-status__success' : 'gv-status__error';

$license_summary = $is_license_activated ? $settings_manager->license_status() : array();
?>
<div class="wrap gv-wrapper" id="gv_settings_wrapper">
	<div class="gv-layout">

		<div class="gv-layout__primary">
			<div class="gv-layout__main gv-grids gv-grids__full">
				<div id="gv_license_status_wrapper">
					<?php if ( $is_license_activated && ! empty( $license_summary ) ) : ?>
						<?php GPLVault_Admin::load_partial( 'settings/status', compact( 'license_summary' ) ); ?>
					<?php endif; ?>
				</div>
				<?php
					GPLVault_Admin::load_partial(
						'settings/license',
						compact( 'is_license_activated', 'license_status_class', 'gv_license_key', 'gv_product_id' )
					);
					?>

				<div class="gv-admin-section" id="gv_items_exclusion">
					<div class="gv-section-header">
						<h2 class="gv-section-header__title"><?php esc_html_e( 'Updater Item Exclusion', 'gplvault' ); ?></h2>
						<hr role="presentation">
					</div>
					<div class="gv-admin-columns gv-grids gv-grids__columns-auto">
					<?php
						GPLVault_Admin::load_partial( 'settings/blocked-plugins', compact( 'blocked_plugins' ) );
						GPLVault_Admin::load_partial( 'settings/blocked-themes', compact( 'blocked_themes' ) );
					?>
					</div>
				</div>
			</div>
		</div>


	</div>
</div> <!-- .wrap -->
<?php
