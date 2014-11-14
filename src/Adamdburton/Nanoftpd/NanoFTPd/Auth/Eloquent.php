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

            $this->data = $instance->toArray();

            return $this->data;
        }
        catch(\Exception $e)
        {
            return false;
        }
    }

    public function getUID()
    {
        return $this->data[$this->app['config']->get('nanoftpd::users.eloquent.uid')];
    }

    public function getGID()
    {
        return $this->data[$this->app['config']->get('nanoftpd::users.eloquent.gid')];
    }

    public function getHomeDirectory()
    {
        return $this->data[$this->app['config']->get('nanoftpd::users.eloquent.home_path')];
    }
}