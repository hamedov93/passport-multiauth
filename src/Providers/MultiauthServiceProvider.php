<?php
namespace Hamedov\PassportMultiauth\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Laravel\Passport\Bridge\RefreshTokenRepository;
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

        // Enable user custom grants
        $this->enableCustomGrants();
    }

    public function register()
    {

    }

    public function enableCustomGrants()
    {
        // Check whether the grants directory exists
        $grants_dir = app_path('Grants');
        if ( ! file_exists($grants_dir))
        {
            return;
        }

        // Loop through the grants and enable them one by one
        foreach (glob($grants_dir . '/*.php') as $grant_path)
        {
            $grant_class_name = basename($grant_path, '.php');
            $grant_class = '\App\Grants\\' . $grant_class_name;
            $grant = new $grant_class(
                $this->app->make(RefreshTokenRepository::class)
            );

            $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());

            app(AuthorizationServer::class)->enableGrantType(
                $grant, Passport::tokensExpireIn()
            );
        }
    }
}
