<?php

namespace AdamDBurton\NanoFTPd\Facade;

class NanoFTPd extends \Illuminate\Support\Facades\Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'nanoftpd';
    }
}
