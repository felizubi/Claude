<?php
/**
* @package   Avion
* @author    YOOtheme http://www.yootheme.com
* @copyright Copyright (C) YOOtheme GmbH
* @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
*/

// Panel
$panel = $settings['panel'] ? 'uk-panel uk-panel-space' : 'uk-panel';

// Media alignment
$media_align = 'tm-media-' . $settings['media_align'];

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

$min_height = $settings['min_height'] ? 'style="min-height: '.$settings['min_height'].'px;"' : '';

?>

<ul id="wk-<?php echo $settings['id']; ?>" class="uk-switcher uk-text-<?php echo $settings['text_align']; ?> uk-flex uk-flex-item-1" data-uk-check-display <?php echo $min_height; ?>>
    <?php foreach ($items as $item) : ?>

        <?php
            // Media
            $background_image = '';
            if ($settings['media'] && $item['media'] && $item->type('media') == 'image') {
                $background_image = 'style="background-image: url('.$item['media'].');"';
            }

            $media  = '';
            $attrs  = array('class' => '');
            $width  = $item['media.width'];
            $height = $item['media.height'];

            if ($item->type('media') == 'video') {
                $attrs['class'] = 'uk-responsive-width';
                $attrs['controls'] = true;
            }

            if ($item->type('media') == 'iframe') {
                $attrs['class'] = 'uk-responsive-width';
            }

            $attrs['width']  = ($width) ? $width : '';
            $attrs['height'] = ($height) ? $height : '';

            if ($settings['media'] && $item['media'] && $item->type('media') != 'image') {
                $media = $item->media('media', $attrs);
            }
        ?>

        <li class="uk-flex uk-flex-middle <?php echo ($background_image) ? $media_align : ''; ?>" <?php echo $background_image; ?>>
            <div class="<?php echo $panel; ?>">

                <?php if ($item['media'] && $settings['media']) : ?>
                <?php echo $media; ?>
                <?php endif; ?>

                <?php if ($item['title'] && $settings['title']) : ?>
                <h3 class="<?php echo $title_size; ?>"><?php echo $item['title']; ?></h3>
                <?php endif; ?>

                <?php if ($item['content'] && $settings['content']) : ?>
                <?php echo $item['content'] ?>
                <?php endif; ?>

                <?php if ($item['link'] && $settings['link']) : ?>
                <p><a<?php if($link_style) echo ' class="' . $link_style . '"'; ?> href="<?php echo $item->escape('link'); ?>"<?php echo $link_target; ?>><?php echo $app['translator']->trans($settings['link_text']); ?></a></p>
                <?php endif; ?>
            </div>
        </li>
    <?php endforeach ?>
</ul>
