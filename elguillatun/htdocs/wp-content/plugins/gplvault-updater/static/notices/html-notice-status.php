<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>
<div id="gv_activation_message" class="notice notice-error">
    <p><strong><?php esc_html_e( 'Attention!', 'gplvault' ); ?></strong>
        &#8211; <?php _e( 'Maybe your license is not activated or unable to be verified by server. Please check your <strong><a href="https://gplvault.com/my-account" target="_blank" title="GPLVault Account">Account page</a></strong> or contact Admin.', 'gplvault' ); ?></p>
</div>
<?php
