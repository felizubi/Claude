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

if ( ! class_exists( 'WpssoProMediaWistia' ) ) {

	class WpssoProMediaWistia {

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
			), $prio = 30 );
		}

		public function filter_content_videos( $videos, $content ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Example:
			 *
			 *	<div id="wistia_wb36s0vwcg" class="wistia_embed" style="width:640px;height:360px;">&nbsp;</div>
			 *
			 * 	<div class="wistia_embed wistia_async_j38ihh83m5" style="height:349px;width:620px">
			 */
			if ( preg_match_all( '/<div[^<>]*? (id=[\'"]wistia_([^\'"<>]+)[\'"][^<>]* class=[\'"]wistia_embed[\'"]|' .
				'class=[\'"]wistia_embed wistia_async_([^\'"<>]+)[^\'"]*[\'"])[^<>]*>/i', $content,
					$all_matches, PREG_SET_ORDER )  ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( count( $all_matches ) . ' x <div/> wistia_embed video tag(s) found' );
				}

				foreach ( $all_matches as $media ) {

					$video_url    = 'http://fast.wistia.net/embed/iframe/' . ( empty( $media[ 2 ] ) ? $media[ 3 ] : $media[ 2 ] );
					$video_width  = preg_match( '/ width:([0-9]+)px/i', $media[ 0 ], $match ) ? $match[ 1 ] : null;
					$video_height = preg_match( '/ height:([0-9]+)px/i', $media[ 0 ], $match ) ? $match[ 1 ] : null;

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'found video URL: ' . $video_url . ' (' . $video_width . 'x' . $video_height . ')' );
					}

					$videos[] = array(
						'url'    => $video_url,
						'width'  => $video_width,
						'height' => $video_height,
					);
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'no <div/> wistia_embed video tag(s) found' );
			}

			/*
			 * example: <a href="//fast.wistia.net/embed/iframe/wb36s0vwcg?popover=true" class="wistia-popover[height=360,playerColor=7b796a,width=640]">
			 */
			if ( preg_match_all( '/<a[^<>]*? href=[\'"]([^\'"]+)[\'"][^<>]* class=[\'"]wistia-popover\[([^\]]+)\][\'"][^<>]*>/i',
				$content, $all_matches, PREG_SET_ORDER )  ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( count( $all_matches ) . ' x <a/> wistia-popover video tag(s) found' );
				}

				foreach ( $all_matches as $media ) {

					$video_url    = $media[ 1 ];
					$video_width  = preg_match( '/ width=([0-9]+)/i', $media[ 2 ], $match ) ? $match[ 1 ] : null;
					$video_height = preg_match( '/ height=([0-9]+)/i', $media[ 2 ], $match ) ? $match[ 1 ] : null;

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'found video URL: ' . $video_url . ' (' . $video_width . 'x' . $video_height . ')' );
					}

					$videos[] = array(
						'url'    => $video_url,
						'width'  => $video_width,
						'height' => $video_height,
					);
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'no <a/> wistia-popover video tag(s) found' );
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

			} elseif ( false === strpos( $args[ 'url' ], 'wistia' ) ) {	// Optimize before preg_match().

				if ( false === strpos( $args[ 'url' ], 'wi.st' ) ) {	// Optimize before preg_match().

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: "wistia" or "wi.st" string not found in video URL' );
					}

					return $mt_single_video;
				}

			} elseif ( ! preg_match( '/^.*(wistia\.net|wistia\.com|wi\.st)\/([^\?\&\#<>]+).*$/i', $args[ 'url' ], $match ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: wistia video URL pattern not found' );
				}

				return $mt_single_video;
			}

			if ( $this->p->debug->enabled ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'wistia video URL pattern found' );
				}

				$this->p->debug->log_arr( 'match', $match );
			}

			/*
			 * Wistia video API
			 */
			$vid_name = preg_replace( '/^.*\//', '', $match[ 2 ] );

			$mt_single_video[ 'og:video:type' ] = 'application/x-shockwave-flash';

			/*
			 * embedType can be 'seo' or 'twitter_card_tags'.
			 */
			$api_url = 'https://fast.wistia.com/oembed.xml?url=http%3A//home.wistia.com/medias/' .
				$vid_name . '%3FembedType=seo&width=' . $this->p->options[ 'og_img_width' ];

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'wistia api url = ' . $api_url );
			}

			if ( function_exists( 'simplexml_load_string' ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting xml from ' . $api_url );
				}

				$cache_md5_pre  = 'wpsso_r_';	// Transient prefix for api response.
				$cache_type     = 'transient';
				$cache_exp_secs = $this->p->util->get_cache_exp_secs( $cache_md5_pre, $cache_type );

				$xml = @simplexml_load_string( $this->p->cache->get( $api_url, $format = 'raw', $cache_type, $cache_exp_secs, $cache_md5_pre ) );

				if ( ! empty( $xml->title ) ) {

					$mt_single_video[ 'og:video:title' ] = (string) $xml->title;
				}

				if ( ! empty( $xml->html ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'getting meta from the html property' );
					}

					$html = '<html><body>' . $xml->html . '</body></html>';	// Add a proper html container.

					$meta = $this->p->util->get_html_head_meta( $html, $query = '//meta|//noscript', $libxml_errors = false );

					if ( isset( $meta[ 'meta' ] ) ) {

						foreach ( $meta as $m ) {		// Loop through all meta tags.

							foreach ( $m as $a ) {		// Loop through all attributes for that meta tag.

								$meta_type = key( $a );
								$meta_name = reset( $a );

								switch ( $meta_type . '-' . $meta_name ) {

									case 'itemprop-description':

										if ( ! empty( $a[ 'textContent' ] ) ) {

											$mt_single_video[ 'og:video:description' ] = $a[ 'textContent' ];
										}

										break;

									case 'itemprop-duration':

										if ( ! empty( $a[ 'content' ] ) ) {

											$mt_single_video[ 'og:video:duration' ] = $a[ 'content' ];
										}

										break;

									case 'itemprop-embedUrl':
									case 'itemprop-embedURL':

										if ( ! empty( $a[ 'content' ] ) ) {

											if ( ! empty( $this->p->options[ 'og_vid_autoplay' ] ) ) {

												if ( false !== strpos( $a[ 'content' ], 'autoPlay=false' ) ) {

													$a[ 'content' ] = preg_replace( '/autoPlay=false/',
														'autoPlay=true', $a[ 'content' ] );
												}
											}

											$secure_url = preg_replace( '/^http:\/\/embed\./',
												'https://embed-ssl.', $a[ 'content' ] );

											$mt_single_video[ 'og:video:secure_url' ] = $secure_url;
											$mt_single_video[ 'og:video:has_video' ]  = true;	// Used by video API modules.

											unset( $mt_single_video[ 'og:video:url' ] );	// Just in case.
										}

										break;

									case 'itemprop-thumbnailUrl':
									case 'itemprop-thumbnailURL':

										if ( ! empty( $a[ 'content' ] ) ) {

											$mt_single_video[ 'og:video:thumbnail_url' ] = $a[ 'content' ];
										}

										break;

									case 'itemprop-uploadDate':

										if ( ! empty( $a[ 'content' ] ) ) {

											$mt_single_video[ 'og:video:upload_date' ] = $a[ 'content' ];
										}

										break;
								}
							}
						}

						$mt_single_video[ 'og:video:embed_url' ] = 'https://fast.wistia.net/embed/iframe/' .
							$vid_name . '?plugin[socialbar-v1][on]=false&twitter=true';

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'meta missing from the returned array' );
					}

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'html missing from returned xml' );
				}

				if ( ! empty( $xml->thumbnail_url ) ) {

					$img_url    = (string) $xml->thumbnail_url;
					$img_width  = (string) $xml->thumbnail_width;
					$img_height = (string) $xml->thumbnail_height;

					$mt_single_video[ 'og:video:width' ]     = $img_width;
					$mt_single_video[ 'og:video:height' ]    = $img_height;
					$mt_single_video[ 'og:video:has_image' ] = true;

					$secure_url = preg_replace( '/^http:\/\/embed\./', 'https://embed-ssl.', $img_url );

					$mt_single_video[ 'og:image:secure_url' ] = $secure_url;
					$mt_single_video[ 'og:image:width' ]      = $img_width;
					$mt_single_video[ 'og:image:height' ]     = $img_height;

					unset( $mt_single_video[ 'og:image:url' ] );	// Just in case.

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

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( $mt_single_video );
			}

			return $mt_single_video;
		}
	}
}
