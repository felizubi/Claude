<?php
/**
* @package   Avion
* @author    YOOtheme http://www.yootheme.com
* @copyright Copyright (C) YOOtheme GmbH
* @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
*/

$class = trim(implode(' ', array(
    'uk-list',
    $settings['class'],
    $settings['space']   ? 'uk-list-space':'',
    $settings['line']    ? 'uk-list-line':'',
    $settings['striped'] ? 'uk-list-striped':'',
)));

?>

<ul class="<?php echo $class ?>">
    <?php foreach ($items as $i => $item): ?>
    <li>

        <?php

           // Meta
           $meta = '';

           if ($settings['meta']) {

               if ($item['date']) {

                   $date = '<time datetime="'.date('c', strtotime($item['date'])).'">'. date('M d', strtotime($item['date'])).'</time>';

                   if ($item['author']) {
                       $meta = $app['translator']->trans('%author% %date%',  array('%author%' => $item['author'], '%date%' => $date));
                   } else {
                       $meta = $app['translator']->trans('%date%',  array('%date%' => $date));
                   }

               } elseif ($item['author']) {
                   $meta = $app['translator']->trans('%author%',  array('%author%' => $item['author']));
               }
           }

           // Content
           $content = '';

            if ($settings['content']) {

                $class   = '';
                $content = str_replace('<a href', '<a class="uk-text-break" href', $item['content']);

                if ($settings['text_size'] == 'large') {
                    $class = 'tm-text-large';
                }

                switch ($settings['text_style']) {

                   case 'italic':
                       $content = '<div class="'.$class.'"><i>"'.$content.'"</i></div>';
                       break;

                   case 'blockquote':
                       $content = '<blockquote class="'.$class.'"><p>'.$content.'</p></blockquote>';
                       break;

                   default:
                       $content = '<div class="'.$class.'">'.$content.'</div>';
                       break;
                }
            }

           // Media
            $media  = '';

            if ($settings['media']) {

                $attrs  = array('class' => '');
                $width  = $item['media.width'];
                $height = $item['media.height'];

                if ($item->type('media') == 'image') {

                    $attrs['alt'] = strip_tags($item['title']);
                    $width        = ($settings['image_width'] != 'auto') ? $settings['image_width'] : '';
                    $height       = ($settings['image_height'] != 'auto') ? $settings['image_height'] : '';
                }

                if ($item->type('media') == 'video') {
                    $attrs['class']    = 'uk-responsive-width';
                    $attrs['controls'] = true;
                }

                if ($item->type('media') == 'iframe') {
                    $attrs['class'] = 'uk-responsive-width';
                }

                $attrs['width']  = ($width) ? $width : '';
                $attrs['height'] = ($height) ? $height : '';

                if (($item->type('media') == 'image') && ($settings['image_width'] != 'auto' || $settings['image_height'] != 'auto')) {
                    $media = $item->thumbnail('media', $width, $height, $attrs);
                } else {
                    $media = $item->media('media', $attrs);
                }
            }


            // Title

            $title = $settings['title'] ? $item['title'] : '';

            if ($title) {

                if ($settings['title_link']) {
                    $title = '<a href="'.$item->escape('link').'" target="_blank">'.$title.'</a>';
                }

            }

        ?>

        <?php if ($media && $settings['media_position'] == 'top') : ?>
        <p class="uk-text-center"><?php echo $media; ?></p>
        <?php endif; ?>





        <?php if ($settings['meta_position'] == 'top') : ?>

        <?php if ($title || $meta) : ?>

        <p>
        <?php if ($meta) : ?>
            <span class="uk-text-muted"><?php echo $meta; ?></span>
        <?php endif; ?>
        <?php if ($title) : ?>
           <span><?php echo $title; ?></span>
        <?php endif; ?>
        </p>

        <?php endif; ?>

        <?php endif; ?>






        <?php if ($content) : ?>
            <?php echo $content ?>
        <?php endif; ?>

        <?php if ($media && $settings['media_position'] == 'bottom') : ?>
            <p class="uk-text-center"><?php echo $media; ?></p>
        <?php endif; ?>





        <?php if ($settings['meta_position'] == 'bottom') : ?>


        <?php if ($title || $meta) : ?>

          <p>
          <?php if ($meta) : ?>
              <span class="uk-text-muted"><?php echo $meta; ?></span>
          <?php endif; ?>
          <?php if ($title) : ?>
             <span><?php echo $title; ?></span>
          <?php endif; ?>
          </p>

        <?php endif; ?>

        <?php endif; ?>







    </li>
    <?php endforeach; ?>
</ul>
