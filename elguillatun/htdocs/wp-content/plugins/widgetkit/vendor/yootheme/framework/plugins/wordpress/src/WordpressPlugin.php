<?php

namespace YOOtheme\Framework\Wordpress;

use YOOtheme\Framework\Application;
use YOOtheme\Framework\ApplicationAware;
use YOOtheme\Framework\Plugin\Plugin;
use YOOtheme\Framework\Routing\Request;
use YOOtheme\Framework\Routing\ResponseProvider;

class WordpressPlugin extends Plugin
{
    /**
     * {@inheritdoc}
     */
    public function main(Application $app)
    {
        $app['db'] = function () {
            return new Database($GLOBALS['wpdb']);
        };

        $app['url'] = function ($app) {
            return new UrlGenerator($app['request'], $app['locator']);
        };

        $app['request'] = function ($app) {

            $baseUrl   = rtrim(site_url(), '/');
            $basePath  = rtrim(strtr(ABSPATH, '\\', '/'), '/');
            $baseRoute = sprintf('%s/wp-admin/admin-ajax.php?action=%s', $basePath, $app['name']);
            $request   = version_compare(PHP_VERSION, '5.4.0', '<') && get_magic_quotes_gpc() ? array_map('stripslashes_deep', $_REQUEST) : $_REQUEST;

            return new Request($baseUrl, $basePath, $baseRoute, $request);
        };

        $app['response'] = function ($app) {
            return new ResponseProvider($app['url']);
        };

        $app['csrf'] = function () {
            return new CsrfProvider;
        };

        $app['users'] = function () {
            return new UserProvider;
        };

        $app['date'] = function () {

            $date = new DateHelper();
            $date->setFormats(array(
                'medium' => get_option('date_format')
            ));

            return $date;
        };

        $app['locale'] = function () {
            return get_locale();
        };

        $app['admin'] = function () {
            return is_admin();
        };

        $app['update'] = function () {
            return new Update();
        };

        $app['session'] = function () {
            return new Session();
        };

        $app['secret'] = function () {
            return NONCE_KEY;
        };

        $app->extend('filter', function ($filter) {
            return $filter->register('content', new ContentFilter());
        });

        $app->on('boot', array($this, 'boot'));
        $app->on('view', array($this, 'registerAssets'), -10);
    }

    /**
     * Callback for 'boot' event.
     */
    public function boot($event, $app)
    {
        if (!is_dir($app['path.cache']) && !mkdir($app['path.cache'], 0777, true)) {
            throw new \RuntimeException(sprintf('Unable to create cache folder in "%s"', $app['path.cache']));
        }

        add_action('wp_loaded', function () use ($app) {
            $app['plugins']->load();
            $app->trigger('init', array($app));
        });

        add_action($app['admin'] ? 'admin_enqueue_scripts' : 'wp_enqueue_scripts', function () use ($app) {
            $app->trigger('view', array($app));
        });

        $handle = function () use ($app) {
            if ($app->handle()) {
                exit;
            }
        };

        add_action('wp_ajax_nopriv_' . $app['name'], $handle);
        add_action('wp_ajax_' . $app['name'], $handle);
    }

    /**
     * Callback to register assets.
     */
    public function registerAssets()
    {
        wp_enqueue_script('jquery');

        $styles  = '';
        $scripts = '';

        foreach ($this['styles'] as $style) {
            if ($source = $style->getSource()) {
                wp_enqueue_style($style->getName(), $this['url']->to($source, array(), true));
            } elseif ($content = $style->getContent()) {
                $styles .= sprintf("<style>%s</style>\n", $content);
            }
        }

        foreach ($this['scripts'] as $script) {
            if ($source = $script->getSource()) {
                wp_enqueue_script($script->getName(), $this['url']->to($source, array(), true));
            } elseif ($content = $script->getContent()) {
                $scripts .= sprintf("<script>%s</script>\n", $content);
            } elseif ($template = $script->getOption('template')) {
                $scripts .= sprintf("<script id=\"%s\" type=\"text/template\">%s</script>\n", $script->getName(), $this['view']->render($template));
            }
        }

        if ($styles) {
            add_action($this['admin'] ? 'admin_print_styles' : 'wp_print_styles', function () use ($styles) {
                echo $styles;
            });
        }

        if ($scripts) {
            add_action($this['admin'] ? 'admin_print_scripts' : 'wp_print_scripts', function () use ($scripts) {
                echo $scripts;
            }, 30);
        }
    }
}
