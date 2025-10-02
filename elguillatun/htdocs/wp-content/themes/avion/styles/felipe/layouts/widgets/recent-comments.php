<?php
/**
* @package   Warp Theme Framework
* @author    YOOtheme http://www.yootheme.com
* @copyright Copyright (C) YOOtheme GmbH
* @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
*/

global $comments, $comment;

// init vars
$number   = (int) max(isset($widget->params['number']) ? $widget->params['number'] : 5, 1);
$comments = get_comments(array('number' => $number, 'status' => 'approve'));

if ($comments) : ?>
<ul class="uk-comment-list">

    <?php foreach ((array) $comments as $comment) : ?>
    <li>

        <article class="uk-comment">

            <header class="fz-recent-comments-header">

                <div class="fz-recent-comments-avatar">
                    <?php echo get_avatar($comment, $size='35', null, 'Avatar'); ?>
                </div>

                <h4 class="fz-recent-comments-title">
                    <?php echo get_comment_author_link(); ?>
                </h4>

                <p class="uk-comment-meta">
                    <time datetime="<?php echo get_comment_date('Y-m-d'); ?>"><?php comment_date(); ?></time>
                    | <a class="permalink" href="<?php echo htmlspecialchars(get_comment_link($comment->comment_ID)) ?>">#</a>
                </p>

            </header>

            <div class="uk-comment-body">
                <?php echo wp_trim_words( get_comment_text(), 20 ); ?>
            </div>

        </article>

    </li>
    <?php endforeach; ?>

</ul>
<?php endif;
