<?php
/**
* @package   Avion
* @author    YOOtheme http://www.yootheme.com
* @copyright Copyright (C) YOOtheme GmbH
* @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
*/

// Id
$settings['id'] = substr(uniqid(), -3);

// Grid
$grid  = 'tm-gallery-avion uk-grid-width-1-'.$settings['columns'] .'';
$grid .= $settings['columns_small'] ? ' uk-grid-width-small-1-'.$settings['columns_small'] : '';
$grid .= $settings['columns_medium'] ? ' uk-grid-width-medium-1-'.$settings['columns_medium'] : '';
$grid .= $settings['columns_large'] ? ' uk-grid-width-large-1-'.$settings['columns_large'] : '';
$grid .= $settings['columns_xlarge'] ? ' uk-grid-width-xlarge-1-'.$settings['columns_xlarge'] : '';

if ($settings['grid'] == 'dynamic') {

    // Filter Tags
    $tags = array();

    if (isset($settings['tag-list']) && is_array($settings['tag-list'])) {
        $tags = $settings['tag-list'];
    }

    if(!count($tags)){
        foreach ($items as $i => $item) {
            if ($item['tags']) {
                $tags = array_merge($tags, $item['tags']);
            }
        }
        $tags = array_unique($tags);

        natsort($tags);
        $tags = array_values($tags);
    }

    // Filter Nav
    $tabs_center = '';
    if ($settings['filter'] == 'tabs') {

        $filter  = 'uk-tab';
        $filter .= ($settings['filter_align'] == 'right') ? ' uk-tab-flip' : '';
        $filter .= ($settings['filter_align'] != 'center') ? ' uk-margin' : '';
        $tabs_center  = ($settings['filter_align'] == 'center') ? 'uk-tab-center uk-margin' : '';

    } elseif ($settings['filter'] != 'none') {

        switch ($settings['filter']) {
            case 'text':
                $filter = 'uk-subnav';
                break;
            case 'lines':
                $filter = 'uk-subnav uk-subnav-line';
                break;
            case 'nav':
                $filter = 'uk-subnav uk-subnav-pill';
                break;
        }

        $filter .= ' uk-flex-' . $settings['filter_align'];
    }

    // JS Options
    $options   = array();
    $options[] = ($settings['gutter_dynamic']) ? 'gutter: \'' . $settings['gutter_v_dynamic'] . ' ' . $settings['gutter_dynamic'] . '\'' : '';
    $options[] = ($settings['filter'] != 'none') ? 'controls: \'#wk-' . $settings['id'] . '\'' : '';
    $options[] = (count($tags) && $settings['filter'] != 'none' && !$settings['filter_all']) ? 'filter: \'' . $tags[0] . '\'': '';
    $options   = implode(',', array_filter($options));

    $grid_js   = $options ? 'data-uk-grid="{' . $options . '}"' : 'data-uk-grid';

} else {
    $grid .= ' uk-grid uk-grid-match';
    $grid .= ($settings['gutter'] == 'collapse') ? ' uk-grid-collapse' : '' ;
    $grid .= ($settings['gutter'] == 'small') ? ' uk-grid-small' : '' ;
    $grid .= ($settings['gutter'] == 'medium') ? ' uk-grid-medium' : '' ;
    $grid_js = 'data-uk-grid-match="{target:\'> div > .uk-panel\', row:true}" data-uk-grid-margin';
}

// Title Size
switch ($settings['title_size']) {
    case 'panel':
        $title_size = 'uk-panel-title';
        break;
    case 'large':
        $title_size = 'uk-heading-large';
        break;
    default:
        $title_size = 'uk-' . $settings['title_size'];
}

// Button: Link
switch ($settings['link_style']) {
    case 'icon-small':
        $button_link = 'uk-icon-small';
        break;
    case 'icon-medium':
        $button_link = 'uk-icon-medium';
        break;
    case 'icon-large':
        $button_link = 'uk-icon-large';
        break;
    case 'icon-button':
        $button_link = 'uk-icon-button';
        break;
    case 'button':
        $button_link = 'uk-button';
        break;
    case 'primary':
        $button_link = 'uk-button uk-button-primary';
        break;
    case 'button-large':
        $button_link = 'uk-button uk-button-large';
        break;
    case 'primary-large':
        $button_link = 'uk-button uk-button-large uk-button-primary';
        break;
    default:
        $button_link = '';
}

