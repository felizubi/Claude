<?php
/*
 * IMPORTANT: READ THE LICENSE AGREEMENT CAREFULLY. BY INSTALLING, COPYING, RUNNING, OR OTHERWISE USING THE WPSSO CORE PREMIUM
 * APPLICATION, YOU AGREE  TO BE BOUND BY THE TERMS OF ITS LICENSE AGREEMENT. IF YOU DO NOT AGREE TO THE TERMS OF ITS LICENSE
 * AGREEMENT, DO NOT INSTALL, RUN, COPY, OR OTHERWISE USE THE WPSSO CORE PREMIUM APPLICATION.
 *
 * License URI: https://wpsso.com/wp-content/plugins/wpsso/license/premium.txt
 *
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoProMediaGravatar' ) ) {

	class WpssoProMediaGravatar {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Filter priorities:
			 *
			 *	10   = Youtube Videos.
			 *	20   = Vimeo Videos.
			 * 	30   = Wistia Videos.
			 *	40   = Slideshare Presentations.
			 * 	60   = Facebook Videos.
			 *	80   = Soundcloud Tracks.
			 *	100  = WP Media Library Video Blocks.
			 *	110  = WP Media Library Video Shortcodes.
			 *	1000 = Gravatar Images.
			 */
			$this->p->util->add_plugin_filters( $this, array(
				'get_user_options' => 3,
				'user_image_urls'  => 4,
			), $prio = 1000 );
		}

		/*
		 * Remove the gravatar image URL from the user meta options in favor of adding it back with the
		 * filter_user_image_urls() filter.
		 */
		public function filter_get_user_options( array $md_opts, $user_id, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$url_part = '.gravatar.com/avatar/';

			if ( isset( $md_opts[ 'og_img_url' ] ) && false !== strpos( $md_opts[ 'og_img_url' ], $url_part ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'removing gravatar image URL from og_img_url option' );
				}

				$md_opts[ 'og_img_url' ] = '';
			}

			return $md_opts;
		}

		public function filter_user_image_urls( $urls, $size_names, $user_id, $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			static $local_cache = array();

			if ( ! isset( $local_cache[ $user_id ] ) ) {	// Key does not exist or value is not null.

				$img_email = strtolower( trim( get_the_author_meta( 'user_email', $user_id ) ) );
				$img_size  = $this->p->media->get_gravatar_size();
				$img_url   = $img_email ? 'https://secure.gravatar.com/avatar/' . md5( $img_email ) . '.jpg?d=mp&s=' . $img_size : '';

				$local_cache[ $user_id ] = $img_url;	// Empty string or image URL.
			}

			if ( ! empty( $local_cache[ $user_id ] ) ) {	// Not false or empty string.

				$urls[] = $local_cache[ $user_id ];
			}

			return $urls;
		}
	}
}
