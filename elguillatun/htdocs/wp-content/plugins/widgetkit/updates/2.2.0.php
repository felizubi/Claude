<?php

namespace YOOtheme\Widgetkit;

use YOOtheme\Widgetkit\Helper\Shortcode;

class Update220 implements Update
{
    public function run()
    {
        global $wpdb;

        $shortcode = new Shortcode();
        $wkcontent = new \ArrayObject(array());
        $done      = new \ArrayObject(array());

        foreach ((array) $wpdb->get_results('SELECT * FROM '.$wpdb->get_blog_prefix().'widgetkit', ARRAY_A) as $content) {
            $wkcontent[$content['id']] = $content;
            $done['c-'.$id] = 0;
        }

        $updateContentItem = function($id, $settings) use($wpdb, $wkcontent, $done) {

            $id      = intval($id);
            $content = isset($wkcontent[$id]) ? $wkcontent[$id] : null;

            if ($content) {

                if (!$done['c-'.$id]) {

                    $data            = json_decode($content['data'], true);
                    $data['_widget'] = $settings;
                    $content['data'] = json_encode($data);

                    $wkcontent[$id]  = $content;

                    $wpdb->update($wpdb->get_blog_prefix().'widgetkit', array('data' => $content['data']), array('id' => $id));

                    $done['c-'.$id]++;

                } else {

                    $clone           = array_merge(array(), $content);
                    $data            = json_decode($clone['data'], true);
                    $data['_widget'] = $settings;

                    $clone['name']   = $clone['name'].' '.(++$done['c-'.$id]);
                    $clone['data']   = json_encode($data);

                    unset($clone['id']);

                    $wpdb->insert($wpdb->get_blog_prefix().'widgetkit', $clone);

                    $clone['id']                 = $wpdb->insert_id;
                    $wkcontent[$wpdb->insert_id] = $clone;

                    return $wpdb->insert_id;
                }
            }

            return $id;
        };


        /**
         *  Update shortcodes in all posts
         */

        foreach ((array) $wpdb->get_results("SELECT * FROM {$wpdb->posts}") as $post) {

            if ($post->post_type == 'revision') continue;

            $modified = false;

            foreach(array('post_excerpt', 'post_content') as $field) {

                $content = $post->{$field};

                if ($content && preg_match_all('/\[widgetkit (.+?)\]/m', $content, $matches)) {

                    foreach($matches[0] as $idx => $match) {

                        $attr = $shortcode->attrs($matches[1][$idx]);

                        if (isset($attr['id'], $attr['widget'])) {

                            $modified = true;

                            $id       = $attr['id'];
                            $widget   = $attr['widget'];

                            // remove widget type + id
                            unset($attr['id']);
                            unset($attr['widget']);

                            $settings = array(
                                'name' => $widget,
                                'data' => $attr
                            );

                            $id      = $updateContentItem($id, $settings);
                            $name    = $wkcontent[$id]['name'];
                            $content = str_replace($match, '[widgetkit id="'.$id.'" name="'.$name.'"]', $content);
                        }
                    }

                    $post->{$field} = $content;
                }
            }

            if ($modified) {
                wp_update_post($post);
            }
        }


        /**
         *  Update all widgets using shortcodes
         */

        foreach ((array) $wpdb->get_results("SELECT * FROM {$wpdb->options}") as $option) {

            $modified = false;
            $content  = $option->option_value;

            if ($content && strpos($content, 'a:')===0) {

                $content = json_encode(unserialize($content));

                if (preg_match_all('/\[widgetkit (.+?)\]/m', $content, $matches)) {


                    foreach($matches[0] as $idx => $match) {

                        $attr = $shortcode->attrs(stripcslashes($matches[1][$idx]));

                        if (isset($attr['id'], $attr['widget'])) {

                            $modified = true;

                            $id       = $attr['id'];
                            $widget   = $attr['widget'];

                            // remove widget type + id
                            unset($attr['id']);
                            unset($attr['widget']);

                            $settings = array(
                                'name' => $widget,
                                'data' => $attr
                            );

                            $id      = $updateContentItem($id, $settings);
                            $name    = $wkcontent[$id]['name'];
                            $content = str_replace($match, '[widgetkit id=\"'.$id.'\" name=\"'.$name.'\"]', $content);
                        }
                    }
                }
            }

            if ($modified) {
                update_option($option->option_name, json_decode($content, true));
            }
        }

        /**
         * Update widgetkit widgets
         */

        if ($widgets = get_option('widget_widgetkit', false)) {

            foreach($widgets as $key => $widget) {

                if (isset($widget['widgetkit'])) {

                    if ($attr = json_decode($widget['widgetkit'], true)) {

                        if (isset($attr['id'], $attr['widget'])) {

                            $id       = $attr['id'];
                            $widget   = $attr['widget'];

                            // remove widget type + id
                            unset($attr['id']);
                            unset($attr['widget']);

                            $settings = array(
                                'name' => $widget,
                                'data' => $attr
                            );

                            $id   = $updateContentItem($id, $settings);
                            $name = $wkcontent[$id]['name'];

                            $widgets[$key]['widgetkit'] = '{"id":"'.$id.'", "name":"'.$name.'"}';
                        }
                    }
                }
            }

            update_option('widget_widgetkit', $widgets);
        }
    }
}

return new Update220();
