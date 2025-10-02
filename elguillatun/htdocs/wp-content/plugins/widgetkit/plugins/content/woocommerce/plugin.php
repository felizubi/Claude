<?php

$config = array(

    'name' => 'content/woocommerce',

    'main' => 'YOOtheme\\Widgetkit\\Content\\Type',

    'config' => array(

        'name'  => 'woocommerce',
        'label' => 'WooCommerce',
        'icon'  => 'plugins/content/woocommerce/content.svg',
        'item'  => array('title', 'content', 'media', 'link'),
        'data'  => array(
            'number'   => 5,
            'content'  => 'intro',
            'category' => '',
            'order_by' => 'post_date',
            'price'    => 'regular',
            'author'   => 'author',
            'date'     => 'publish_up',
            'categories' => 'categories'
        )

    ),

    'items' => function($items, $content) {

        $order = explode('_asc', $content['order_by']);
        $args  = array(
            'numberposts' => $content['number'] ?: 5,
            'orderby'     => isset($order[0]) ? $order[0] : 'post_date',
            'order'       => isset($order[1]) ? 'ASC' : 'DESC',
            'post_status' => 'publish',
            'post_type'   => 'product'
        );

        if ($content['category'] > 0) {
            $args['tax_query'] = array(
                array(
                    'taxonomy'         => 'product_cat',
                    'field'            => 'id',
                    'terms'            => (int) $content['category'],
                    'include_children' => false
                )
            );
        }

        foreach (get_posts($args) as $post) {

            $product = new WC_Product($post->ID);
            $data    = array();

            $data['title']      = get_the_title($post->ID);
            $data['link']       = get_permalink($post->ID);
            $data['price']      = wc_price(get_post_meta($post->ID, "_{$content['price']}_price", true));
            $data['author']     = $content['author'] ? get_the_author_meta('display_name', $post->post_author) : '';
            $data['date']       = $content['date'] ? $post->post_date : '';

            $pieces = get_extended($post->post_content);

            if ($content['content'] == 'excerpt') {
                $data['content'] = apply_filters('the_content', $post->post_excerpt);
            } else if ($content['content'] == 'intro') {
                $data['content'] = apply_filters('the_content', $pieces['main']);
            } else {
                $data['content'] = apply_filters('the_content', $pieces['main'].$pieces['extended']);
            }

            if ($content['categories']) {

                $data['categories'] = array();

                $terms = apply_filters( 'woocommerce_get_related_product_cat_terms', wp_get_post_terms( $post->ID, 'product_cat' ), $post->ID );
                foreach ( $terms as $term ) {
                    $data['categories'][$term->name] = esc_url( get_term_link($term) );
                }

            }

            if ($thumbnail = get_post_thumbnail_id($post->ID)) {
                $image = wp_get_attachment_image_src($thumbnail, 'full');
                $data['media'] = $image[0];
            }
            else {
                $data['media'] = wc_placeholder_img_src();
            }

            // get all product images
            $args = array(
                'post_type' => 'attachment',
                'numberposts' => -1,
                'post_status' => null,
                'post_parent' => $post->ID
            );

            if ($attachments = get_posts($args)) {

                foreach ($attachments as $i => $attachment) {

                    if ($thumbnail && $thumbnail == $attachment->ID) continue;

                    $image = wp_get_attachment_image_src($attachment->ID, 'full');
                    $data["media{$i}"] = $image[0];
                }
            }

            $data['tags'] = array();

            if ($tags = get_the_terms($post->ID, 'product_tag')) {

                foreach($tags as $tag) {
                    $data['tags'][] = $tag->name;
                }
            }

            // map custom fields
            foreach ((array)$content['mapping'] as $map) {
                if (isset($map['name']) && isset($map['field'])) {
                    $value = get_post_meta($post->ID, $map['field']);
                    $data[$map['name']] = array_shift($value);
                }
            }

            $items->add($data);
        }

    },

    'events' => array(

        'init.admin' => function($event, $app) {
            $app['scripts']->add('widgetkit-woocommerce-controller', 'plugins/content/woocommerce/assets/controller.js');
            $app['angular']->addTemplate('woocommerce.edit', 'plugins/content/woocommerce/views/edit.php');
        }

    )

);

return defined('WPINC') && in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) ? $config : false;
