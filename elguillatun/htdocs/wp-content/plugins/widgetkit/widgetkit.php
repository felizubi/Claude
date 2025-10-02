<?php

/*
    Plugin Name: Widgetkit
    Plugin URI: http://www.yootheme.com/widgetkit
    Description: A widget toolkit by YOOtheme.
    Version: 2.9.25
    Author: YOOtheme
    Author URI: http://yootheme.com
    License: GNU General Public License v2 or later
*/

use YOOtheme\Widgetkit\Application;
use YOOtheme\Framework\Wordpress\Option;

$loader = require __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config.php';

$app = new Application($config);
$app['autoloader'] = $loader;
$app['templates'] = function () {
    
    $dirs = array();

    foreach(array_unique(array(
        get_template_directory(), 
        get_stylesheet_directory()
    )) as $dir) {
        
        if (file_exists($dir = "{$dir}/widgetkit")) {
            $dirs[] = $dir;
        }
    }

    return $dirs;
};
$app['option'] = function () {
    return new Option('widgetkit-option');
};

$app->boot();

$app->on('init', function ($event, $app) {

    // set the API Keys
    $app['config']->set('apikey', get_option('yootheme_apikey'));
    $app['config']->set('googlemapseapikey', get_option('yootheme_googlemapseapikey'));

    // system editor setting
    $app['config']->set('system_editor', get_option('yootheme_system_editor'));

    // init 1click Update
    $app['update']->register('widgetkit', 'plugin', 'http://yootheme.com/api/update/widgetkit_wp', array('key' => $app['config']->get('apikey')));

    // set theme support
    if (!$app['admin']) {
        $app['config']->set('theme.support', current_theme_supports('widgetkit') ?: (current_theme_supports('widgetkit-noconflict') ? 'noconflict' : ''));
    }

});

$app->on('view', function ($event, $app) {

    // add to admin styles
    if ($app['admin']) {
        $app['styles']->add('widgetkit-wordpress', 'assets/css/wordpress.css');
    }

});

// init on admin ajax
add_action('admin_init', function () use ($app) {

    $app['config']->set('settings-page', admin_url('options-general.php?page=widgetkit-config'));

    if (defined('DOING_AJAX') && $app['request']->get('action') == $app['name']) {
        $app->trigger('init.admin', array($app));
    }
});

// init on certain admin screens
add_action('current_screen', function ($screen) use ($app) {

    if (in_array($screen->base, array('post', 'customize', 'widgets', 'toplevel_page_widgetkit'))) {

        add_action('admin_enqueue_scripts', function () use ($screen) {

            if ($screen->base != 'toplevel_page_widgetkit') {
                return;
            }

            // create dummy editor to initialize tinyMCE on widgetkit admin page

            add_action('in_admin_footer', function() {
                echo '<div style="display:none">';

        	    wp_editor('', 'editor-dummy-widgetkit', array(
        	        'wpautop' => false,
        	        'media_buttons' => true,
        	        'textarea_name' => 'textarea-dummy-widgetkit',
        	        'textarea_rows' => 10,
        	        'editor_class' => 'horizontal',
        	        'teeny' => false,
        	        'dfw' => false,
        	        'tinymce' => true,
        	        'quicktags' => true
        	    ));

        	    echo '</div>';
            });

            wp_enqueue_media();
        });

        $app['scripts']->add('widgetkit-wordpress', 'assets/js/wordpress.js', array('widgetkit-admin'));
        $app->trigger('init.admin', array($app));

        add_action('in_widget_form', function ($widget, $return, $instance) use ($app) {

            if ($widget->id_base == 'text') {
                $id = "widget-{$widget->id}-text";
                echo '<a href="#" class="button add_media widgetkit-editor" title="' . $app['translator']->trans('Add Widget') . '" data-source="' . $id . '"><span></span> Widgetkit</a>';
            }
        }, 10, 3);

    }
});

// add to admin menu
add_action('admin_menu', function () use ($app) {

    add_action('load-toplevel_page_widgetkit', function () use ($app) {

        $response = $app->handle(null, false);

        add_action('toplevel_page_widgetkit', function () use ($response) {
            $response->send();
        });
    });


    add_action('load-settings_page_' . $app['name'] . '-config', function () use ($app) {

        if ($app['request']->get('action') === 'save' and wp_verify_nonce($app['request']->get('_wpnonce'))) {
            $config = $app['request']->get('config', array());

            // save the YOOtheme API key outside the config
            if (isset($config['apikey'])) {
                update_option('yootheme_apikey', trim($config['apikey']));
            }

            // save the Google API key outside the config
            if (isset($config['googlemapseapikey'])) {
                update_option('yootheme_googlemapseapikey', trim($config['googlemapseapikey']));
            }

            // save editor setting
            if (isset($config['system_editor'])) {
                update_option('yootheme_system_editor', $config['system_editor']);
            } else {
                update_option('yootheme_system_editor', 0);
            }

            $app['config']->set('apikey', get_option('yootheme_apikey'));
            $app['config']->set('googlemapseapikey', get_option('yootheme_googlemapseapikey'));
            $app['config']->set('system_editor', get_option('yootheme_system_editor'));
        }
    });


    add_menu_page('Widgetkit', 'Widgetkit', 'manage_widgetkit', $app['name'], function () {
    }, 'dashicons-admin-widgetkit', '50');

    add_options_page('Widgetkit Settings', 'Widgetkit', 'manage_options', $app['name'] . '-config', function () use ($app) {
        require(__DIR__ . '/widgetkit-config.php');
    });
});

// add settings link
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) use ($app) {
    array_unshift($links, '<a href="' . admin_url('options-general.php?page=widgetkit-config') . '">' . $app['translator']->trans('Settings') . '</a>');
    return $links;
});

// add media buttons
add_action('media_buttons_context', function ($context) use ($app) {
    return $context . '<a href="#" class="button add_media widgetkit-editor" title="' . $app['translator']->trans('Add Widget') . '"><span></span> Widgetkit</a>';
});

// add shortcode
add_shortcode('widgetkit', function ($attrs, $content, $code) use ($app) {
    return $app->renderWidget($attrs);
});

// add widget
add_action('widgets_init', function () {
    require_once(__DIR__ . '/widgetkit-widget.php');
    register_widget('WP_Widget_Widgetkit');
});

// apply shortcodes in text widgets
add_filter('widget_text', 'do_shortcode');

// enable svg upload
add_filter('upload_mimes', function ($mimes) {

    $mimes['svg|svgz'] = 'image/svg+xml';
    return $mimes;

});

add_filter('wp_check_filetype_and_ext', function ($data, $file, $filename, $mimes) {

    if (empty($data['type']) && substr($filename, -4) === '.svg') {
        $data['ext'] = 'svg';
        $data['type'] = 'image/svg+xml';
    }

    return $data;

}, 10, 4);

$roles = array('administrator', 'editor', 'author');

// add activation hook
register_activation_hook(__FILE__, function () use ($app, $roles) {

    $oldVersion = get_option('widgetkit.version');

    if ($oldVersion && version_compare($oldVersion, '2.2.0', '<')) {
        $update = require($app['path'] . '/updates/2.2.0.php');
        $update->run();
    }

    foreach ($roles as $name) {
        get_role($name)->add_cap('manage_widgetkit');
    }

    $app->install();

    update_option('widgetkit.version', '2.9.25');
});

// add deactivation hook
register_deactivation_hook(__FILE__, function () use ($app, $roles) {

    foreach ($roles as $name) {
        get_role($name)->remove_cap('manage_widgetkit');
    }

});
