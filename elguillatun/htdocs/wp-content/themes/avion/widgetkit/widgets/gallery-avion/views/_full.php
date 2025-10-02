<?php
/**
* @package   Avion
* @author    YOOtheme http://www.yootheme.com
* @copyright Copyright (C) YOOtheme GmbH
* @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
*/

// Buttons
$button_link = ($button_link) ? 'class="' . $button_link . '"' : '';
$button_lightbox = ($button_lightbox) ? 'class="' . $button_lightbox . '"' : '';

$buttons = array();
if ($item['link'] && $settings['link']) {
    $buttons['link'] = '<a ' . $button_link . ' href="' . $item->escape('link') . '"' . $link_target . '>' . $app['translator']->trans($settings['link_text']) . '</a>';
}
if ($settings['lightbox'] && $settings['lightbox_link']) {
    $buttons['lightbox'] = '<a ' . $button_lightbox . ' ' . $lightbox . ' data-uk-lightbox="{group:\'.wk-2' . $settings['id'] . '\'}" ' . $lightbox_caption . '>' . $app['translator']->trans($settings['lightbox_text']) . '</a>';
}

// Overlays
$content_animation = 'uk-animation-' . $settings['content_animation'];
$background = ($settings['overlay_background'] == 'hover') ? 'uk-overlay-fade' : 'uk-ignore';

?>

<div class="uk-panel<?php if ($settings['animation'] != 'none') echo ' uk-invisible'; ?>">

    <figure class="uk-overlay uk-overlay-hover <?php echo $border; ?>">

        <?php echo $thumbnail; ?>

        <?php if ($settings['overlay_background'] != 'none') : ?>
        <div class="uk-overlay-panel uk-overlay-background <?php echo $background; ?>"></div>
        <?php endif; ?>

        <div class="uk-overlay-panel uk-animation-hover uk-ignore">
            <div>

                <?php if (isset($buttons['lightbox'])) : ?>
                    <?php echo $buttons['lightbox']; ?>
                <?php endif; ?>
                <?php if ($item['title'] && $settings['title']) : ?>
                <h3 class="<?php echo $title_size; ?> uk-margin"><?php echo $item['title']; ?></h3>
                <?php endif; ?>

                <?php if ($item['content'] && $settings['content']) : ?>
                <div class="uk-margin-small <?php echo $content_animation; ?>"><?php echo $item['content']; ?></div>
                <?php endif; ?>

                <?php if ($buttons) : ?>
                <div class="uk-grid uk-grid-small uk-margin <?php echo $content_animation; ?>" data-uk-grid-margin>

                    <?php if (isset($buttons['link'])) : ?>
                    <div><?php echo $buttons['link']; ?></div>
                    <?php endif; ?>

                    <?php if (!$item['title'] . !$settings['title']) : ?>
                        <?php if (isset($buttons['lightbox'])) : ?>
                        <div><?php echo $buttons['lightbox']; ?></div>
                        <?php endif; ?>
                    <?php endif; ?>

                </div>
                <?php endif; ?>

            </div>
        </div>

        <?php if (!$buttons) : ?>
            <?php if ($settings['lightbox']) : ?>
                <a class="uk-position-cover" <?php echo $lightbox; ?> data-uk-lightbox="{group:'.wk-1<?php echo $settings['id']; ?>'}" <?php echo $lightbox_caption; ?>></a>
            <?php elseif ($item['link']) : ?>
                <a class="uk-position-cover" href="<?php echo $item->escape('link'); ?>"<?php echo $link_target; ?>></a>
            <?php endif; ?>
        <?php endif; ?>

    </figure>

</div>
