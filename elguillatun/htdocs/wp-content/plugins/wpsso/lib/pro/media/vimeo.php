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

if ( ! class_exists( 'WpssoProMediaVimeo' ) ) {

	class WpssoProMediaVimeo {

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
			), $prio = 20 );
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

			} elseif ( false === strpos( $args[ 'url' ], 'vimeo.com' ) ) {	// Optimize before preg_match().

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: "vimeo.com" string not found in video URL' );
				}

				return $mt_single_video;

			} elseif ( ! preg_match( '/^.*(vimeo\.com)\/([^<>]+\/)?([^\/\?\&\#<>]+).*$/', $args[ 'url' ], $match ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: vimeo video URL pattern not found' );
				}

				return $mt_single_video;
			}

			if ( $this->p->debug->enabled ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'vimeo video URL pattern found' );
				}

				$this->p->debug->log_arr( 'match', $match );
			}

			/*
			 * Vimeo video API
			 */
			$vid_name  = preg_replace( '/^.*\//', '', $match[ 3 ] );
			$autoplay  = empty( $this->p->options[ 'og_vid_autoplay' ] ) ? '' : '?autoplay=1';	// Maybe force autoplay.
			$video_url = 'https://vimeo.com/moogaloop.swf?clip_id=' . $vid_name . $autoplay;

			$mt_single_video[ 'og:video:secure_url' ] = $video_url;
			$mt_single_video[ 'og:video:has_video' ]  = true;	// Used by video API modules.
			$mt_single_video[ 'og:video:type' ]       = 'application/x-shockwave-flash';
			$mt_single_video[ 'og:video:embed_url' ]  = 'https://player.vimeo.com/video/' . $vid_name . $autoplay;

			unset( $mt_single_video[ 'og:video:url' ] );	// Just in case.

			$api_url = 'https://vimeo.com/api/oembed.xml?url=http%3A//vimeo.com/' . $vid_name;

			if ( function_exists( 'simplexml_load_string' ) ) {

				/*
				 * Note that a 'CURLOPT_REFERER' value is required for non-public videos (ie. using a whitelist).
				 *
				 * See https://developer.vimeo.com/api/oembed/videos#embedding-videos-with-domain-privacy
				 */
				$curl_opts = array(
					'CURLOPT_REFERER' => get_home_url(),
				);

				$cache_md5_pre  = 'wpsso_r_';	// Transient prefix for api response.
				$cache_type     = 'transient';
				$cache_exp_secs = $this->p->util->get_cache_exp_secs( $cache_md5_pre, $cache_type );

				$xml = @simplexml_load_string( $this->p->cache->get( $api_url, $format = 'raw', $cache_type, $cache_exp_secs, $cache_md5_pre, $curl_opts ) );

				if ( ! empty( $xml->title ) ) {

					$mt_single_video[ 'og:video:title' ] = $this->p->util->cleanup_html_tags( $xml->title );
				}

				if ( ! empty( $xml->description[ 0 ] ) ) {

					$mt_single_video[ 'og:video:description' ] = $this->p->util->cleanup_html_tags( $xml->description[ 0 ] );
				}

				if ( ! empty( $xml->duration ) ) {

					$mt_single_video[ 'og:video:duration' ] = 'PT' . (string) $xml->duration . 'S';
				}

				if ( ! empty( $xml->upload_date ) ) {

					if ( function_exists( 'date_format' ) ) {	// Available since PHP v5.2.

						$mt_single_video[ 'og:video:upload_date' ] = date_format( date_create( (string) $xml->upload_date ), 'c' );
					}
				}

				if ( ! empty( $xml->thumbnail_url ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'setting og:video and og:image from vimeo api xml' );
					}

					$img_url    = (string) $xml->thumbnail_url;
					$img_width  = (string) $xml->thumbnail_width;
					$img_height = (string) $xml->thumbnail_height;

					$mt_single_video[ 'og:video:width' ]         = $img_width;
					$mt_single_video[ 'og:video:height' ]        = $img_height;
					$mt_single_video[ 'og:video:thumbnail_url' ] = $img_url;
					$mt_single_video[ 'og:video:has_video' ]     = true;
					$mt_single_video[ 'og:video:has_image' ]     = true;

					$mt_single_video[ 'og:image:url' ]    = $img_url;
					$mt_single_video[ 'og:image:width' ]  = $img_width;
					$mt_single_video[ 'og:image:height' ] = $img_height;

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'thumbnail_url missing from returned xml' );
				}

			} else {

				$func_name = 'simplexml_load_string()';
				$func_url  = __( 'https://secure.php.net/manual/en/function.simplexml-load-string.php', 'wpsso' );
				$error_msg = sprintf( __( 'The <a href="%1$s">PHP %2$s function</a> is not available.', 'wpsso' ),
					$func_url, '<code>' . $func_name . '</code>' ) . ' ';

				$error_msg .= __( 'Please contact your hosting provider to have the missing PHP function installed.', 'wpsso' );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $func_name . ' function is missing' );
				}

				if ( $this->p->notice->is_admin_pre_notices() ) {

					$this->p->notice->err( $error_msg );
				}

				SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg, $strip_html = true );
			}

			/*
			 * Used for X (Twitter) player card meta tags.
			 */
			$mt_single_video[ 'og:video:iphone_name' ] = 'Vimeo';
			$mt_single_video[ 'og:video:iphone_id' ]   = '425194759';
			$mt_single_video[ 'og:video:iphone_url' ]  = 'vimeo://app.vimeo.com/videos/' . $vid_name;

			$mt_single_video[ 'og:video:ipad_name' ] = 'Vimeo';
			$mt_single_video[ 'og:video:ipad_id' ]   = '425194759';
			$mt_single_video[ 'og:video:ipad_url' ]  = 'vimeo://app.vimeo.com/videos/' . $vid_name;

			$mt_single_video[ 'og:video:googleplay_name' ] = 'Vimeo';
			$mt_single_video[ 'og:video:googleplay_id' ]   = 'com.vimeo.android.videoapp';
			$mt_single_video[ 'og:video:googleplay_url' ]  = 'vimeo://app.vimeo.com/videos/' . $vid_name;

			/*
			 * Facebook AppLink meta tags.
			 */
			$mt_single_video[ 'al:ios:app_name' ]     = 'Vimeo';
			$mt_single_video[ 'al:ios:app_store_id' ] = '425194759';
			$mt_single_video[ 'al:ios:url' ]          = 'vimeo://app.vimeo.com/videos/' . $vid_name;

			$mt_single_video[ 'al:android:app_name' ] = 'Vimeo';
			$mt_single_video[ 'al:android:package' ]  = 'com.vimeo.android.videoapp';
			$mt_single_video[ 'al:android:url' ]      = 'vimeo://app.vimeo.com/videos/' . $vid_name;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( $mt_single_video );
			}

			return $mt_single_video;
		}
	}
}
