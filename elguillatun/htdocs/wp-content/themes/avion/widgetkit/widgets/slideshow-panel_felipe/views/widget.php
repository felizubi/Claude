<?php
/**
* @package   Avanti
* @author    YOOtheme http://www.yootheme.com
* @copyright Copyright (C) YOOtheme GmbH
* @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
*/

// Id
$settings['id'] = substr(uniqid(), -3);

// JS Options
$options = array();
$options[] = ($settings['duration'] != '500') ? 'duration: ' . $settings['duration'] : '';
$options[] = ($settings['autoplay']) ? 'autoplay: true ' : '';
$options[] = ($settings['interval'] != '3000') ? 'autoplayInterval: ' . $settings['interval'] : '';
$options[] = ($settings['autoplay_pause']) ? '' : 'pauseOnHover: false';

$options = '{'.implode(',', array_filter($options)).'}';

// Panel
$panel = 'uk-panel';
switch ($settings['panel']) {
    case 'box' :
        $panel .= ' uk-panel-box';
        break;
    case 'primary' :
        $panel .= ' uk-panel-box uk-panel-box-primary';
        break;
    case 'secondary' :
        $panel .= ' uk-panel-box uk-panel-box-secondary';
        break;
}

// Media Width
if ($settings['media_align'] == 'top') {

    $media_width = 'uk-width-1-1';
    $content_width = 'uk-width-1-1';

} else {

    $media_width = 'uk-width-' . $settings['media_breakpoint'] . '-' . $settings['media_width'];

    switch ($settings['media_width']) {
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
        case '3-5':
            $content_width = '2-5';
            break;
        case '2-3':
            $content_width = '1-3';
            break;
    }

    $content_width = 'uk-width-' . $settings['media_breakpoint'] . '-' . $content_width;

    // Align Right
    $media_width .= ($settings['media_align'] == 'right') ? ' uk-float-right uk-flex-order-last-' . $settings['media_breakpoint'] : '';

}

// Content Max Width
$content_max_width = 'uk-width-xlarge-';

switch ($settings['content_max_width']) {
    case '50':
        $content_max_width .= '1-2';
        break;
    case '60':
        $content_max_width .= '3-5';
        break;
    case '66':
        $content_max_width .= '4-6';
        break;
    case '70':
        $content_max_width .= '7-10';
        break;
    case '75':
        $content_max_width .= '3-4';
        break;
    case '80':
        $content_max_width .= '8-10';
        break;
    case '90':
        $content_max_width .= '9-10';
        break;
    case '100':
        $content_max_width .= '1-1';
        break;
}

// Title Size
switch ($settings['title_size']) {
    case 'panel':
        $title_size = 'uk-panel-title';
        break;
    case 'large':
        $title_size = 'uk-heading-large uk-margin-top-remove';
        break;
    default:
        $title_size = 'uk-' . $settings['title_size'] . ' uk-margin-top-remove';
}

// Content Align
$content_align  = ($settings['content_align'] && $settings['media_align'] != 'top') ? 'uk-flex-middle' : '';

// Title Size
switch ($settings['title_size']) {
    case 'panel':
        $title_size = 'uk-panel-title';
        break;
    case 'large':
        $title_size = 'uk-heading-large uk-margin-top-remove';
        break;
    default:
        $title_size = 'uk-' . $settings['title_size'] . ' uk-margin-top-remove';
}

// Content Size
switch ($settings['content_size']) {
    case 'large':
        $content_size = 'uk-text-large';
        break;
    case 'h2':
    case 'h3':
    case 'h4':
        $content_size = 'uk-' . $settings['content_size'];
        break;
    default:
        $content_size = '';
}

// Link Style
switch ($settings['link_style']) {
    case 'button':
        $link_style = 'uk-button';
        break;
    case 'primary':
        $link_style = 'uk-button uk-button-primary';
        break;
    case 'button-large':
        $link_style = 'uk-button uk-button-large';
        break;
    case 'primary-large':
        $link_style = 'uk-button uk-button-large uk-button-primary';
        break;
    case 'button-link':
        $link_style = 'uk-button uk-button-link';
        break;
    default:
        $link_style = '';
}

