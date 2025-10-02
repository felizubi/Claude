<?php
/**
* @package   Avion
* @author    YOOtheme http://www.yootheme.com
* @copyright Copyright (C) YOOtheme GmbH
* @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
*/

// Id
$settings['id'] = substr(uniqid(), -3);

// Width
$nav_width = 'uk-width-medium-' . $settings['width'];

switch ($settings['width']) {
    case '1-5':
        $content_width = '4-5';
        break;
    case '1-4':
        $content_width = '3-4';
        break;
    case '3-10':
        $content_width = '7-10';
        break;
    case '1-3':
        $content_width = '2-3';
        break;
    case '2-5':
        $content_width = '3-5';
        break;
    case '1-2':
        $content_width = '1-2';
        break;
}

$content_width = 'uk-width-medium-' . $content_width;

?>

<?php if ($settings['position'] == 'top' || $settings['position'] == 'bottom') : ?>

<div id="<?php echo $settings['id'] ?>" class="tm-switcher-avion uk-flex uk-flex-column <?php echo $settings['class'] ?>">

    <?php if ($settings['position'] == 'top') : ?>
    <?php echo $this->render('plugins/widgets/' . $widget->getConfig('name') . '/views/_nav.php', compact('items', 'settings')); ?>
    <?php endif ?>

    <?php echo $this->render('plugins/widgets/' . $widget->getConfig('name') . '/views/_content.php', compact('items', 'settings')); ?>

    <?php if ($settings['position'] == 'bottom') : ?>
    <?php echo $this->render('plugins/widgets/' . $widget->getConfig('name') . '/views/_nav.php', compact('items', 'settings')); ?>
    <?php endif ?>

<?php else : ?>

<div id="<?php echo $settings['id'] ?>" class="tm-switcher-avion uk-grid uk-grid-match uk-grid-collapse <?php echo $settings['class']; ?>" data-uk-grid-margin>

    <div class="<?php echo $nav_width ?> uk-position-relative <?php echo ($settings['position'] == 'right') ? 'uk-float-right uk-flex-order-last' : ''; ?>">
        <?php echo $this->render('plugins/widgets/' . $widget->getConfig('name') . '/views/_nav.php', compact('items', 'settings')); ?>
    </div>

    <div class="<?php echo $content_width ?>">
        <?php echo $this->render('plugins/widgets/' . $widget->getConfig('name') . '/views/_content.php', compact('items', 'settings', 'min_height')); ?>
    </div>

<?php endif ?>

</div>

<script>
jQuery(function($) {
    var switcher = $('#<?php echo $settings['id'] ?>'),
        container = switcher.parent();

    if (container.is('.uk-panel')) {
        $.UIkit.$win.on('load resize', UIkit.Utils.debounce((function() {

            var fn = function() {
                UIkit.Utils.matchHeights([switcher[0], container[0]]);
                return fn;
            }

            return fn();

        })(), 50));
    }
});
</script>
