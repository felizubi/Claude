<?php
defined( 'ABSPATH' ) or die( 'Cheatin\' uh?' );
/*
Plugin Name: Redirect http to https
Author: Jonathan (WP Rocket Team)
Author URI: http://wp-rocket.me
*/

add_filter( 'before_rocket_htaccess_rules', '__redirect_https' );
function __redirect_https( $marker ) {
    $redirection = '# Redirect http to https' . PHP_EOL;
    $redirection .= 'RewriteEngine On' . PHP_EOL;
    $redirection .= 'RewriteCond %{HTTPS} !on' . PHP_EOL;
    $redirection .= 'RewriteCond %{SERVER_PORT} !^443$' . PHP_EOL;
    $redirection .= 'RewriteCond %{HTTP:X-Forwarded-Proto} !https' . PHP_EOL;
    $redirection .= 'RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]' . PHP_EOL;
    $redirection .= '# END https redirect' . PHP_EOL . PHP_EOL;
    $marker = $redirection . $marker;
    return $marker;
}

