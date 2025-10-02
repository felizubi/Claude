<?php
// phpcs:ignoreFile
defined( 'ABSPATH' ) || exit;
/**
 * @var string $license_status_class
 * @var bool $is_license_activated
 * @var string $gv_license_key
 * @var int $gv_product_id
 */
?>
<div class="gv-admin-section" id="api_settings_section">
	<div class="gv-section-header">
		<h2 class="gv-section-header__title"><?php esc_html_e( 'License Activation', 'gplvault' ); ?></h2>
		<hr role="presentation">
	</div>
	<div class="gv-admin-columns gv-grids gv-grids__columns-auto">
		<div class="gv-layout__columns">
			<div class="gv-card" id="api_settings_column">
				<div class="gv-card__header">
					<h3 class="gv-card__title"><?php esc_html_e( 'API Settings', 'gplvault' ); ?></h3>
					<div class="gv-card__header-label <?php echo esc_attr( $license_status_class ); ?>">
						<?php if ( $is_license_activated ) : ?>
							<span class="dashicons dashicons-yes"></span>
							<span class="gv-label__text"><?php esc_html_e( 'Activated', 'gplvault' ); ?></span>
						<?php else : ?>
							<span class="dashicons dashicons-no"></span>
							<span class="gv-label__text"><?php esc_html_e( 'Deactivated', 'gplvault' ); ?></span>
						<?php endif; ?>
					</div>
				</div>
				<div class="gv-card__body">
					<div class="gv-card__body-inner">
						<div class="gv-fields__container">
							<div class="gv-fields__item">
								<div
									class="gv-fields__label"><span><?php esc_html_e( 'Master Key', 'gplvault' ); ?></span><span class="gv-help-tip gv-has-tooltip"
																																data-tippy-placement="top-start"
																																data-tippy-content="<?php esc_attr_e( 'Enter the Master Key found on GPLVault account section.', 'gplvault' ); ?>"></span></div>
								<div class="gv-fields__field gv-input__field gv-input__pwd">
									<input class="gv-input" type="password" id="api_master_key" name="api_master_key" placeholder="<?php esc_attr_e( 'Enter master key', 'gplvault' ); ?>" value="<?php echo esc_attr( $gv_license_key ); ?>" />
									<button type="button" class="button button-secondary gv-hide-pw hide-if-no-js" data-toggle="0" aria-label="<?php esc_attr_e( 'Show password', 'gplvault' ); ?>">
										<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
									</button>
								</div>
							</div>
							<div class="gv-fields__item">
								<div
									class="gv-fields__label"><span><?php esc_html_e( 'Product ID', 'gplvault' ); ?></span><span class="gv-help-tip gv-has-tooltip"
																																data-tippy-placement="top-start"
																																data-tippy-content="<?php esc_attr_e( 'Enter the Product ID of your purchased subscription on the server.', 'gplvault' ); ?>"></span></div>
								<div class="gv-fields__field gv-input__field">
									<input class="gv-input" type="text" id="api_product_id" name="api_product_id" placeholder="<?php esc_attr_e( 'Enter product id', 'gplvault' ); ?>" value="<?php echo esc_attr( $gv_product_id ); ?>" />
								</div>
							</div>
							<div class="gv-fields__actions">
								<button
									type="button"
									id="gv_activate_api"
									class="button button-primary"
									data-context="license_activation"
									<?php echo $is_license_activated ? 'disabled' : ''; ?>
								><?php esc_html_e( 'Activate', 'gplvault' ); ?></button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="gv-layout__columns">
			<div class="gv-card">
				<div class="gv-card__header">
					<h3 class="gv-card__title"><?php esc_html_e( 'License Actions', 'gplvault' ); ?></h3>
				</div>
				<div class="gv-card__body">
					<div class="gv-card__body-inner">
						<div class="gv-layout__button-wrapper">
							<div class="gv-button__container gv-has-tooltip"
								 data-tippy-content="<?php esc_attr_e( 'Use it to deactivate the license activation for this current site.', 'gplvault' ); ?>">
								<button
									class="button button-danger gv-has-confirmation"
									data-confirmation="<?php esc_attr_e( 'Do you really want to deactivate the license?', 'gplvault' ); ?>"
									type="button"
									id="license_deactivation"
									data-context="license_deactivation"
									<?php echo gv_settings_manager()->get_activation_status() ? '' : 'disabled'; ?>
								><?php esc_html_e( 'Deactivate License', 'gplvault' ); ?></button>

							</div>

							<div class="gv-button__container gv-has-tooltip"
								 data-tippy-content="<?php esc_attr_e( 'Use it to check current status with GPLVault server for your local license settings.', 'gplvault' ); ?>">
								<button
									class="button button-primary"
									type="button"
									id="check_license"
									data-context="check_license"
									<?php echo gv_settings_manager()->get_activation_status() ? '' : 'disabled'; ?>
								><?php esc_html_e( 'Check License', 'gplvault' ); ?></button>

							</div>
							<div class="gv-button__container gv-has-tooltip"
								 data-tippy-content="<?php esc_attr_e( 'Use it to delete your local license settings. You should only use if there is any license conflict with server, but local site still has license settings left.', 'gplvault' ); ?>">
								<button
									class="button button-danger gv-has-confirmation"
									data-confirmation="<?php esc_attr_e( 'Do you really want to delete local license settings?', 'gplvault' ); ?>"
									type="button"
									id="cleanup_settings"
									<?php echo gv_settings_manager()->get_activation_status() ? '' : 'disabled'; ?>
									data-context="cleanup_settings"><?php esc_html_e( 'Clear Local Settings', 'gplvault' ); ?></button>

							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