switch ($settings['link_style']) {
    case 'icon':
    case 'icon-small':
    case 'icon-medium':
    case 'icon-large':
    case 'icon-button':
        $button_link .= ' uk-icon-' . $settings['link_icon'];
        $settings['link_text'] = '';
        break;
}

// Button: Lightbox
switch ($settings['lightbox_style']) {
    case 'arrow':
        $button_lightbox = 'tm-icon-arrow uk-link-reset';
        break;
    case 'icon':
        $button_lightbox = 'uk-icon-mini';
        break;
    case 'icon-small':
        $button_lightbox = 'uk-icon-small';
        break;
    case 'icon-medium':
        $button_lightbox = 'uk-icon-medium';
        break;
    case 'icon-large':
        $button_lightbox = 'uk-icon-large';
        break;
    case 'icon-button':
        $button_lightbox = 'uk-icon-button';
        break;
    case 'button':
        $button_lightbox = 'uk-button';
        break;
    case 'primary':
        $button_lightbox = 'uk-button uk-button-primary';
        break;
    case 'button-large':
        $button_lightbox = 'uk-button uk-button-large';
        break;
    case 'primary-large':
        $button_lightbox = 'uk-button uk-button-large uk-button-primary';
        break;
    default:
        $button_lightbox = '';
}

switch ($settings['lightbox_style']) {
    case 'arrow':
        $button_lightbox .= '';
        $settings['lightbox_text'] = '';
        break;
    case 'icon':
    case 'icon-small':
    case 'icon-medium':
    case 'icon-large':
    case 'icon-button':
        $button_lightbox .= ' uk-icon-' . $settings['lightbox_icon'];
        $settings['lightbox_text'] = '';
        break;
}

// Media Border
$border = ($settings['media_border'] != 'none') ? 'uk-border-' . $settings['media_border'] : '';

// Animation
$animation = ($settings['animation'] != 'none') ? ' data-uk-scrollspy="{cls:\'uk-animation-' . $settings['animation'] . ' uk-invisible\', target:\'> div > .uk-panel\', delay:300}"' : '';

// Force overlay style
if (!in_array($settings['overlay'], array('full', 'bottom'))) {
    $settings['overlay'] = 'full';
}

?>

<?php if (isset($tags) && $tags && $settings['filter'] != 'none') : ?>

    <?php if ($tabs_center) : ?>
    <div class="<?php echo $tabs_center; ?>">
    <?php endif ?>

    <ul id="wk-<?php echo $settings['id']; ?>" class="<?php echo $filter; ?>"<?php if ($settings['filter'] == 'tabs') echo ' data-uk-tab'?>>

        <?php if ($settings['filter_all']) : ?>
        <li class="uk-active" data-uk-filter=""><a href="#"><?php echo $app['translator']->trans('All'); ?></a></li>
        <?php endif ?>

        <?php foreach ($tags as $i => $tag) : ?>
        <li data-uk-filter="<?php echo $tag; ?>"><a href="#"><?php echo ucwords($tag); ?></a></li>
        <?php endforeach; ?>

    </ul>

    <?php if ($tabs_center) : ?>
    </div>
    <?php endif ?>

<?php endif; ?>

<div id="wk-grid<?php echo $settings['id']; ?>" class="<?php echo $grid; ?> <?php echo $settings['class']; ?>" <?php echo $grid_js; ?> <?php echo $animation; ?>>

