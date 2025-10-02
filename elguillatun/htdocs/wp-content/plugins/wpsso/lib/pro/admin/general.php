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

if ( ! class_exists( 'WpssoProAdminGeneral' ) ) {

	class WpssoProAdminGeneral {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! empty( $this->p->options[ 'plugin_wpsso_tid' ] ) &&
				SucomPlugin::is_plugin_active( 'wpsso-um/wpsso-um.php' ) ) {

				$this->p->util->add_plugin_filters( $this, array(
					'mb_general_open_graph_videos_rows' => 2,
				) );

			} else WpssoLoader::load_plugin_std( $plugin, 'admin', 'general' );
		}

		/*
		 * SSO > General Settings > Videos tab.
		 */
		public function filter_mb_general_open_graph_videos_rows( $table_rows, $form ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$max_media_items = $this->p->cf[ 'form' ][ 'max_media_items' ];

			$table_rows[ 'og_vid_max' ] = $form->get_tr_hide( $in_view = 'basic', 'og_vid_max' ) .
				$form->get_th_html( _x( 'Maximum Videos to Include', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'og_vid_max' ) .
				'<td>' . $form->get_select( 'og_vid_max', range( 0, $max_media_items ),
					$css_class = 'short', $css_id = '', $is_assoc = true ) . '</td>';

			$table_rows[ 'og_vid_prev_img' ] = '' .
				$form->get_th_html( _x( 'Include Video Preview Images', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'og_vid_prev_img' ) .
				'<td>' . $form->get_checkbox( 'og_vid_prev_img' ) . $this->p->msgs->preview_images_are_first() . '</td>';

			$table_rows[ 'og_vid_autoplay' ] = '' .
				$form->get_th_html( _x( 'Force Autoplay when Possible', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'og_vid_autoplay' ) .
				'<td>' . $form->get_checkbox( 'og_vid_autoplay' ) . '</td>';

			$check_embed_html = '';

			foreach ( $this->p->cf[ 'form' ][ 'embed_media' ] as $opt_key => $opt_label ) {

				$check_embed_html .= '<p>' . $form->get_checkbox( $opt_key ) . ' ' . _x( $opt_label, 'option value', 'wpsso' ) . '</p>';
			}

			$table_rows[ 'plugin_embed_media' ] = '' .
				$form->get_th_html( _x( 'Detect Embedded Media', 'option label', 'wpsso' ),
					$css_class = '', $css_id = 'plugin_embed_media' ) .
				'<td>' . $check_embed_html . '</td>';

			return $table_rows;
		}
	}
}
