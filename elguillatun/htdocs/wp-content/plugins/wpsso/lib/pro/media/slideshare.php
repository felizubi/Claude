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

if ( ! class_exists( 'WpssoProMediaSlideshare' ) ) {

	class WpssoProMediaSlideshare {

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
				'content_videos' => 2,
				'video_details'  => 3,
			), $prio = 40 );
		}

		public function filter_content_videos( $videos, $content ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Example:
			 *
			 *	<object type='application/x-shockwave-flash' wmode='opaque'
			 *		data='http://static.slideshare.net/swf/ssplayer2.swf?id=29776875&doc=album-design-part-3-visuals-140107132112-phpapp01'
			 *			width='650' height='533'>
			 */
			if ( preg_match_all( '/<object[^<>]*? data=[\'"]([^\'"<>]+\.slideshare.net\/swf[^\'"]+)[\'"][^<>]*>/i', $content, $all_matches, PREG_SET_ORDER )  ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( count( $all_matches ) . ' x <object/> slideshare video tag(s) found' );
				}

				foreach ( $all_matches as $media ) {

					$video_url    = $media[ 1 ];
					$video_width  = preg_match( '/ width=[\'"]([0-9]+)[\'"]/i', $media[0], $match ) ? $match[1] : null;
					$video_height = preg_match( '/ height=[\'"]([0-9]+)[\'"]/i', $media[0], $match ) ? $match[1] : null;
					$video_type   = preg_match( '/ type=[\'"]([^\'"<>]+)[\'"]/i', $media[0], $match ) ? $match[1] : 'application/x-shockwave-flash';

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'found video URL: ' . $video_url . ' (' . $video_width . 'x' . $video_height . ')' );
					}

					$videos[] = array(
						'url'    => $video_url,
						'width'  => $video_width,
						'height' => $video_height,
						'type'   => $video_type,
					);
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'no <object/> slideshare video tag(s) found' );
			}

			return $videos;
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

			} elseif ( false === strpos( $args[ 'url' ], 'slideshare.net' ) ) {	// Optimize before preg_match().

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: "slideshare.net" string not found in video URL' );
				}

				return $mt_single_video;

			/*
			 * This matches both the iframe and object URLs.
			 */
			} elseif ( ! preg_match( '/^.*(slideshare\.net)\/.*(\/([0-9]+)|\?id=([0-9]+).*)$/i', $args[ 'url' ], $match ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: slideshare video URL pattern not found' );
				}

				return $mt_single_video;
			}

			if ( $this->p->debug->enabled ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'slideshare video URL pattern found' );
				}

				$this->p->debug->log_arr( 'match', $match );
			}

			/*
			 * Slideshare video API
			 */
			$vid_name = $match[ 3 ] ? $match[ 3 ] : $match[ 4 ];

			$mt_single_video[ 'og:video:embed_url' ] = 'https://www.slideshare.net/slideshow/embed_code/' . $vid_name;

			$api_url = 'https://www.slideshare.net/api/oembed/2?url=' . $mt_single_video[ 'og:video:embed_url' ] . '&format=xml';

			if ( function_exists( 'simplexml_load_string' ) ) {

				$cache_format   = 'raw';
				$cache_type     = 'transient';
				$cache_md5_pre  = 'wpsso_r_';	// Transient prefix for api response.
				$cache_exp_secs = $this->p->util->get_cache_exp_secs( $cache_md5_pre, $cache_type );

				$xml = @simplexml_load_string( $this->p->cache->get( $api_url, $cache_format, $cache_type, $cache_exp_secs, $cache_md5_pre ) );

				if ( ! empty( $xml->html ) ) {

					$mt_single_video[ 'og:video:secure_url' ] = 'https://static.slideshare.net/swf/ssplayer2.swf?id=' . $vid_name;
					$mt_single_video[ 'og:video:has_video' ]  = true;	// Used by video API modules.
					$mt_single_video[ 'og:video:width' ]      = (int) $xml->{'width'};
					$mt_single_video[ 'og:video:height' ]     = (int) $xml->{'height'};

					unset( $mt_single_video[ 'og:video:url' ] );	// Just in case.

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'html missing from returned xml' );
				}

				if ( ! empty( $xml->{'slide-image-baseurl'} ) ) {

					$mt_single_video[ 'og:image:url' ] = 'https:' . (string) $xml->{'slide-image-baseurl'} . '1' .
						(string) $xml->{'slide-image-baseurl-suffix'};

					$mt_single_video[ 'og:image:width' ] = preg_replace( '/[^0-9]/', '',
						(string) $xml->{'slide-image-baseurl-suffix'} );

					$mt_single_video[ 'og:video:thumbnail_url' ] = 'https:' . (string) $xml->{'slide-image-baseurl'} . '1' .
						(string) $xml->{'slide-image-baseurl-suffix'};

					$mt_single_video[ 'og:video:has_image' ] = true;

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'slide-image-baseurl missing from returned xml' );
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

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( $mt_single_video );
			}

			return $mt_single_video;
		}
	}
}
