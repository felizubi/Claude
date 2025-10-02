<?php
// phpcs:ignoreFile
defined( 'ABSPATH' ) || exit;
/**
 * @var array   $blocked_themes
 */
?>
<div class="gv-layout__columns">
	<div class="gv-card">
		<div class="gv-card__header">
			<h3 class="gv-card__title"><?php esc_html_e( 'Themes', 'gplvault' ); ?></h3>
<!--			<small>(--><?php //esc_html_e( 'Theme upgrade feature will be added soon.', 'gplvault' ); ?><!--)</small>-->
		</div>
		<div class="gv-card__body">
			<div class="gv-card__body-inner">
				<div class="gv-fields__container">
					<div class="gv-fields__item">
						<div
							class="gv-fields__label"><span><?php esc_html_e( 'Select Themes to Exclude', 'gplvault' ); ?></span><span class="gv-help-tip gv-has-tooltip"
																															  data-tippy-placement="top-start"
																															  data-tippy-content="<?php esc_attr_e( "Select those themes which you don't want to upgrade with GPLVault Updater client.", 'gplvault' ); ?>"></span></div>
						<div class="gv-fields__field">
							<select
								class="gv-select2"
								name="gv_blocked_themes[]"
								id="gv_blocked_themes"
								data-type="theme"
								data-placeholder="<?php esc_attr_e( 'Select themes', 'gplvault' ); ?>"
								multiple
							>
								<?php foreach ( GPLVault_Helper::all_themes( false ) as $theme_dir => $theme_data ) : ?>
									<option value="<?php echo esc_attr( $theme_dir ); ?>"<?php echo in_array( $theme_dir, $blocked_themes, true ) ? ' selected' : ''; ?>><?php echo esc_html( $theme_data->get( 'Name' ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</div>
					<div class="gv-fields__actions">
						<button
							class="button button-primary"
							id="themes_exclusion_btn"
							type="button"
							data-context="themes_exclusion"
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
