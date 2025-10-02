<?php
/**
* @package   Avion
* @author    YOOtheme http://www.yootheme.com
* @copyright Copyright (C) YOOtheme GmbH
* @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
*/

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

?>

<div class="uk-panel<?php if ($settings['animation'] != 'none') echo ' uk-invisible'; ?>">

    <figure class="uk-overlay uk-overlay-hover <?php echo $border; ?>">

        <?php echo $thumbnail; ?>

        <div class="uk-overlay-panel uk-overlay-bottom uk-overlay-background uk-overlay-<?php echo $settings['overlay_animation']; ?>">

            <?php if ($buttons) : ?>
            <div class="uk-flex uk-flex-middle uk-flex-wrap uk-clearfix uk-margin" data-uk-margin>

                <?php if (($item['title'] && $settings['title']) || ($item['content'] && $settings['content'])) : ?>
                <div class="uk-flex-item-auto uk-float-left">

                    <?php if ($item['title'] && $settings['title']) : ?>
                    <h3 class="<?php echo $title_size; ?> uk-margin-bottom-remove"><?php echo $item['title']; ?></h3>
                    <?php endif; ?>

                    <?php if ($item['content'] && $settings['content']) : ?>
                    <div><?php echo $item['content']; ?></div>
                    <?php endif; ?>

                </div>
                <?php endif; ?>

                <div class="uk-grid uk-grid-small uk-float-right" data-uk-grid-margin>

                    <?php if (isset($buttons['link'])) : ?>
                    <div><?php echo $buttons['link']; ?></div>
                    <?php endif; ?>

                    <?php if (isset($buttons['lightbox'])) : ?>
                    <div><?php echo $buttons['lightbox']; ?></div>
                    <?php endif; ?>

                </div>

            </div>
            <?php else : ?>

                <?php if ($item['title'] && $settings['title']) : ?>
                <h3 class="<?php echo $title_size; ?> uk-margin-bottom-remove"><?php echo $item['title']; ?></h3>
                <?php endif; ?>

                <?php if ($item['content'] && $settings['content']) : ?>
                <div><?php echo $item['content']; ?></div>
                <?php endif; ?>

            <?php endif; ?>

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