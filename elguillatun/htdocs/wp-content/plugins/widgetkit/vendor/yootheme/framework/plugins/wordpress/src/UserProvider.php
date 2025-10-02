<?php

namespace YOOtheme\Framework\Wordpress;

use YOOtheme\Framework\User\User;
use YOOtheme\Framework\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function get($id = null)
    {
        if ($id === null) {
            $id = get_current_user_id();
        }

        return $this->loadUserBy('id', $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getByUsername($username)
    {
        return $this->loadUserBy('login', $username);
    }

    /**
     * Loads a user.
     *
     * @param  string $field
     * @param  string $value
     * @return UserInterface
     */
    protected function loadUserBy($field, $value)
    {
        if ($user = get_user_by($field, $value)) {
            return new User(array('id' => $user->ID, 'username' => $user->user_login, 'name' => $user->user_nicename, 'email' => $user->user_email, 'permissions' => array_keys($user->allcaps)));
        }
    }
}
