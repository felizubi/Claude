<?php
/**
* @package   Avion
* @author    YOOtheme http://www.yootheme.com
* @copyright Copyright (C) YOOtheme GmbH
* @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
*/

return array(

    'name' => 'widget/slideset-avion',

    'main' => 'YOOtheme\\Widgetkit\\Widget\\Widget',

    'config' => array(

        'name'      => 'slideset-avion',
        'label'     => 'Slideset Avion',
        'core'      => false,
        'icon'      => 'plugins/widgets/slideset-avion/widget.svg',
        'view'      => 'plugins/widgets/slideset-avion/views/widget.php',
        'item'      => array('title', 'content', 'media'),
        'fields'    => array(
            array(
                'name'  => 'date',
                'type'  => 'text',
                'label' => 'Date'
            )
        ),
        'settings'  => array(
            'nav'                => true,
            'slidenav'           => 'aside',
            'slidenav_align'     => 'center',
            'slidenav_contrast'  => false,
            'filter'             => 'none',
            'tag-list'           => array(),
            'filter_position'    => 'top',
            'filter_align'       => 'left',
            'filter_all'         => false,
            'animation'          => 'fade',
            'duration'           => '200',
            'delay'              => '0',
            'autoplay'           => false,
            'interval'           => '3000',
            'autoplay_pause'     => true,
            'gutter'             => 'collapse',
            'grid_divider'       => false,
            'columns'            => '1',
            'columns_small'      => 0,
            'columns_medium'     => 0,
            'columns_large'      => 0,
            'columns_xlarge'     => 0,
            'panel'              => 'box',
            'panel_link'         => false,

            'media'              => true,
            'image_width'        => 'auto',
            'image_height'       => 'auto',
            'media_align'        => 'teaser',
            'media_border'       => 'none',
            'media_overlay'      => 'icon',
            'overlay_animation'  => 'fade',
            'media_animation'    => 'scale',

            'title'              => true,
            'content'            => true,
            'social_buttons'     => true,
            'title_size'         => 'panel',
            'text_align'         => 'center',
            'link'               => true,
            'link_style'         => 'button',
            'link_text'          => 'Read more',
            'badge'              => true,
            'badge_style'        => 'badge',
            'badge_position'     => 'panel',

            'link_target'        => false,
            'class'              => ''
        )

    ),

    'events' => array(

        'init.site' => function($event, $app) {

        },

        'init.admin' => function($event, $app) {
            $app['angular']->addTemplate('slideset-avion.edit', 'plugins/widgets/slideset-avion/views/edit.php', true);
        }

    )

);
