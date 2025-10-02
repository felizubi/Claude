<div class="tm-header">

    <?php if ($this['widgets']->count('toolbar-l + toolbar-r')) : ?>
    <div class="tm-toolbar uk-clearfix uk-hidden-small">

        <?php if ($this['widgets']->count('toolbar-l')) : ?>
        <div class="uk-float-left"><?php echo $this['widgets']->render('toolbar-l'); ?></div>
        <?php endif; ?>

        <?php if ($this['widgets']->count('toolbar-r')) : ?>
        <div class="uk-float-right"><?php echo $this['widgets']->render('toolbar-r'); ?></div>
        <?php endif; ?>

    </div>
    <?php endif; ?>

    <?php if ($this['widgets']->count('menu + logo + search')) : ?>
    <nav class="tm-navbar uk-navbar">

        <?php if ($this['widgets']->count('logo')) : ?>
        <a class="tm-logo uk-float-left uk-hidden-small" href="<?php echo $this['config']->get('site_url'); ?>"><?php echo $this['widgets']->render('logo'); ?></a>
        <?php endif; ?>

        <?php if ($this['widgets']->count('menu')) : ?>
        <?php echo $this['widgets']->render('menu'); ?>
        <?php endif; ?>

        <?php if ($this['widgets']->count('search + menu-more')) : ?>
        <div class="uk-navbar-flip">
        <?php if ($this['widgets']->count('search')) : ?>
            <div class="uk-navbar-content uk-visible-large"><?php echo $this['widgets']->render('search'); ?></div>
        <?php endif; ?>
        <?php if ($this['widgets']->count('menu-more')) : ?>
            <div class="uk-flex uk-flex-middle uk-navbar-content tm-navbar-more uk-visible-large" data-uk-dropdown="{mode:'click'}">
                <a class="uk-link-reset"></a>
                <div class="uk-dropdown uk-dropdown-flip">
                <?php echo $this['widgets']->render('menu-more'); ?>
                </div>
            </div>
        <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($this['widgets']->count('offcanvas')) : ?>
        <a href="#offcanvas" class="uk-navbar-toggle uk-navbar-flip uk-padding-remove uk-hidden-large" data-uk-offcanvas></a>
        <?php endif; ?>

        <?php if ($this['widgets']->count('logo-small')) : ?>
        <div class="uk-navbar-content uk-padding-remove uk-visible-small"><a class="tm-logo-small uk-float-left" href="<?php echo $this['config']->get('site_url'); ?>"><?php echo $this['widgets']->render('logo-small'); ?></a></div>
        <?php endif; ?>

    </nav>
    <?php endif; ?>

</div>