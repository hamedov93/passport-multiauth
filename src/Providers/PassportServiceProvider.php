<?php
namespace Hamedov\PassportMultiauth\Providers;

use Illuminate\Auth\RequestGuard;
use Illuminate\Support\Facades\Auth;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\ResourceServer;
use Laravel\Passport\PassportServiceProvider as BasePassportServiceProvider;
use Laravel\Passport\Passport;
use Laravel\Passport\TokenRepository;
use Laravel\Passport\ClientRepository as BaseClientRepository;
use Laravel\Passport\Bridge\ClientRepository;
use Laravel\Passport\Bridge\ScopeRepository;
use Hamedov\PassportMultiauth\Bridge\PasswordGrant;
use Hamedov\PassportMultiauth\Bridge\AccessTokenRepository;
use Hamedov\PassportMultiauth\OAuth2\BearerTokenValidator;
use Hamedov\PassportMultiauth\Guards\TokenGuard;


class PassportServiceProvider extends BasePassportServiceProvider
{
    /**
     * Create and configure a Password grant instance.
     *
     * @return PasswordGrant
     */
    protected function makePasswordGrant()
    {
        $grant = new PasswordGrant(
            $this->app->make(\Hamedov\PassportMultiauth\Bridge\UserRepository::class),
            $this->app->make(\Laravel\Passport\Bridge\RefreshTokenRepository::class)
        );

        $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());

        return $grant;
    }

    /**
     * Make the authorization service instance.
     *
     * @return \League\OAuth2\Server\AuthorizationServer
     */
    public function makeAuthorizationServer()
    {
        return new AuthorizationServer(
            $this->app->make(ClientRepository::class),
            // Override AccessTokenRepository
            $this->app->make(AccessTokenRepository::class),
            $this->app->make(ScopeRepository::class),
            $this->makeCryptKey('private'),
            app('encrypter')->getKey()
        );
    }

    /**
     * Register the resource server.
     *
     * @return void
     */
    protected function registerResourceServer()
    {
        $this->app->singleton(ResourceServer::class, function () {
            return new ResourceServer(
                // Override AccessTokenRepository
                $this->app->make(AccessTokenRepository::class),
                $this->makeCryptKey('public'),
                $this->app->make(BearerTokenValidator::class)
            );
        });
    }

    /**
     * Make an instance of the token guard.
     *
     * @param  array  $config
     * @return \Illuminate\Auth\RequestGuard
     */
    protected function makeGuard(array $config)
    {
        return new RequestGuard(function ($request) use ($config) {
            // Override TokenGuard
            return (new TokenGuard(
                $this->app->make(ResourceServer::class),
                Auth::createUserProvider($config['provider']),
                $this->app->make(TokenRepository::class),
                $this->app->make(BaseClientRepository::class),
                $this->app->make('encrypter')
            ))->user($request);
        }, $this->app['request']);
    }
}
