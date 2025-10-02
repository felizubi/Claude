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

if ( ! class_exists( 'WpssoProReviewShopperApproved' ) ) {

	class WpssoProReviewShopperApproved {

		private $p;	// Wpsso class object.

		private $api_base_url = 'https://api.shopperapproved.com/';

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
						'svc_title_transl' => _x( 'Shopper Approved (Ratings and Reviews)', 'metabox title', 'wpsso' ),
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

			if ( empty( $this->p->options[ 'plugin_shopperapproved_site_id' ] ) ) {	// Just in case.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: shopper approved site id is empty' );
				}

				return $mt_og;

			} elseif ( empty( $this->p->options[ 'plugin_shopperapproved_token' ] ) ) {	// Just in case.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: shopper approved token is empty' );
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
			$site_id     = $this->p->options[ 'plugin_shopperapproved_site_id' ];
			$token       = $this->p->options[ 'plugin_shopperapproved_token' ];
			$num_max     = $this->p->options[ 'plugin_ratings_reviews_num_max' ];		// Maximum Number of Reviews.
			$months_max  = $this->p->options[ 'plugin_ratings_reviews_months_max' ];	// Maximum Age of Reviews.
			$from_date   = gmdate( 'Y-m-d', time() - ( $months_max * MONTH_IN_SECONDS ) );
			$have_schema = $this->p->avail[ 'p' ][ 'schema' ] ? true : false;

			$cache_md5_pre  = 'wpsso_r_';	// Transient prefix for api response.
			$cache_type     = 'transient';
			$cache_exp_secs = $this->p->util->get_cache_exp_secs( $cache_md5_pre, $cache_type );

			/*
			 * Set the reference values for admin notices.
			 */
			if ( is_admin() ) {

				$canonical_url = $this->p->util->get_canonical_url( $mod );

				$this->p->util->maybe_set_ref( $canonical_url, $mod, __( 'getting shopper approved ratings and reviews', 'wpsso' ) );
			}

			/*
			 * Add rating meta tags.
			 *
			 * Example JSON from the Shopper Approved API:
			 *
			 * https://api.shopperapproved.com/aggregates/products/xxxxx/xxxx?token=xxxxxxxxxx&xml=false
			 *
			 * {
			 *	"certificate_url": "https://www.shopperapproved.com/reviews/product/xxxxxxxxxxx/xxxxxxxxxxxxxx/12345678",
			 *	"product_totals": {
			 *		"average_rating": 4.86,
			 *		"total_reviews": 100,
			 *		"total_with_comments": 100
			 *	}
			 * }
			 */
			if ( apply_filters( 'wpsso_og_add_mt_rating', true, $mod ) ) {	// Enabled by default.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'add rating meta tags is true' );
				}

				$url = add_query_arg( array(
					'token' => $token,
					'from'  => $from_date,
					'limit' => $num_max,
					'xml'   => 'false',
				), $this->api_base_url . 'aggregates/products/' . $site_id . '/' . $product_id );

				if ( ! $this->is_ignored_url_notice( $url ) ) {

					$res = $this->p->cache->get( $url, $format = 'raw', $cache_type = 'transient', $cache_exp_secs, $cache_md5_pre );

					$res = json_decode( $res, $assoc = true );

					if ( ! empty( $res[ 'product_totals' ] ) && is_array( $res[ 'product_totals' ] ) ) {

						if ( isset( $res[ 'product_totals' ][ 'average_rating' ] ) && isset( $res[ 'product_totals' ][ 'total_reviews' ] ) ) {

							if ( $this->p->debug->enabled ) {

								$this->p->debug->log( 'adding product ratings from shopper approved API response' );
							}

							$mt_og[ $og_type . ':rating:average' ] = (float) $res[ 'product_totals' ][ 'average_rating' ];
							$mt_og[ $og_type . ':rating:count' ]   = (int) $res[ 'product_totals' ][ 'total_reviews' ];
							$mt_og[ $og_type . ':rating:worst' ]   = 1;
							$mt_og[ $og_type . ':rating:best' ]    = 5;

						} elseif ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'error: response average_rating and/or total_reviews keys missing' );
						}

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'error: response product_totals key missing' );
					}
				}

			} elseif ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'add rating meta tags is false' );
			}

			/*
			 * Add reviews meta tags.
			 *
			 * Example JSON from the Shopper Approved API:
			 *
			 * https://api.shopperapproved.com/products/reviews/xxxxx/xxxx?token=xxxxxxxxxx&xml=false
			 *
			 * {
			 *	"12345678": {
			 *		"review_id": 12345678,
			 *		"display_name": "John Smith",
			 *		"date": "Sun, 12 Jul 2020 09:00:00 GMT",
			 *		"product_id": "1234",
			 *		"rating": 5.0,
			 *		"comments": "Review text.",
			 *		"public": 1
			 *	}
			 * }
			 */
			if ( apply_filters( 'wpsso_og_add_mt_reviews', $have_schema, $mod ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'add reviews meta tags is true' );
				}

				$url = add_query_arg( array(
					'token' => $token,
					'from'  => $from_date,
					'limit' => $num_max,
					'xml'   => 'false',
				), $this->api_base_url . 'products/reviews/' . $site_id . '/' . $product_id );

				if ( ! $this->is_ignored_url_notice( $url ) ) {

					$res = $this->p->cache->get( $url, $format = 'raw', $cache_type = 'transient', $cache_exp_secs, $cache_md5_pre );

					$res = json_decode( $res, $assoc = true );

					if ( ! empty( $res ) && is_array( $res ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'adding product reviews from shopper approved API response' );
						}

						$review_count = 0;

						foreach ( $res as $review ) {

							if ( empty( $review[ 'public' ] ) ) {

								continue;
							}

							$review_count++;

							$date_created = isset( $review[ 'date' ] ) ? date_format( date_create( $review[ 'date' ] ), 'c' ) : '';

							$single_review = array(
								'review:id'           => isset( $review[ 'review_id' ] ) ? $review[ 'review_id' ] : '',
								'review:url'          => isset( $review[ 'url' ] ) ? $review[ 'url' ] : '',
								'review:title'        => '',
								'review:description'  => isset( $review[ 'comments' ] ) ? $review[ 'comments' ] : '',
								'review:created_time' => $date_created,
								'review:author:id'    => '',
								'review:author:name'  => isset( $review[ 'display_name' ] ) ? $review[ 'display_name' ] : '',
								'review:rating:value' => isset( $review[ 'rating' ] ) ? (float) $review[ 'rating' ] : 0,
								'review:rating:worst' => 1,
								'review:rating:best'  => 5,
							);

							$mt_og[ $og_type . ':reviews' ][] = $single_review;
						}

						$mt_og[ $og_type . ':review:count' ] = $review_count;

					} elseif ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'error: response is empty and/or not an array' );
					}
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

				$notice_key = 'shopperapproved_ignored_url_' . $url;

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
