<?php
/*
 * IMPORTANT: READ THE LICENSE AGREEMENT CAREFULLY. BY INSTALLING, COPYING, RUNNING, OR OTHERWISE USING THE WPSSO CORE PREMIUM
 * APPLICATION, YOU AGREE  TO BE BOUND BY THE TERMS OF ITS LICENSE AGREEMENT. IF YOU DO NOT AGREE TO THE TERMS OF ITS LICENSE
 * AGREEMENT, DO NOT INSTALL, RUN, COPY, OR OTHERWISE USE THE WPSSO CORE PREMIUM APPLICATION.
 *
 * License URI: https://wpsso.com/wp-content/plugins/wpsso/license/premium.txt
 *
 * Copyright 2020-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoProReviewJudgeme' ) ) {

	class WpssoProReviewJudgeme {

		private $p;	// Wpsso class object.

		private $api_base_url    = null;
		private $api_shop_domain = null;
		private $api_shop_token  = null;

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			/*
			 * The Judge.me Product Reviews for WooCommerce plugin is required. Un-configure the service if the plugin
			 * is missing. If the plugin is re-activated, the WpssoIntegReviewJudgemeForWc class constructor will
			 * automatically re-configure the service.
			 */
			if ( ! class_exists( 'JudgeMe' ) ) {

				$this->p->options[ 'plugin_ratings_reviews_svc' ] = 'none';
				$this->p->options[ 'plugin_judgeme_shop_domain' ] = '';
				$this->p->options[ 'plugin_judgeme_shop_token' ]  = '';

				return;	// Stop here.
			}

			$this->api_base_url    = 'https://judge.me/api/v1/';
			$this->api_shop_domain = $this->p->options[ 'plugin_judgeme_shop_domain' ];
			$this->api_shop_token  = $this->p->options[ 'plugin_judgeme_shop_token' ];

			if ( ! empty( $this->p->avail[ 'ecom' ][ 'woocommerce' ] ) &&
				$this->is_enabled_for_post_type( 'product' ) &&
					'yes' === get_option( 'woocommerce_enable_reviews' ) ) {

				if ( $this->p->notice->is_admin_pre_notices() ) {

					$notice_key = 'notice-ratings-reviews-wc-enabled';
					$notice_msg = $this->p->msgs->get( $notice_key, $info = array(
						'svc_title_transl' => _x( 'Judge.me (Ratings and Reviews)', 'metabox title', 'wpsso' ),
					) );

					$this->p->notice->err( $notice_msg, $user_id = null, $notice_key );
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: WooCommerce product reviews are enabled' );
				}

				return;	// Stop here.
			}

			$this->p->util->add_plugin_filters( $this, array(
				'og' => 2,
			), $prio = 2000 );	// Run after the WPSSO RAR add-on.
		}

		public function filter_og( array $mt_og, array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( empty( $this->api_shop_domain ) ) {	// Just in case.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: judge.me shop domain is empty' );
				}

				return $mt_og;

			} elseif ( empty( $this->api_shop_token ) ) {	// Just in case.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: judge.me shop token is empty' );
				}

				return $mt_og;

			} elseif ( empty( $mt_og[ 'og:type' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: og:type is empty and required' );
				}

				return $mt_og;

			} elseif ( empty( $mod[ 'is_post' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: module object is not a post' );
				}

				return $mt_og;

			} elseif ( empty( $mod[ 'id' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: module id is empty' );
				}

				return $mt_og;

			} elseif ( empty( $mod[ 'post_type' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: module post type is empty' );
				}

				return $mt_og;

			} elseif ( 'publish' !== $mod[ 'post_status' ] ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: post status is not published' );
				}

				return $mt_og;

			} elseif ( $mod[ 'is_post_type_archive' ] ) {	// The post ID may be 0.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: post type is an archive' );
				}

				return $mt_og;

			} elseif ( ! $this->is_enabled_for_post_type( $mod[ 'post_type' ] ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: disabled for post type ' . $mod[ 'post_type' ] );
				}

				return $mt_og;
			}

			$og_type     = $mt_og[ 'og:type' ];
			$product_id  = $mod[ 'id' ];
			$num_max     = $this->p->options[ 'plugin_ratings_reviews_num_max' ];		// Maximum Number of Reviews.
			$months_max  = $this->p->options[ 'plugin_ratings_reviews_months_max' ];	// Maximum Age of Reviews.
			$reviews     = array();
			$have_schema = $this->p->avail[ 'p' ][ 'schema' ] ? true : false;

			$cache_md5_pre  = 'wpsso_r_';	// Transient prefix for api response.
			$cache_type     = 'transient';
			$cache_exp_secs = $this->p->util->get_cache_exp_secs( $cache_md5_pre, $cache_type );	// Default is DAY_IN_SECONDS.

			/*
			 * Set the reference values for admin notices.
			 */
			if ( is_admin() ) {

				$canonical_url = $this->p->util->get_canonical_url( $mod );

				$this->p->util->maybe_set_ref( $canonical_url, $mod, __( 'getting judge.me ratings and reviews', 'wpsso' ) );
			}

			if ( apply_filters( 'wpsso_og_add_mt_rating', true, $mod ) ||		// Enabled by default.
				apply_filters( 'wpsso_og_add_mt_reviews', $have_schema, $mod ) ) {

				$url = add_query_arg( array( 
					'shop_domain'         => $this->api_shop_domain,
					'api_token'           => $this->api_shop_token,
					'product_external_id' => $product_id,
					'page'                => 1,
					'per_page'            => $num_max * 3,
				), $this->api_base_url . 'reviews' );

				if ( $this->is_ignored_url_notice( $url ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: URL is ignored' );
					}

					return $mt_og;
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'getting API data for product ID ' . $product_id );
				}

				$res = $this->p->cache->get( $url, $format = 'raw', $cache_type = 'transient', $cache_exp_secs, $cache_md5_pre );

				$res = json_decode( $res, $assoc = true );

				if ( empty( $res[ 'reviews' ] ) || ! is_array( $res[ 'reviews' ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: API response is empty or not an array' );
					}

					return $mt_og;
				}

				$reviews = $this->get_sanitized_reviews( $res[ 'reviews' ], $num_max, $months_max );

			} else {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: add rating and reviews meta tags is false' );
				}

				return $mt_og;
			}

			/*
			 * Add rating meta tags.
			 */
			if ( apply_filters( 'wpsso_og_add_mt_rating', true, $mod ) ) {	// Enabled by default.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'add rating meta tags is true' );
					$this->p->debug->log( 'adding product ratings from judge.me API response' );
				}

				$mt_og[ $og_type . ':rating:average' ] = $this->get_rating_average( $reviews );
				$mt_og[ $og_type . ':rating:count' ]   = $this->get_rating_count( $reviews );
				$mt_og[ $og_type . ':rating:worst' ]   = 1;
				$mt_og[ $og_type . ':rating:best' ]    = 5;

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'add rating meta tags is false' );
			}

			/*
			 * Add reviews meta tags.
			 */
			if ( apply_filters( 'wpsso_og_add_mt_reviews', $have_schema, $mod ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'add reviews meta tags is true' );
					$this->p->debug->log( 'adding product reviews from stamp.io API response' );
				}

				$review_count = 0;

				foreach ( $reviews as $review ) {

					$review_count++;

					if ( empty( $review[ 'published' ] ) ) continue;

					if ( ! empty( $review[ 'hidden' ] ) ) continue;

					$single_review = array(
						'review:id'           => isset( $review[ 'id' ] ) ? $review[ 'id' ] : '',
						'review:url'          => '',
						'review:title'        => isset( $review[ 'title' ] ) ? $review[ 'title' ] : '',
						'review:description'  => isset( $review[ 'body' ] ) ? $review[ 'body' ] : '',
						'review:created_time' => isset( $review[ 'created_at' ] ) ? $review[ 'created_at' ] : '',
						'review:updated_time' => isset( $review[ 'updated_at' ] ) ? $review[ 'updated_at' ] : '',
						'review:author:id'    => isset( $review[ 'reviewer' ][ 'id' ] ) ? $review[ 'reviewer' ][ 'id' ] : '',
						'review:author:name'  => isset( $review[ 'reviewer' ][ 'name' ] ) ? $review[ 'reviewer' ][ 'name' ] : '',
						'review:rating:value' => isset( $review[ 'rating' ] ) ? (float) $review[ 'rating' ] : 0,
						'review:rating:worst' => 1,
						'review:rating:best'  => 5,
						'review:image'        => array(),
						'review:video'        => array(),
					);

					/* if ( ! empty( $review[ 'pictures' ] ) && is_array( $review[ 'pictures' ] ) ) {

						foreach ( $review[ 'pictures' ] as $image ) {

							if ( empty( $image[ 'urls' ][ 'original' ] ) ) continue;

							if ( ! empty( $image[ 'hidden' ] ) ) continue;

							$single_review[ 'review:image' ][] = $this->p->media->get_mt_single_image_url( $image[ 'urls' ][ 'original' ] );
						}
					} */

					$mt_og[ $og_type . ':reviews' ][] = $single_review;
				}

				$mt_og[ $og_type . ':review:count' ] = $review_count;

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'add reviews meta tags is false' );
			}

			/*
			 * Restore previous reference values for admin notices.
			 */
			if ( is_admin() ) {

				$this->p->util->maybe_unset_ref( $canonical_url );
			}

			return $mt_og;
		}

		private function get_sanitized_reviews( array $reviews, $num_max, $months_max ) {

			$secs_max  = $months_max * MONTH_IN_SECONDS;
			$from_time = time() - $secs_max;

			foreach ( $reviews as $num => $review ) {

				if ( ! empty( $review[ 'hidden' ] ) ||
					empty( $review[ 'published' ] ) ||
					empty( $review[ 'rating' ] ) ||
					empty( $review[ 'created_at' ] ) || strtotime( $review[ 'created_at' ] ) < $from_time ) {

						unset( $reviews[ $num ] );

						continue;
				};
			}

			return array_slice( $reviews, 0, $num_max );
		}

		private function get_rating_average( array $reviews ) {

			$ratings = array();

			foreach ( $reviews as $num => $review ) $ratings[] = $review[ 'rating' ];

			return count( $ratings ) ? (float) array_sum( $ratings ) / count( $ratings ) : null;	// Avoid dividing by 0.
		}

		private function get_rating_count( array $reviews ) {

			return (int) count( $reviews );
		}

		private function is_ignored_url_notice( $url ) {

			$time_left = $this->p->cache->is_ignored_url( $url );

			if ( $time_left ) {

				$notice_msg = sprintf( __( 'There has been a previous error connecting to %s for caching.', 'wpsso' ),
					'<a href="' . $url . '">' . $url . '</a>' ) . ' ';

				$notice_msg .= sprintf( __( 'Requests to retrieve and cache this URL are ignored for another %d second(s)', 'wpsso' ), $time_left );

				$notice_key = 'judgeme_ignored_url_' . $url;

				$this->p->notice->warn( $notice_msg, null, $notice_key );
			}

			return $time_left;
		}

		private function is_enabled_for_post_type( $post_type ) {

			$svc_enabled = empty( $this->p->options[ 'plugin_ratings_reviews_for_' . $post_type ] ) ? false : true;

			$filter_name = SucomUtil::sanitize_hookname( 'wpsso_ratings_reviews_for_' . $post_type );

			return apply_filters( $filter_name, $svc_enabled );
		}
	}
}
