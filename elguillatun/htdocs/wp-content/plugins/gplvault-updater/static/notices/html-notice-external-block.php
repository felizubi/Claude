<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$host = parse_url( GV_UPDATER_API_URL, PHP_URL_HOST );
?>
 <div id="gv_external_blocking_notice" class="notice notice-error">
	 <?php /* translators: 1: title of affecting items 2: host to add in whitelist 3: constant name where host should be added */ ?>
                        <p><?php printf( __( '<strong>Warning!</strong> You\'re blocking external requests which means you won\'t be able to get %1$s updates. Please add %2$s to %3$s.', 'gplvault' ), __('GPLVault Plugins and Themes', 'gplvault'), '<strong>' . $host . '</strong>', '<code>WP_ACCESSIBLE_HOSTS</code>' ); ?></p>
</div>
<?php
