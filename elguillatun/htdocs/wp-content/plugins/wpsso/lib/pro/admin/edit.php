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

if ( ! class_exists( 'WpssoProAdminEdit' ) ) {

	class WpssoProAdminEdit {

		private $p;	// Wpsso class object.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! empty( $this->p->options[ 'plugin_wpsso_tid' ] ) &&
				SucomPlugin::is_plugin_active( 'wpsso-um/wpsso-um.php' ) ) {

				$this->p->util->add_plugin_filters( $this, array(
					'mb_sso_edit_media_prio_video_rows' => 4,
				) );

			} else WpssoLoader::load_plugin_std( $plugin, 'admin', 'edit' );
		}

		public function filter_mb_sso_edit_media_prio_video_rows( $table_rows, $form, $head_info, $mod ) {

			$size_name     = 'wpsso-opengraph';
			$media_request = array( 'og_vid_url', 'og_vid_title', 'og_vid_desc', 'og_vid_stream_url', 'og_vid_width', 'og_vid_height', 'og_vid_upload' );
			$media_info    = $this->p->media->get_media_info( $size_name, $media_request, $mod, $md_pre = 'none' );
			$input_limits  = WpssoConfig::get_input_limits();	// Uses a local cache.

			$form_rows = array(
				'subsection_priority_video' => array(
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Priority Video Information', 'metabox title', 'wpsso' )
				),
				'og_vid_embed' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Video Embed HTML', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_vid_embed',
					'content'  => $form->get_textarea( 'og_vid_embed' ),
				),
				'og_vid_url' => array(
					'th_class' => 'medium',
					'label'    => _x( 'or a Video URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_vid_url',
					'content'  => $form->get_input_video_url( 'og_vid' ),
				),
				'subsection_priority_video_info' => array(
					'td_class' => 'subsection',
					'header'   => 'h5',
					'label'    => _x( 'Video Information from Video API', 'metabox title', 'wpsso' )
				),
				'og_vid_title' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Video Name', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_vid_title',
					'content'  => $form->get_input( 'og_vid_title', $css_class = 'wide', $css_id = '',
						$input_limits[ 'og_title' ], $media_info[ 'og_vid_title' ] ),
				),
				'og_vid_desc' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Video Description', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_vid_desc',
					'content'  => $form->get_textarea( 'og_vid_desc', $css_class = '', $css_id = '',
						$input_limits[ 'og_desc' ], $media_info[ 'og_vid_desc' ] ),
				),
				'og_vid_stream_url' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Video Stream URL', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_vid_stream_url',
					'content'  => $form->get_input( 'og_vid_stream_url', $css_class = 'wide', $css_id = '',
						$len = 0, $media_info[ 'og_vid_stream_url' ] ),
				),
				'og_vid_dimensions' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Video Dimensions', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_vid_dimensions',
					'content'  => $form->get_input_video_dimensions( 'og_vid', $media_info ),
				),
				'og_vid_upload_date' => array(
					'th_class' => 'medium',
					'label'    => _x( 'Video Upload Date', 'option label', 'wpsso' ),
					'tooltip'  => 'meta-og_vid_upload_date',
					'content'  => $form->get_date_time_timezone( 'og_vid_upload', $media_info ),
				),
			);

			return $form->get_md_form_rows( $table_rows, $form_rows, $head_info, $mod );
		}
	}
}
