<?php

namespace AdamDBurton\NanoFTPd\Command;

/**
 * Artisan command to run the FTP server.
 */
class NanoFTPd extends \Illuminate\Console\Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'nanoftpd';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the NanoFTPd server';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->laravel['nanoftpd']->run();
    }
}
