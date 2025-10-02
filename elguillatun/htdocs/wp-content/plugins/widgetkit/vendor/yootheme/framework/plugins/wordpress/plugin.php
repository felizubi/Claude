<?php

$config = array(

    'name' => 'framework/wordpress',

    'main' => 'YOOtheme\\Framework\\Wordpress\\WordpressPlugin',

    'autoload' => array(

        'YOOtheme\\Framework\\Wordpress\\' => 'src'

    )

);

return defined('WPINC') ? $config : false;