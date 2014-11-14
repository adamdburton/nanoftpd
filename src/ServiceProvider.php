<?php

namespace AdamDBurton\NanoFTPd;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('adamdburton/nanoftpd');

        $this->registerCommands();
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        $this->app['nanoftpd'] = $this->app->share(
            function($app)
            {
                $server = new NanoFTPd\Server;

                return $server;
            }
        );

        // Register the package configuration with the loader.
        $this->app['config']->package('adamdburton/nanoftpd', __DIR__.'/config');
	}

    /**
     * Register console command bindings.
     *
     * @return void
     */
    protected function registerCommands()
    {
        $this->app->bindIf('command.nanoftpd', function()
        {
            $command = new Command\NanoFTPd;

            return $command;
        });

        $this->commands(
            'command.nanoftpd'
        );
    }

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
        return array(
            'command.nanoftpd',
            'nanoftpd'
        );
	}

}
