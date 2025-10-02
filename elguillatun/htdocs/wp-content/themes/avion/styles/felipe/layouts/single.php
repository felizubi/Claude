 <?php if (have_posts()) : ?>
    <?php while (have_posts()) : the_post(); ?>

    <article class="uk-article tm-article" data-permalink="<?php the_permalink(); ?>">

        <div class="tm-article-content uk-position-relative fz-category"><?php echo get_the_category_list(', '); ?></div>

        <div class="tm-article-content uk-position-relative <?php echo get_the_date() ? ' tm-article-date-true' : ''; ?>">

            <div class="tm-article-date uk-text-center">
                <?php printf('<span class="tm-article-date-day">'.get_the_date('d').'</span>'.'<span class="tm-article-date-month">'.get_the_date('M').'</span>'.'<span class="tm-article-date-year">'.get_the_date('Y').'</span>'); ?>
            </div>

            <h1 class="uk-article-title"><?php the_title(); ?></h1>

            <?php if( get_field('subtitle') ): ?>
                <p class="fz-subtitle"><?php the_field('subtitle'); ?></p>
            <?php endif; ?>

            <p class="uk-article-meta">
                <?php printf(__('Written by %s. Posted in %s', 'warp'), '<a href="'.get_author_posts_url(get_the_author_meta('ID')).'" title="'.get_the_author().'">'.get_the_author().'</a>'); ?>
            </p>


            <?php if ( function_exists( 'wpssossb_get_sharing_buttons' ) )
                echo wpssossb_get_sharing_buttons( array( 'facebook', 'twitter' ) ); ?>


            <?php

            if(function_exists('social_warfare')):
                social_warfare();
            endif;

            ?>

            <div class="tm-article-featured-image">
                <figure><?php the_post_thumbnail(array($width, $height), array('class' => '')); ?>
                    <?php if ( $caption = get_post( get_post_thumbnail_id() )->post_excerpt ) : ?>
                        <figcaption><?php echo $caption; ?></figcaption>
                    <?php endif; ?>
                </figure>
            </div>

            <?php the_content(''); ?>

            <?php wp_link_pages(); ?>

            <div class="fz-tags"><?php the_tags('<p>'.__('Tags: ', 'warp'), ', ', '</p>'); ?></div>

            <?php edit_post_link(__('Edit this post.', 'warp'), '<p><i class="uk-icon-pencil"></i> ','</p>'); ?>

            <?php if ($this['config']->get('article_style')=='tm-article-blog') : ?>
        </div>
        <?php endif; ?>

        <div class="clearfix"></div>

        <?php if (get_the_author_meta('description')) : ?>
        <div class="uk-panel">
            <div class="fz-autor">

                <div class="uk-align-medium-left">
                    <?php echo get_wp_user_avatar(get_the_author_meta('user_email'), 128); ?>
                </div>

                <h3 class="uk-h3 uk-margin-top-remove"><?php the_author(); ?></h3>

                <div class="uk-margin fz-autor-description"><?php the_author_meta('description'); ?></div>

            </div>
        </div>
        <?php endif; ?>

        <?php comments_template(); ?>

    </article>

    <?php endwhile; ?>
 <?php endif; ?>
