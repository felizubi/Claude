<?php if (have_posts()) : ?>

    <?php echo $this->render('_posts'); ?>

    <?php if ($this['config']->get('no_pagination', true)) : ?>
        <?php echo $this->render("_pagination", array("type"=>"posts")); ?>
    <?php endif; ?>

<?php else : ?>

    <h1><?php _e('Not Found', 'warp'); ?></h1>
    <p><?php _e("Sorry, but you are looking for something that isn't here.", "warp"); ?></p>
    <?php get_search_form(); ?>

<?php endif; ?>