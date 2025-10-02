<?php
defined( 'ABSPATH' ) || exit;
/**
 * @var array   $blocked_plugins
 */
?>
<div class="gv-layout__columns">
	<div class="gv-card">
		<div class="gv-card__header">
			<h3 class="gv-card__title"><?php esc_html_e( 'Plugins', 'gplvault' ); ?></h3>
		</div>
		<div class="gv-card__body">
			<div class="gv-card__body-inner">
				<div class="gv-fields__container">
					<div class="gv-fields__item">
						<div
							class="gv-fields__label"><span><?php esc_html_e( 'Select Plugins to Exclude', 'gplvault' ); ?></span><span class="gv-help-tip gv-has-tooltip"
																														data-tippy-placement="top-start"
																														data-tippy-content="<?php esc_attr_e( "Select those plugins which you don't want to upgrade with GPLVault Updater client.", 'gplvault' ); ?>"></span></div>
						<div class="gv-fields__field">
							<select
								class="gv-select2"
								name="gv_blocked_plugins[]"
								id="gv_blocked_plugins"
								data-type="plugin"
								data-placeholder="<?php esc_attr_e( 'Select plugins', 'gplvault' ); ?>"
								multiple
							>
								<?php foreach ( GPLVault_Helper::all_plugins( false ) as $plugin_file => $plugin_data ) : ?>
									<option value="<?php echo esc_attr( $plugin_file ); ?>"<?php echo in_array( $plugin_file, $blocked_plugins, true ) ? ' selected' : ''; ?>><?php echo esc_html( $plugin_data['Name'] ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
					<div class="gv-fields__actions">
						<button
							class="button button-primary"
							id="plugins_exclusion_btn"
							type="button"
							data-context="plugins_exclusion"
							disabled
						>
							<?php esc_html_e( 'Save', 'gplvault' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
