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

if ( ! class_exists( 'WpssoProMediaFacebook' ) ) {

	class WpssoProMediaFacebook {

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
				'video_details' => 3,
			), $prio = 60 );
		}

		public function filter_video_details( $mt_single_video, $args, $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! empty( $args[ 'attach_id' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: have attachment ID' );
				}

				return $mt_single_video;

			} elseif ( ! empty( $mt_single_video[ 'og:video:has_video' ] ) ) {	// Used by video API modules.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: have video is true' );
				}

				return $mt_single_video;

			} elseif ( false === strpos( $args[ 'url' ], 'facebook.com' ) ) {	// Optimize before preg_match().

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: "facebook.com" string not found in video URL' );
				}

				return $mt_single_video;

			/*
			 * Note that forward-slashes in the 'href' query value are encoded as %2F.
			 *
			 * Example: https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2Ffacebook%2Fvideos%2F10153231379946729%2F&width=500&show_text=false&appId=525239184171769&height=281
			 */
			} elseif ( preg_match( '/^.*(facebook\.com)\/plugins\/video.php\?href=([^\/\?\&\#<>]+).*$/', $args[ 'url' ], $match ) ) {

				$embed_url = $match[ 0 ];
				$video_url = urldecode( $match[ 2 ] );

			/*
			 * Example: https://www.facebook.com/DrDainHeer/videos/943226206036691/
			 */
			} elseif ( preg_match( '/^(.*facebook\.com\/.*\/videos\/[^\?\#]+).*$/', $args[ 'url' ], $match ) ) {

				$embed_url = 'https://www.facebook.com/plugins/video.php?href=' . urlencode( $match[ 1 ] );
				$video_url = $match[ 1 ];

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: facebook video URL pattern not found' );
				}

				return $mt_single_video;
			}

			if ( $this->p->debug->enabled ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'facebook video URL pattern found' );
				}

				$this->p->debug->log_arr( 'match', $match );

				$this->p->debug->log( 'facebook embed url = ', $embed_url );
				$this->p->debug->log( 'facebook video url = ', $video_url );
			}

			/*
			 * Facebook video API.
			 */
			$cache_md5_pre  = 'wpsso_r_';	// Transient prefix for api response.
			$cache_type     = 'transient';
			$cache_exp_secs = $this->p->util->get_cache_exp_secs( $cache_md5_pre, $cache_type );

			$video_html = $this->p->cache->get( $embed_url, $format = 'raw', $cache_type, $cache_exp_secs, $cache_md5_pre );

			if ( empty( $video_html ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: error caching ' . $embed_url );
				}

				return $mt_single_video;
			}

			if ( preg_match( '/"(hd|sd)_src_no_ratelimit":"([^"]+)"/', $video_html, $match ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'decoding mp4 url = ' . $match[ 2 ] );
				}

				$mp4_url = str_replace( '\/', '/', SucomUtil::replace_unicode_escape( $match[ 2 ] ) );

				if ( SucomUtil::is_https( $mp4_url ) ) {	// Just in case.

					$mt_single_video[ 'og:video:secure_url' ] = $mp4_url;
					$mt_single_video[ 'og:video:stream_url' ] = $mp4_url;	// VideoObject contentUrl.

					unset( $mt_single_video[ 'og:video:url' ] );	// Just in case.

				} else {

					$mt_single_video[ 'og:video:url' ] = $mp4_url;

					if ( empty( $mt_single_video[ 'og:video:stream_url' ] ) ) {

						$mt_single_video[ 'og:video:stream_url' ] = $mp4_url;
					}
				}

				$mt_single_video[ 'og:video:has_video' ] = true;	// Used by video API modules.
				$mt_single_video[ 'og:video:type' ]      = 'video/mp4';
				$mt_single_video[ 'og:video:embed_url' ] = $embed_url;

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: hd|sd_src_no_ratelimit not in ' . $embed_url );
				}

				return $mt_single_video;
			}

			/*
			 * Check for preview image.
			 */
			if ( preg_match( '/<img .* src="([^"]+\.jpg[^"]*)"/', $video_html, $match ) ) {

				$img_url = html_entity_decode( $match[ 1 ] );

				if ( SucomUtil::is_https( $img_url ) ) {	// Just in case.

					$mt_single_video[ 'og:image:secure_url' ]    = $img_url;
					$mt_single_video[ 'og:video:thumbnail_url' ] = $img_url;

					unset( $mt_single_video[ 'og:image:url' ] );	// Just in case.

				} else {

					$mt_single_video[ 'og:image:url' ] = $img_url;

					if ( empty( $mt_single_video[ 'og:video:thumbnail_url' ] ) ) {

						$mt_single_video[ 'og:video:thumbnail_url' ] = $img_url;
					}
				}

				$mt_single_video[ 'og:video:has_image' ] = true;
			}

			/*
			 * Check for video title.
			 */
			if ( preg_match( '/<a .* href="' . str_replace( '/', '\/', $video_url ) . '"[^>]*><span [^>]+>([^<]+)<\/span><\/a>/', $video_html, $match ) ) {

				$mt_single_video[ 'og:video:title' ] = html_entity_decode( SucomUtil::decode_utf8( $match[ 1 ] ) );
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( $mt_single_video );
			}

			return $mt_single_video;
		}
	}
}
