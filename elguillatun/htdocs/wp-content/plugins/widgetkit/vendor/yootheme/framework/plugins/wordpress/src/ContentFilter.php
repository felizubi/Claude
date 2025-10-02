<?php

namespace YOOtheme\Framework\Wordpress;

use YOOtheme\Framework\Filter\FilterInterface;

class ContentFilter implements FilterInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter($value)
    {
        return apply_filters('the_content', $value);
    }
}
