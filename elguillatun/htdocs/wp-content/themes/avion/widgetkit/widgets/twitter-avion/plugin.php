<?php
/**
* @package   Avion
* @author    YOOtheme http://www.yootheme.com
* @copyright Copyright (C) YOOtheme GmbH
* @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
*/

return array(

    'name' => 'widget/twitter-avion',

    'main' => 'YOOtheme\\Widgetkit\\Widget\\Widget',

    'config' => array(

        'name'  => 'twitter-avion',
        'label' => 'Twitter',
        'core'  => false,
        'icon'  => 'plugins/widgets/twitter-avion/widget.svg',
        'view'  => 'plugins/widgets/twitter-avion/views/widget.php',
        'item'  => array('title', 'content', 'media'),
        'settings' => array(

            'media'             => true,
            'media_position'    => 'top',
            'media_width'       => '1-2',
            'image_width'       => 'auto',
            'image_height'      => 'auto',

            'content'           => true,
            'text_size'         => 'normal',
            'text_style'        => 'none',

            'title'             => true,
            'title_link'        => true,
            'meta'              => true,
            'meta_position'     => 'bottom',

            'class'             => '',
            'space'             => false,
            'line'              => false,
            'striped'           => false,
        )

    ),

    'events' => array(

        'init.site' => function($event, $app) {

        },

        'init.admin' => function($event, $app) {
            $app['angular']->addTemplate('twitter-avion.edit', 'plugins/widgets/twitter-avion/views/edit.php', true);
        }

    )

);
