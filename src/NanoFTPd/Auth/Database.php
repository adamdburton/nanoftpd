<?php

namespace AdamDBurton\NanoFTPd\NanoFTPd\Auth;

class Database
{
    protected $app;
    protected $data;

    function __construct($app)
    {
        $this->app = $app;
    }

    public function authenticate($username, $password)
    {
        $table = $this->app['config']->get('nanoftpd::users.eloquent.model');
        $usernameField = $this->app['config']->get('nanoftpd::users.eloquent.username');
        $passwordField = $this->app['config']->get('nanoftpd::users.eloquent.password');

        $row = $this->app['db']->table($table)->where($usernameField, $username)->where($passwordField, $password)->first();

        if($row)
        {
            $this->data = $row;

            return $this->data;
        }
        else
        {
            return false;
        }
    }

    public function getUID()
    {
        return $this->data[$this->app->config('nanoftpd::users.database.uid')];
    }

    public function getGID()
    {
        return $this->data[$this->app->config('nanoftpd::users.database.gid')];
    }

    public function getHomeDirectory()
    {
        return $this->data[$this->app->config('nanoftpd::users.database.home_path')];
    }
}