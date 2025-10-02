<?php
defined( 'ABSPATH' ) || exit;
?>

<script type="text/template" id="tmpl-gv-templates-api-header">
	<div class="gv-card__header-label {{ data.activated ? 'gv-status__success' : 'gv-status__error' }}">
	<# if (data.activated) { #>
	<span class="dashicons dashicons-yes"></span>
	<span class="gv-label__text"><?php esc_html_e( 'Activated', 'gplvault' ); ?></span>
	<# } else { #>
	<span class="dashicons dashicons-no"></span>
	<span class="gv-label__text"><?php esc_html_e( 'Deactivated', 'gplvault' ); ?></span>
	<# } #>
	</div>
</script>
<script type="text/template" id="tmpl-gv-templates-status">
	<div class="gv-admin-section" id="gv_license_status_section">
		<div class="gv-section-header">
			<h2 class="gv-section-header__title"><?php esc_html_e( 'License Status', 'gplvault' ); ?></h2>
			<hr role="presentation">
		</div>
		<div class="gv-summary">
			<div class="gv-summary__item-container">
				<div class="gv-summary__item">
					<div class="gv-summary__item-label">
						<p><?php esc_html_e( 'Status', 'gplvault' ); ?></p>
					</div>
					<div class="gv-summary__item-data">
						<div class="gv-summary__item-value">
							{{ data.activated ? 'Activated' : 'Deactivated' }}
						</div>
					</div>
				</div>
			</div>
			<div class="gv-summary__item-container">
				<div class="gv-summary__item">
					<div class="gv-summary__item-label">
						<p><?php esc_html_e( 'Total Quota', 'gplvault' ); ?></p>
					</div>
					<div class="gv-summary__item-data">
						<div class="gv-summary__item-value">
							{{ data.total_activations_purchased }}
						</div>
					</div>
				</div>
			</div>
			<div class="gv-summary__item-container">
				<div class="gv-summary__item">
					<div class="gv-summary__item-label">
						<p><?php esc_html_e( 'Already Activated', 'gplvault' ); ?></p>
					</div>
					<div class="gv-summary__item-data">
						<div class="gv-summary__item-value">
							{{ data.total_activations }}
						</div>
					</div>
				</div>
			</div>
			<div class="gv-summary__item-container">
				<div class="gv-summary__item">
					<div class="gv-summary__item-label">
						<p><?php esc_html_e( 'Remaining', 'gplvault' ); ?></p>
					</div>
					<div class="gv-summary__item-data">
						<div class="gv-summary__item-value">
							{{ data.activations_remaining }}
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</script>
<?php
