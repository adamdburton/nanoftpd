<?php

namespace AdamDBurton\NanoFTPd\NanoFTPd\Auth;

class Eloquent
{
    protected $app;
    protected $data;

    function __construct($app)
    {
        $this->app = $app;
    }

    public function authenticate($username, $password)
    {
        $model = $this->app['config']->get('nanoftpd::users.eloquent.model');
        $usernameField = $this->app['config']->get('nanoftpd::users.eloquent.username');
        $passwordField = $this->app['config']->get('nanoftpd::users.eloquent.password');

        $instance = new $model;

        try
        {
            $instance->where($usernameField, $username)->where($passwordField, $password)->firstOrFail();

            $this->data = $instance;

            return $this->data;
        }
        catch(\Exception $e)
        {
            return false;
        }
    }

    public function getUID()
    {
        $field = $this->app['config']->get('nanoftpd::users.eloquent.uid');

        return $this->data->$field;
    }

    public function getGID()
    {
        $field = $this->app['config']->get('nanoftpd::users.eloquent.gid');

        return $this->data->$field;
    }

    public function getHomeDirectory()
    {
        $field = $this->app['config']->get('nanoftpd::users.eloquent.home_path');

        return $this->data->$field;
    }
}