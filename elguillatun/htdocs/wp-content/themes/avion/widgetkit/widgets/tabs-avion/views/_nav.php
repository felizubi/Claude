<?php
/**
* @package   Avion
* @author    YOOtheme http://www.yootheme.com
* @copyright Copyright (C) YOOtheme GmbH
* @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
*/

// Position
$tab_position = 'uk-tab-' . $settings['position'];

?>

<ul class="uk-tab <?php echo $tab_position ?>" data-uk-tab="{connect: '#wk-<?php echo $settings['id']; ?>'}">
    <?php foreach ($items as $item) : ?>
        <li class="uk-flex uk-flex-item-1"><a href=""><?php echo $item['title'] ?></a></li>
    <?php endforeach; ?>
</ul>
