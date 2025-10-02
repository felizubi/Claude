<?php

namespace YOOtheme\Framework\Wordpress;

use YOOtheme\Framework\Csrf\DefaultCsrfProvider;

class CsrfProvider extends DefaultCsrfProvider
{
    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        return wp_create_nonce();
    }

    /**
     * {@inheritdoc}
     */
    public function validate($token)
    {
        return (bool) wp_verify_nonce($token);
    }
}