<?php foreach ($items as $i => $item) : ?>
    <?php if ($item['media']) :

        // Link Target
        $link_target = ($settings['link_target']) ? ' target="_blank"' : '';

        // Second Image as Thumbnail Overlay
        $thumbnail2 = '';
        if ($settings['overlay'] == 'default' && $settings['overlay_image']) {
            foreach ($item as $field) {
                if ($field != 'media' && $item->type($field) == 'image') {
                    $thumbnail2 = $field;
                    break;
                }
            }
        }

        // Thumbnails
        $thumbnail = '';
        if (($item->type('media') == 'image' || $item['media.poster'])) {

            $attrs           = array('class' => '');
            $width           = ($settings['image_width'] != 'auto') ? $settings['image_width'] : '';
            $height          = ($settings['image_height'] != 'auto') ? $settings['image_height'] : '';

            $attrs['alt']    = strip_tags($item['title']);
            $attrs['width']  = $width;
            $attrs['height'] = $height;

            $attrs['class'] .= ($settings['image_animation'] != 'none' && !$thumbnail2) ? 'uk-overlay-' . $settings['image_animation'] : '';

            if ($settings['image_width'] != 'auto' || $settings['image_height'] != 'auto') {
                $thumbnail = $item->thumbnail($item->type('media') == 'image' ? 'media' : 'media.poster', $width, $height, $attrs);
            } else {
                if(($item->type('media') == 'image') && $settings['gutter_dynamic']){

                    if ($img  = $app['image']->create($item->get('media'))) {
                        $size = getimagesize($img->getPathName());
                        $width  = $size[0];
                        $height = $size[1];
                        $attrs['width'] = $width;
                        $attrs['height'] = $height;
                    }
                }
                $thumbnail = $item->media($item->type('media') == 'image' ? 'media' : 'media.poster', $attrs);
            }
        }

        // Lightbox
        $lightbox = '';
        if ($settings['lightbox']) {
            if ($item->type('media') == 'image') {
                if ($settings['lightbox_width'] != 'auto' || $settings['lightbox_height'] != 'auto') {

                    $width  = ($settings['lightbox_width'] != 'auto') ? $settings['lightbox_width'] : '';
                    $height = ($settings['lightbox_height'] != 'auto') ? $settings['lightbox_height'] : '';

                    $lightbox = 'href="' . htmlspecialchars($item->thumbnail('media', $width, $height, $attrs, true)) . '" data-lightbox-type="image"';
                } else {
                    $lightbox = 'href="' . $item['media'] . '" data-lightbox-type="image"';
                }
            } else {
                $lightbox = 'href="' . $item['media'] . '"';
            }
        }

        // Second Image as Overlay
        if ($thumbnail2) {

            $attrs['class'] .= ' uk-overlay-panel uk-overlay-image';
            $attrs['class'] .= ($settings['image_animation'] != 'none') ? ' uk-overlay-' . $settings['image_animation'] : '';

            $thumbnail2 = $item->thumbnail($thumbnail2, $width, $height, $attrs);
        }

        // Lightbox Caption
        $lightbox_caption = '';
        if ($settings['lightbox_caption'] != 'none') {
            $lightbox_caption = $item[$settings['lightbox_caption']] ? 'title="' . htmlspecialchars($item[$settings['lightbox_caption']]) .'"' : '';
        }

        // Filter
        $filter = '';
        if ($item['tags'] && $settings['grid'] == 'dynamic' && $settings['filter'] != 'none') {
            $filter = ' data-uk-filter="' . implode(',', $item['tags']) . '"';
        }

    ?>

    <div<?php echo $filter; ?>>
    <?php echo $this->render('plugins/widgets/' . $widget->getConfig('name')  . '/views/_' . $settings['overlay'] . '.php', compact('item', 'settings', 'title_size', 'border', 'thumbnail', 'thumbnail2', 'lightbox', 'lightbox_caption', 'button_link', 'button_lightbox', 'link_target')); ?>
    </div>

    <?php endif; ?>
<?php endforeach; ?>

</div>
<script>
    (function($){

        $('.uk-overlay > img:first', $('#wk-grid<?php echo $settings['id']; ?>')).each(function() {

            var $img = $(this),
                $canvas = $('<canvas class="uk-responsive-width"></canvas>').attr({width:$img.attr('width'), height:$img.attr('height')}),
                img = new Image;

            $img.css('display', 'none').after($canvas);

            img.onload = function(){
                $canvas.remove();
                $img.css('display', '');
            };

            img.src = this.src;
        });

    })(jQuery);
</script>
