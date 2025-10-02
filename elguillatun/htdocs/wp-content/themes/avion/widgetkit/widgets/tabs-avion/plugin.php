<?php
/**
* @package   Avion
* @author    YOOtheme http://www.yootheme.com
* @copyright Copyright (C) YOOtheme GmbH
* @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
*/

return array(

    'name' => 'widget/tabs-avion',

    'main' => 'YOOtheme\\Widgetkit\\Widget\\Widget',

    'config' => array(

        'name'  => 'tabs-avion',
        'label' => 'Tabs Avion',
        'core'  => false,
        'icon'  => 'plugins/widgets/tabs-avion/widget.svg',
        'view'  => 'plugins/widgets/tabs-avion/views/widget.php',
        'item'  => array('title', 'content', 'media'),
        'settings' => array(
            'position'          => 'left',
            'width'             => '1-4',
            'panel'             => false,
            'min_height'        => '350',

            'media'             => true,
            'media_align'       => 'right-bottom',

            'title'             => true,
            'content'           => true,
            'title_size'        => 'panel',
            'text_align'        => 'left',
            'link'              => true,
            'link_style'        => 'button',
            'link_text'         => 'Read more',

            'link_target'       => false,
            'class'             => ''
        )

    ),

    'events' => array(

        'init.site' => function($event, $app) {
            $app['styles']->add('tabs-avion', 'plugins/widgets/tabs-avion/styles/tabs.css');
        },

        'init.admin' => function($event, $app) {
            $app['angular']->addTemplate('tabs-avion.edit', 'plugins/widgets/tabs-avion/views/edit.php', true);
        }

    )

);
