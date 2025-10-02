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

if ( ! class_exists( 'WpssoProMediaWpvideoshortcode' ) ) {

	class WpssoProMediaWpvideoshortcode {

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
			), $prio = 110 );
		}

		public function filter_content_videos( $videos, $content ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * Example video shortcode:
			 *
			 * <video class="wp-video-shortcode" id="video-6937-1" width="850" height="478"
			 *	poster="https://f9affd5f1846f0e624eb81ef-fzsx3idmvfhl.netdna-ssl.com/wp-content/uploads/2018/01/cover-min.png"
			 *		preload="none" controls="controls">
			 *
			 *	<source type="video/mp4" src="https://f9affd5f1846f0e624eb81ef-fzsx3idmvfhl.netdna-ssl.com/wp-content/uploads/2018/01/ranking_2.mp4" />
			 *
			 *	<a href="https://f9affd5f1846f0e624eb81ef-fzsx3idmvfhl.netdna-ssl.com/wp-content/uploads/2018/01/ranking_2.mp4">
			 *		https://www.forwardpathway.com/wp-content/uploads/2018/01/ranking_2.mp4
			 *	</a>
			 *
			 * </video>
			 */
			if ( preg_match_all( '/<video class="wp-video-shortcode[^"]*" [^<>]*>[^<>]*' .
				'<source type=[\'"]([^\'"<>]+)[\'"] src=[\'"]([^\'"<>]+)[\'"].*<\/video>/Ui',
					$content, $all_matches, PREG_SET_ORDER )  ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( count( $all_matches ).' x <video/> WordPress video shortcode tag(s) found' );
				}

				foreach ( $all_matches as $media ) {

					$video_type     = $media[ 1 ];
					$video_url      = remove_query_arg( '_', $media[ 2 ] );	// Remove the instance id.
					$video_width    = preg_match( '/ width=[\'"]([0-9]+)[\'"]/i', $media[ 0 ], $match ) ? $match[ 1 ] : null;
					$video_height   = preg_match( '/ height=[\'"]([0-9]+)[\'"]/i', $media[ 0 ], $match ) ? $match[ 1 ] : null;
					$video_prev_url = preg_match( '/ poster=[\'"]([^\'"<>]+)[\'"]/i', $media[ 0 ], $match ) ? $match[ 1 ] : null;

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'found video URL: ' . $video_url );
					}

					$videos[] = array(
						'url'        => $video_url,
						'stream_url' => $video_url,
						'width'      => $video_width,
						'height'     => $video_height,
						'type'       => $video_type,		// Example: 'video/mp4'.
						'prev_url'   => $video_prev_url,
					);
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'no <video/> WordPress video shortcode tag(s) found' );
			}

			return $videos;
		}
	}
}
