<?php

use YOOtheme\Widgetkit\Application;

class WP_Widget_Widgetkit extends WP_Widget
{
    public $app;

    public function __construct()
    {
        $this->app = Application::getInstance();

        parent::__construct('', 'Widgetkit', array('description' => $this->app['translator']->trans('Display your widgets'), 'settings' => array('title'  => '', 'widgetkit' => '{}')));
    }

    public function widget($args, $instance)
    {
        $output   = array($args['before_widget']);
        $settings = array_merge($this->widget_options['settings'], $instance);

        if ($settings['title']) {
            array_push($output, $args['before_title'], $settings['title'], $args['after_title']);
        }

        array_push($output, $this->app->renderWidget(json_decode($settings['widgetkit'], true)), $args['after_widget']);

        echo implode('', $output);
    }

    public function form($instance)
    {
        $settings = array_merge($this->widget_options['settings'], $instance);
        $content = json_decode($settings['widgetkit'], true);

        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php echo $this->app['translator']->trans('Title'); ?>:</label>
            <input type="text" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo esc_attr($settings['title']); ?>" class="widefat" id="<?php echo $this->get_field_id('title'); ?>">
        </p>
        <p class="widgetkit-widget">
            <button class="button"><?php echo $content ? $this->app['translator']->trans('Widget: %widget%', array('%widget%' => $content['name'])) : $this->app['translator']->trans('Select Widget'); ?></button>
            <input type="hidden" name="<?php echo $this->get_field_name('widgetkit'); ?>" value="<?php echo esc_attr($settings['widgetkit']); ?>">
        </p>
    <?php
    }
}
