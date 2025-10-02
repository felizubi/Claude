<?php
defined( 'ABSPATH' ) || exit;
/**
 * @var array $license_summary
 */
?>
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
						<?php
						printf(
							'%s',
							esc_attr( $license_summary['activated'] )
								? esc_html__( 'Activated', 'gplvault' ) :
								esc_html__( 'Deactivated', 'gplvault' )
						);
						?>
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
						<?php echo esc_html( $license_summary['total_activations_purchased'] ); ?>
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
						<?php echo esc_html( $license_summary['total_activations'] ); ?>
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
						<?php echo esc_html( $license_summary['activations_remaining'] ); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
