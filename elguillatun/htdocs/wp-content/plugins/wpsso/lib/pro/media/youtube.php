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

if ( ! class_exists( 'WpssoProMediaYoutube' ) ) {

	class WpssoProMediaYoutube {

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
			), $prio = 10 );
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

			} elseif ( false === strpos( $args[ 'url' ], 'youtu' ) ) {	// Optimize before preg_match().

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: "youtu" string not found in video URL' );
				}

				return $mt_single_video;

			} elseif ( ! preg_match( '/^.*(youtube\.com|youtube-nocookie\.com|youtu\.be)\/(watch\/?\?v=)?([^\?\&\#<>]+)' .
				'(\?(list)=([^\?\&\#<>]+)|.*)$/', $args[ 'url' ], $match ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: youtube video URL pattern not found' );
				}

				return $mt_single_video;
			}

			if ( $this->p->debug->enabled ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'youtube video URL pattern found' );
				}

				$this->p->debug->log_arr( 'match', $match );
			}

			/*
			 * YouTube video API
			 */
			$list_name = false;
			$vid_name  = isset( $match[ 3 ] ) ? preg_replace( '/^.*\//', '', $match[ 3 ] ) : false;
			$img_name  = 'maxresdefault.jpg?m=default';

			if ( empty( $vid_name ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: video name is empty' );
				}

				return $mt_single_video;

			} elseif ( $vid_name === 'videoseries' ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: video name is videoseries' );
				}

				return $mt_single_video;
			}

			/*
			 * og:video meta tags.
			 */
			$mt_single_video[ 'og:video:secure_url' ]    = 'https://www.youtube.com/embed/' . $vid_name;
			$mt_single_video[ 'og:video:embed_url' ]     = 'https://www.youtube.com/embed/' . $vid_name;
			$mt_single_video[ 'og:video:type' ]          = 'text/html';
			$mt_single_video[ 'og:video:has_video' ]     = true;	// Used by video API modules.
			$mt_single_video[ 'og:video:thumbnail_url' ] = 'https://i.ytimg.com/vi/' . $vid_name . '/' . $img_name;
			$mt_single_video[ 'og:video:has_image' ]     = true;

			unset( $mt_single_video[ 'og:video:url' ] );	// Just in case.

			/*
			 * og:image meta tags.
			 */
			$mt_single_video[ 'og:image:secure_url' ] = 'https://i.ytimg.com/vi/' . $vid_name . '/' . $img_name;

			/*
			 * Define default meta tag values.
			 */
			if ( ! empty( $match[ 6 ] ) && 'list' === $match[ 5 ] ) {

				$list_name = $match[ 6 ];

				$mt_single_video[ 'og:video:secure_url' ] = $mt_single_video[ 'og:video:embed_url' ];
				$mt_single_video[ 'og:video:embed_url' ]  = 'https://www.youtube.com/embed/' . $vid_name . '?list=' . $list_name;
			}

			/*
			 * Get additional meta tags from the YouTube video webpage.
			 *
			 * Note that cached files are automatically removed after WPSSO_CACHE_FILES_EXP_SECS (default is 1 month).
			 *
			 * See WpssoUtilCache->clear_expired_cache_files().
			 */
			$canonical_url = 'https://www.youtube.com/watch?v=' . $vid_name;

			if ( apply_filters( 'wpsso_og_add_media_from_url', $canonical_url, 'facebook' ) ) {

				$cache_md5_pre  = 'wpsso_y_';
				$cache_exp_secs = $this->p->util->get_cache_exp_secs( $cache_md5_pre, $cache_type = 'file' );	// Default is 1 week.
				$throttle_secs  = apply_filters( 'wpsso_og_add_media_throttle_secs', 5, 'youtube' );

				$this->p->media->add_og_video_from_url( $mt_single_video, $canonical_url, $cache_exp_secs, $throttle_secs );

				if ( $this->p->notice->is_admin_pre_notices() ) {

					if ( empty( $mt_single_video[ 'og:video:title' ] ) ) {

						$notice_msg = sprintf( __( 'Video title not found for %s.', 'wpsso' ),
							'<a href="' . $canonical_url . '">' . $canonical_url . '</a>' ) . ' ';

						$notice_key = 'video-title-missing-' . $canonical_url;

						$this->p->notice->err( $notice_msg, null, $notice_key, $dismiss_time = false );
					}

					if ( empty( $mt_single_video[ 'og:video:description' ] ) ) {

						$notice_msg = sprintf( __( 'Video description not found for %s.', 'wpsso' ),
							'<a href="' . $canonical_url . '">' . $canonical_url . '</a>' ) . ' ';

						$notice_key = 'video-description-missing-' . $canonical_url;

						$this->p->notice->warn( $notice_msg, null, $notice_key, $dismiss_time = true );
					}

					if ( empty( $mt_single_video[ 'og:video:upload_date' ] ) ) {

						$notice_msg = sprintf( __( 'Video upload date not found for %s.', 'wpsso' ),
							'<a href="' . $canonical_url . '">' . $canonical_url . '</a>' ) . ' ';

						$notice_key = 'video-upload-date-missing-' . $canonical_url;

						$this->p->notice->err( $notice_msg, null, $notice_key, $dismiss_time = false );
					}
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( $mt_single_video );
			}

			return $mt_single_video;
		}
	}
}
