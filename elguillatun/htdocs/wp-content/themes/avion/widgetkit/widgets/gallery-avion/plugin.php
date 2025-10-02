<?php
/**
* @package   Avion
* @author    YOOtheme http://www.yootheme.com
* @copyright Copyright (C) YOOtheme GmbH
* @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
*/

return array(

    'name' => 'widget/gallery-avion',

    'main' => 'YOOtheme\\Widgetkit\\Widget\\Widget',

    'config' => array(

        'name'  => 'gallery-avion',
        'label' => 'Gallery Avion',
        'core'  => false,
        'icon'  => 'plugins/widgets/gallery-avion/widget.svg',
        'view'  => 'plugins/widgets/gallery-avion/views/widget.php',
        'item'  => array('title', 'content', 'media'),
        'settings' => array(
            'grid'                 => 'default',
            'gutter'               => 'default',
            'gutter_dynamic'       => '20',
            'gutter_v_dynamic'     => '',
            'filter'               => 'none',
            'tag-list'             => array(),
            'filter_align'         => 'left',
            'filter_all'           => true,
            'columns'              => '1',
            'columns_small'        => 0,
            'columns_medium'       => 0,
            'columns_large'        => 0,
            'columns_xlarge'       => 0,
            'animation'            => 'none',

            'image_width'          => 'auto',
            'image_height'         => 'auto',
            'media_border'         => 'none',
            'overlay'              => 'default',
            'panel'                => 'blank',
            'overlay_background'   => 'hover',
            'overlay_image'        => false,
            'overlay_animation'    => 'fade',
            'content_animation'    => 'slide-bottom',
            'image_animation'      => 'scale',

            'title'                => true,
            'content'              => true,
            'title_size'           => 'panel',
            'link'                 => false,
            'link_style'           => 'button',
            'link_icon'            => 'share',
            'link_text'            => 'View',

            'lightbox'             => true,
            'lightbox_width'       => 'auto',
            'lightbox_height'      => 'auto',
            'lightbox_caption'     => 'content',
            'lightbox_link'        => false,
            'lightbox_style'       => 'button',
            'lightbox_icon'        => 'search',
            'lightbox_text'        => 'Details',

            'link_target'          => false,
            'class'                => ''
        )

    ),

    'events' => array(

        'init.site' => function($event, $app) {

        },

        'init.admin' => function($event, $app) {
            $app['angular']->addTemplate('gallery-avion.edit', 'plugins/widgets/gallery-avion/views/edit.php', true);
        }

    )

);
