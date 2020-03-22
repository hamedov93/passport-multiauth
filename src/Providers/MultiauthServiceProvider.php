<?php
namespace Hamedov\PassportMultiauth\Providers;

use Illuminate\Support\ServiceProvider;
use League\OAuth2\Server\AuthorizationServer;
use Hamedov\PassportMultiauth\Commands\GrantMakeCommand;

class MultiauthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
	        $this->commands([
	            GrantMakeCommand::class,
	        ]);
	    }
    }

    public function register()
    {

    }
}