// Link Target
$link_target = ($settings['link_target']) ? ' target="_blank"' : '';

// Badge Style
switch ($settings['badge_style']) {
    case 'badge':
        $badge_style = 'tm-slideshow-panel-badge uk-badge';
        break;
    case 'success':
        $badge_style = 'uk-badge uk-badge-success';
        break;
    case 'warning':
        $badge_style = 'uk-badge uk-badge-warning';
        break;
    case 'danger':
        $badge_style = 'uk-badge uk-badge-danger';
        break;
    case 'text-muted':
        $badge_style  = 'uk-text-muted';
        break;
    case 'text-primary':
        $badge_style  = 'uk-text-primary';
        break;
}

?>

<div id="wk-<?php echo $settings['id']; ?>" class="fz-slideshow-panel <?php echo $settings['class']; ?>">
    <div class="uk-panel <?php echo $panel; ?> uk-padding-remove uk-overflow-hidden">

        <?php if ($settings['media']) : ?>
        <div class="uk-grid uk-grid-collapse <?php echo $content_align; ?>" data-uk-grid-margin>
            <div class="<?php echo $media_width ?> uk-text-center">
                <?php echo $this->render('plugins/widgets/' . $widget->getConfig('name')  . '/views/_media.php', compact('items', 'settings', 'widget', 'badge_style')); ?>
            </div>
            <div class="<?php echo $content_width ?>">
        <?php endif; ?>

        <div class="uk-panel-body uk-text-<?php echo $settings['text_align']; ?>" data-uk-slideshow="<?php echo $options; ?>">
            <ul class="uk-slideshow">
                <?php foreach ($items as $item) : ?>

                <li<?php echo ($settings['media_align'] == 'right') ? ' class="uk-flex uk-flex-right"' : ''; ?>>

                    <div class="<?php echo $content_max_width; ?>">

                        <?php if ($item['title'] && $settings['title']) : ?>
                        <h3 class="<?php echo $title_size; ?>">

                            <?php if ($item['link']) : ?>
                                <a class="uk-link-reset" href="<?php echo $item->escape('link'); ?>"<?php echo $link_target; ?>><?php echo $item['title']; ?></a>
                            <?php else : ?>
                                <?php echo $item['title']; ?>
                            <?php endif; ?>

                        </h3>
                        <?php endif; ?>

                        <?php if ($item['content'] && $settings['content']) : ?>
                        <div class="<?php echo $content_size; ?> uk-margin-top"><?php echo $item['content']; ?></div>
                        <?php endif; ?>

                        <?php if ($item['link'] && $settings['link']) : ?>
                        <p class="uk-margin-bottom-remove"><a<?php if($link_style) echo ' class="' . $link_style . '"'; ?> href="<?php echo $item->escape('link'); ?>"<?php echo $link_target; ?>><?php echo $app['translator']->trans($settings['link_text']); ?></a></p>
                        <?php endif; ?>

                    </div>

                </li>

            <?php endforeach; ?>
            </ul>

            <?php if (!$settings['nav_overlay'] && ($settings['nav'] != 'none')) : ?>
            <div class="uk-margin-top">
                <?php echo $this->render('plugins/widgets/' . $widget->getConfig('name')  . '/views/_nav.php', compact('items', 'settings')); ?>
            </div>
            <?php endif ?>

        </div>

        <?php if ($settings['media']) : ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>

    (function($){

        var settings   = <?php echo json_encode($settings); ?>,
            container  = $('#wk-<?php echo $settings["id"]; ?>'),
            slideshows = container.find('[data-uk-slideshow]');

        container.on('beforeshow.uk.slideshow', function(e, next) {
            slideshows.not(next.closest('[data-uk-slideshow]')[0]).data('slideshow').show(next.index());
        });

        if (settings.autoplay && settings.autoplay_pause) {

            container.on({
                mouseenter: function() {
                    slideshows.each(function(){
                        $(this).data('slideshow').stop();
                    });
                },
                mouseleave: function() {
                    slideshows.each(function(){
                        $(this).data('slideshow').start();
                    });
                }
            });
        }

    })(jQuery);

</script>
