<?php
/**
* @package   Avion
* @author    YOOtheme http://www.yootheme.com
* @copyright Copyright (C) YOOtheme GmbH
* @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
*/

// check compatibility
if (version_compare(PHP_VERSION, '5.3', '>=')) {

    // bootstrap warp
    require(__DIR__.'/warp.php');
}

// Google fonts
function fz_enqueue_google_fonts() {
    wp_enqueue_style( 'google-fonts', 'https://fonts.googleapis.com/css?family=Montserrat|Lato' );
}
add_action( 'wp_enqueue_scripts', 'fz_enqueue_google_fonts' );

// WPSSO Topics list
add_filter( 'wpsso_article_topics', 'filter_wpsso_topics', 10, 1 );
function filter_wpsso_topics( $topics = array() ) {
    $topics[] = 'Arte y cultura';
    $topics[] = 'Artes visuales';
    $topics[] = 'Cine';
    $topics[] = 'Cultura pop';
    $topics[] = 'Danza';
    $topics[] = 'Escultura';
    $topics[] = 'Fotografía';
    $topics[] = 'Ilustración';
    $topics[] = 'Literatura';
    $topics[] = 'Música';
    $topics[] = 'Ópera';
    $topics[] = 'Pintura';
    $topics[] = 'Serie de televisión';
    $topics[] = 'Teatro';
    return $topics;
}
