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

if ( ! class_exists( 'WpssoProMediaUpscale' ) ) {

	class WpssoProMediaUpscale {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			add_filter( 'image_resize_dimensions', array( $this, 'upscale_image_resize_dimensions' ), 1000, 6 );
		}

		/*
		 * This filter does not receive the image ID or size_name. get_attachment_image_src() in the WpssoMedia class saves
		 * / sets the image information (pid, size_name, etc) before calling the image_make_intermediate_size() function
		 * (and others), which eventually can get us here. We can use WpssoMedia::get_image_src_args() to retrieve this
		 * image information and check for our own image sizes, for use in status and warning notices.
		 */
		public function upscale_image_resize_dimensions( $ret, $orig_w, $orig_h, $dst_w, $dst_h, $crop ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log_args( array(
					'ret'    => $ret,
					'orig_w' => $orig_w,
					'orig_h' => $orig_h,
					'dst_w'  => $dst_w,
					'dst_h'  => $dst_h,
					'crop'   => $crop,
				) );
			}

			/*
			 * Check input arguments:
			 *
			 * - The original image must have a width and height larger than 0.
			 * - If we're not cropping, at least one new side must be larger than 0.
			 * - If we're cropping, then both new sides must be larger than 0.
			 */
			if ( $orig_w <= 0 || $orig_h <= 0 || ( ! $crop && ( $dst_w <= 0 && $dst_h <= 0 ) ) || ( $crop && ( $dst_w <= 0 || $dst_h <= 0 ) ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'upscale skipped: one or more input arguments is invalid' );
				}

				return $ret;
			}

			/*
			 * Check if upscaling is required:
			 *
			 * - If the original image is large enough to be downsized, then we don't need to upscale.
			 * - If we're not cropping, and one side is large enough, then we're ok to downsize.
			 * - If we're cropping, then both sides have to be large enough to downsize.
			 */
			$is_sufficient_w = $dst_w > 0 && $orig_w >= $dst_w ? true : false;
			$is_sufficient_h = $dst_h > 0 && $orig_h >= $dst_h ? true : false;

			if ( ( ! $crop && ( $is_sufficient_w || $is_sufficient_h ) ) || ( $crop && ( $is_sufficient_w && $is_sufficient_h ) ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'upscale skipped: original image dimensions are sufficient' );
				}

				return $ret;
			}

			/*
			 * get_attachment_image_src() in the WpssoMedia class saves / sets the image information (pid, size_name,
			 * etc) before calling the image_make_intermediate_size() function (and others). Returns null if no image
			 * information was set (presumably because we arrived here without  passing through our own method).
			 */
			$img_src_args = WpssoMedia::get_image_src_args();

			if ( $this->p->debug->enabled ) {

				if ( empty( $img_src_args ) ) {

					$this->p->debug->log( 'no image source information from media class' );

				} else {

					$this->p->debug->log_arr( 'img_info', $img_src_args );
				}
			}

			/*
			 * By default, only upscale our own image sizes (ie. having passed through our own method). $img_src_args will
			 * be null (or empty) if WordPress or another plugin is requesting the resize. In that case, our own image
			 * sizes will not be upscaled until we request them ourselves. Set the WPSSO_IMAGE_UPSCALE_ALL constant to
			 * true in order to upscale all image sizes. SucomUtil::get_const() returns null if the constant is not
			 * defined.
			 */
			$upscale_all = apply_filters( 'wpsso_image_upscale_all', SucomUtil::get_const( 'WPSSO_IMAGE_UPSCALE_ALL', $undef = false ) );

			if ( ( empty( $img_src_args[ 'size_name' ] ) || 0 !== strpos( $img_src_args[ 'size_name' ], 'wpsso-' ) ) && ! $upscale_all ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'upscale skipped: not wpsso size name and upscale all is false' );
				}

				return $ret;
			}

			/*
			 * Check for pre-filtered / inherited resize values.
			 */
			if ( is_array( $ret ) && count( $ret ) === 8 ) {

				list( $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h ) = $ret;

				$size_ratio = max( $dst_w / $orig_w, $dst_h / $orig_h );

			} elseif ( $crop ) {

				$size_ratio = max( $dst_w / $orig_w, $dst_h / $orig_h );

				$src_w = round( $dst_w / $size_ratio );
				$src_h = round( $dst_h / $size_ratio );

				if ( ! is_array( $crop ) || count( $crop ) !== 2 ) {

					$crop = array( 'center', 'center' );
				}

				list( $crop_x, $crop_y ) = $crop;

				if ( $crop_x === 'left' ) {

					$src_x = 0;

				} elseif ( $crop_x === 'right' ) {

					$src_x = $orig_w - $src_w;

				} else {

					$src_x = floor( ( $orig_w - $src_w ) / 2 );
				}

				if ( $crop_y === 'top' ) {

					$src_y = 0;

				} elseif ( $crop_y === 'bottom' ) {

					$src_y = $orig_h - $src_h;

				} else {

					$src_y = floor( ( $orig_h - $src_h ) / 2 );
				}

			} else {

				$src_x = 0;
				$src_y = 0;

				$src_w = $orig_w;
				$src_h = $orig_h;

				/*
				 * Calculate width and height ratios between the new and original sizes. Calculate any missing width
				 * / height values for the new size - input sanitation assures us that we have at least one positive
				 * value.
				 */
				if ( $dst_w > 0 ) {

					$w_ratio = $dst_w / $orig_w;
				}

				if ( $dst_h > 0 ) {

					$h_ratio = $dst_h / $orig_h;

					if ( $dst_w <= 0 ) {

						$dst_w   = $orig_w * $h_ratio;
						$w_ratio = $dst_w / $orig_w;
					}

				} else {

					$dst_h   = $orig_h * $w_ratio;
					$h_ratio = $dst_h / $orig_h;
				}

				$min_ratio = min( $w_ratio, $h_ratio );
				$max_ratio = max( $w_ratio, $h_ratio );

				if ( (int) round( $orig_w * $max_ratio ) > $dst_w || (int) round( $orig_h * $max_ratio ) > $dst_h ) {

					$ratio = $min_ratio;

				} else {

					$ratio = $max_ratio;
				}

				$dst_w = max( 1, (int) round( $orig_w * $ratio ) );
				$dst_h = max( 1, (int) round( $orig_h * $ratio ) );

				$size_ratio = max( $dst_w / $orig_w, $dst_h / $orig_h );
			}

			$size_diff = round( ( $size_ratio * 100 ) - 100 );

			$max_diff = apply_filters( 'wpsso_image_upscale_max', $this->p->options[ 'plugin_upscale_pct_max' ], $img_src_args );

			if ( ! empty( $img_src_args[ 'size_name' ] ) ) {

				/*
				 * Add notice only if the admin notices have not already been shown.
				 */
				if ( $this->p->notice->is_admin_pre_notices() ) {

					$size_label = $this->p->util->get_image_size_label( $img_src_args[ 'size_name' ] );	// Returns pre-translated labels.

					if ( $size_diff > $max_diff ) {

						$notice_key = 'wp_' . $img_src_args[ 'pid' ] . '_' . $orig_w . 'x' . $orig_h . '_' .
							$img_src_args[ 'size_name' ] . '_' . $dst_w . 'x' . $dst_h . '_upscaled';

						$warn_msg = __( 'Failed to upscale image ID %1$s of %2$s by %3$s from %4$s to %5$s for the %6$s image size (exceeds %7$s maximum upscale setting).', 'wpsso' );

						$this->p->notice->warn( sprintf( $warn_msg, $img_src_args[ 'pid' ], $orig_w . 'x' . $orig_h, $size_diff . '%',
							$src_w . 'x' . $src_h, $dst_w . 'x' . $dst_h, '<b>' . $size_label . '</b>',
								$max_diff . '%' ), null, $notice_key, true );

					} else {

						$inf_msg = __( 'Image ID %1$s of %2$s has been upscaled by %3$s from %4$s to %5$s for the %6$s image size.', 'wpsso' );

						$this->p->notice->inf( sprintf( $inf_msg, $img_src_args[ 'pid' ], $orig_w . 'x' . $orig_h, $size_diff . '%',
							$src_w . 'x' . $src_h, $dst_w . 'x' . $dst_h, '<b>' . $size_label . '</b>' ) );
					}
				}
			}

			if ( $size_diff > $max_diff ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'upscale skipped: ' . $orig_w . 'x' . $orig_h . ' from ' . $src_w . 'x' . $src_h .
						' to ' . $dst_w . 'x' . $dst_h . ' is ' . $size_diff . '% diff and exceeds ' . $max_diff . '% limit' );
				}

				return $ret;
			}

			/*
			 * The WPSSO_IMAGE_UPSCALE_TEST constant and associated filter allows us to display passed / failed notices
			 * without actually making any changes (saving the image). SucomUtil::get_const() will return false if the
			 * constant is not defined.
			 */
			$upscale_test = apply_filters( 'wpsso_image_upscale_test', SucomUtil::get_const( 'WPSSO_IMAGE_UPSCALE_TEST', $undef = false ), $img_src_args );

			if ( $upscale_test ) {

				return $ret;

			} else {

				/*
				 * Return an array( dst_x, dst_y, src_x, src_y, dst_w, dst_h, src_w, src_h ).
				 */
				return array( 0, 0, (int) $src_x, (int) $src_y, (int) $dst_w, (int) $dst_h, (int) $src_w, (int) $src_h );
			}
		}
	}
}
