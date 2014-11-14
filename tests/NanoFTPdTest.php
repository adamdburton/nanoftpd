<?php

class NanoFTPdTest extends Orchestra\Testbench\TestCase
{
    protected function getPackageProviders()
    {
        return [ 'AdamDBurton\NanoFTPd\NanoFTPdServiceProvider' ];
    }

    protected function getPackageAliases()
    {
        return [
            'NanoFTPd' => 'AdamDBurton\NanoFTPd\Facade\NanoFTPd'
        ];
    }

    public function testServerStarts()
    {
        Log::shouldReceive('info')->once()->with('[NanoFTPd] Server starting...');

        $this->app['nanoftpd']->shouldRun = false;
        $this->app['nanoftpd']->run();
    }
}