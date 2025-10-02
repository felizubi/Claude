<?php

namespace YOOtheme\Framework\Wordpress;

class Session
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        if (!session_id())
            session_start();
    }

    /**
     * Checks if a key exists.
     *
     * @param  string $name
     * @return bool
     */
    public function has($name)
    {
        return isset($_SESSION[$name]);
    }

    /**
     * Gets a value.
     *
     * @param  string $name
     * @param  mixed $default
     * @return mixed
     */
    public function get($name, $default = null)
    {
        return isset($_SESSION[$name]) ? $_SESSION[$name] : $default;
    }

    /**
     * Sets a value.
     *
     * @param  string $name
     * @param  mixed $value
     */
    public function set($name, $value)
    {
        $_SESSION[$name] = $value;
    }

    /**
     * Unsets a key.
     *
     * @param  string $name
     */
    public function clear($name)
    {
        unset($_SESSION[$name]);
    }
}
