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

if ( ! class_exists( 'WpssoProReviewStamped' ) ) {

	class WpssoProReviewStamped {

		private $p;	// Wpsso class object.

		private $api_base_url = 'https://stamped.io/api/';

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! empty( $this->p->avail[ 'ecom' ][ 'woocommerce' ] ) &&
				$this->is_enabled_for_post_type( 'product' ) &&
					'yes' === get_option( 'woocommerce_enable_reviews' ) ) {

				/*
				 * An is_admin() test is required to make sure the WpssoMessages class is available.
				 */
				if ( $this->p->notice->is_admin_pre_notices() ) {

					$notice_key = 'notice-ratings-reviews-wc-enabled';
					$notice_msg = $this->p->msgs->get( $notice_key, $info = array(
						'svc_title_transl' => _x( 'Stamped.io (Ratings and Reviews)', 'metabox title', 'wpsso' ),
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

			if ( empty( $this->p->options[ 'plugin_stamped_store_hash' ] ) ) {	// Just in case.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: stamp.io store hash is empty' );
				}

				return $mt_og;

			} elseif ( empty( $this->p->options[ 'plugin_stamped_key_public' ] ) ) {	// Just in case.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: stamp.io public key is empty' );
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
			$store_hash  = $this->p->options[ 'plugin_stamped_store_hash' ];
			$key_public  = $this->p->options[ 'plugin_stamped_key_public' ];
			$num_max     = $this->p->options[ 'plugin_ratings_reviews_num_max' ];		// Maximum Number of Reviews.
			$months_max  = $this->p->options[ 'plugin_ratings_reviews_months_max' ];	// Maximum Age of Reviews.
			$from_date   = gmdate( 'm/d/Y', time() - ( $months_max * MONTH_IN_SECONDS ) );
			$have_schema = $this->p->avail[ 'p' ][ 'schema' ] ? true : false;

			$cache_md5_pre  = 'wpsso_r_';	// Transient prefix for api response.
			$cache_type     = 'transient';
			$cache_exp_secs = $this->p->util->get_cache_exp_secs( $cache_md5_pre, $cache_type );

			/*
			 * Set the reference values for admin notices.
			 */
			if ( is_admin() ) {

				$canonical_url = $this->p->util->get_canonical_url( $mod );

				$this->p->util->maybe_set_ref( $canonical_url, $mod, __( 'getting stamp.io ratings and reviews', 'wpsso' ) );
			}

			if ( apply_filters( 'wpsso_og_add_mt_rating', true, $mod ) ||		// Enabled by default.
				apply_filters( 'wpsso_og_add_mt_reviews', $have_schema, $mod ) ) {

				/*
				 * See https://developers.stamped.io/#7d02c323-794e-443f-9512-28373a244854.
				 *
				 * Note that minRating=1 must be included, otherwise only 5 star reviews are returned by the API.
				 */
				$url = arr_query_arg( array(
					'productId' => $product_id,
					'take'      => $num_max,
					'dateFrom'  => $from_date,
					'minRating' => 1,
					'storeUrl'  => $store_hash,
					'apiKey'    => $key_public,
				), $this->api_base_url . 'widget/reviews' );

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

				if ( empty( $res ) || ! is_array( $res ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: API response is empty or not an array' );
					}

					return $mt_og;
				}

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
				}

				if ( isset( $res[ 'rating' ] ) && isset( $res[ 'total' ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'adding product ratings from stamp.io API response' );
						$this->p->debug->log( 'rating = ' . $res[ 'rating' ] );
						$this->p->debug->log( 'total = ' . $res[ 'total' ] );
					}

					$mt_og[ $og_type . ':rating:average' ] = (float) $res[ 'rating' ];
					$mt_og[ $og_type . ':rating:count' ]   = (int) $res[ 'total' ];
					$mt_og[ $og_type . ':rating:worst' ]   = 1;
					$mt_og[ $og_type . ':rating:best' ]    = 5;

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'error: response rating and/or total keys missing' );
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'add rating meta tags is false' );
			}

			/*
			 * Add reviews meta tags.
			 */
			if ( apply_filters( 'wpsso_og_add_mt_reviews', $have_schema, $mod ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'add reviews meta tags is true' );
				}

				if ( ! empty( $res[ 'data' ] ) && is_array( $res[ 'data' ] ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'adding product reviews from stamp.io API response' );
					}

					$review_count = 0;

					foreach ( $res[ 'data' ] as $review ) {

						$review_count++;

						$date_created = isset( $review[ 'dateCreated' ] ) ? date_format( date_create( $review[ 'dateCreated' ] ), 'c' ) : '';

						$single_review = array(
							'review:id'           => isset( $review[ 'id' ] ) ? $review[ 'id' ] : '',
							'review:url'          => '',
							'review:title'        => isset( $review[ 'reviewTitle' ] ) ? $review[ 'reviewTitle' ] : '',
							'review:description'  => isset( $review[ 'reviewMessage' ] ) ? $review[ 'reviewMessage' ] : '',
							'review:created_time' => $date_created,
							'review:author:id'    => '',
							'review:author:name'  => isset( $review[ 'author' ] ) ? $review[ 'author' ] : '',
							'review:rating:value' => isset( $review[ 'reviewRating' ] ) ? (float) $review[ 'reviewRating' ] : 0,
							'review:rating:worst' => 1,
							'review:rating:best'  => 5,
						);

						$mt_og[ $og_type . ':reviews' ][] = $single_review;
					}

					$mt_og[ $og_type . ':review:count' ] = $review_count;

				} elseif ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'error: response data is empty and/or not an array' );
				}

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

		private function is_ignored_url_notice( $url ) {

			$time_left = $this->p->cache->is_ignored_url( $url );

			if ( $time_left ) {

				$notice_msg = sprintf( __( 'There has been a previous error connecting to %s for caching.', 'wpsso' ),
					'<a href="' . $url . '">' . $url . '</a>' ) . ' ';

				$notice_msg .= sprintf( __( 'Requests to retrieve and cache this URL are ignored for another %d second(s)', 'wpsso' ), $time_left );

				$notice_key = 'stamped_ignored_url_' . $url;

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
