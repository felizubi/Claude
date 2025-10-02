 <?php if (have_posts()) : ?>
    <?php while (have_posts()) : the_post(); ?>

    <article class="uk-article tm-article" data-permalink="<?php the_permalink(); ?>">

    <?php if ($this['config']->get('article_style')=='tm-article-blog') : ?>

        <?php if (has_post_thumbnail()) : ?>

            <?php
            $width = get_option('thumbnail_size_w'); //get the width of the thumbnail setting
            $height = get_option('thumbnail_size_h'); //get the height of the thumbnail setting
            ?>

            <div class="tm-article-featured-image"><?php the_post_thumbnail(array($width, $height), array('class' => '')); ?></div>

        <?php endif; ?>

        <div class="tm-article-content uk-position-relative <?php echo get_the_date() ? ' tm-article-date-true' : ''; ?>">

            <div class="tm-article-date uk-text-center">
            <?php printf('<span class="tm-article-date-day">'.get_the_date('d').'</span>'.'<span class="tm-article-date-month">'.get_the_date('M').'</span>'); ?>
            </div>

    <?php else : ?>

        <?php if (has_post_thumbnail()) : ?>
            <?php
            $width = get_option('thumbnail_size_w'); //get the width of the thumbnail setting
            $height = get_option('thumbnail_size_h'); //get the height of the thumbnail setting
            ?>
            <?php the_post_thumbnail(array($width, $height), array('class' => '')); ?>
        <?php endif; ?>

    <?php endif; ?>

        <h1 class="uk-article-title"><?php the_title(); ?></h1>

        <p class="uk-article-meta">
            <?php if ($this['config']->get('article_style')!=='tm-article-blog') : ?>
            <?php
                $date = '<time datetime="'.get_the_date('Y-m-d').'">'.get_the_date().'</time>';
                printf(__('Written by %s on %s. Posted in %s', 'warp'), '<a href="'.get_author_posts_url(get_the_author_meta('ID')).'" title="'.get_the_author().'">'.get_the_author().'</a>', $date, get_the_category_list(', '));
            ?>
            <?php else : ?>
            <?php
                printf(__('Written by %s. Posted in %s', 'warp'), '<a href="'.get_author_posts_url(get_the_author_meta('ID')).'" title="'.get_the_author().'">'.get_the_author().'</a>', get_the_category_list(', '));
            ?>
            <?php endif; ?>
        </p>

        <?php the_content(''); ?>

        <?php wp_link_pages(); ?>

        <?php the_tags('<p>'.__('Tags: ', 'warp'), ', ', '</p>'); ?>

        <?php edit_post_link(__('Edit this post.', 'warp'), '<p><i class="uk-icon-pencil"></i> ','</p>'); ?>

        <?php if ($this['config']->get('article_style')=='tm-article-blog') : ?>
        </div>
        <?php endif; ?>

        <?php if (pings_open()) : ?>
        <p><?php printf(__('<a href="%s">Trackback</a> from your site.', 'warp'), get_trackback_url()); ?></p>
        <?php endif; ?>

        <?php if (get_the_author_meta('description')) : ?>
        <div class="uk-panel">

            <div class="uk-align-medium-left">

                <?php echo get_avatar(get_the_author_meta('user_email')); ?>

            </div>

            <h2 class="uk-h3 uk-margin-top-remove"><?php the_author(); ?></h2>

            <div class="uk-margin"><?php the_author_meta('description'); ?></div>

        </div>
        <?php endif; ?>

        <?php comments_template(); ?>

        <?php
            $prev = get_previous_post();
            $next = get_next_post();
        ?>

        <?php if ($this['config']->get('post_nav', 0) && ($prev || $next)) : ?>
        <ul class="uk-pagination">
            <?php if ($next) : ?>
            <li class="uk-pagination-next">
                <a href="<?php echo get_permalink($next->ID) ?>" title="<?php echo __('Next', 'warp'); ?>">
                    <?php echo __('Next', 'warp'); ?>
                    <i class="uk-icon-arrow-right"></i>
                </a>
            </li>
            <?php endif; ?>
            <?php if ($prev) : ?>
            <li class="uk-pagination-previous">
                <a href="<?php echo get_permalink($prev->ID) ?>" title="<?php echo __('Prev', 'warp'); ?>">
                    <i class="uk-icon-arrow-left"></i>
                    <?php echo __('Prev', 'warp'); ?>
                </a>
            </li>
            <?php endif; ?>
        </ul>
        <?php endif; ?>

    </article>

    <?php endwhile; ?>
 <?php endif; ?>
