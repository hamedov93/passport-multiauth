<?php

namespace Hamedov\PassportMultiauth\Guards;

use Illuminate\Http\Request;
use Laravel\Passport\Guards\TokenGuard as PassportTokenGuard;

class TokenGuard extends PassportTokenGuard
{
    /**
     * Authenticate the incoming request via the Bearer token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    protected function authenticateViaBearerToken($request)
    {
        if (! $psr = $this->getPsrRequestViaBearerToken($request)) {
            return;
        }

        // If the access token is valid we will retrieve the user according to the user ID
        // associated with the token. We will use the provider implementation which may
        // be used to retrieve users from Eloquent. Next, we'll be ready to continue.
        $user = $this->provider->retrieveById(
            $psr->getAttribute('oauth_user_id') ?: null
        );

        if (! $user) {
            return;
        }

        $guard = $psr->getAttribute('oauth_guard');
        
        // Check if the user belongs to the guard the token was issued for.
        $provider = config('auth.guards.'.$guard.'.provider');
        $provider_driver = config('auth.providers.'.$provider.'.driver');
        $provider_model = config('auth.providers.'.$provider.'.model');
        $provider_table = config('auth.providers.'.$provider.'.table');

        if ( ! $guard || ! $provider)
        {
            return;
        }

        if (($provider_driver === 'eloquent' && ! $user instanceof $provider_model) ||
            ($provider_driver === 'database' && $user->getTable() !== $provider_table))
        {
            return;
        }

        // Next, we will assign a token instance to this user which the developers may use
        // to determine if the token has a given scope, etc. This will be useful during
        // authorization such as within the developer's Laravel model policy classes.
        $token = $this->tokens->find(
            $psr->getAttribute('oauth_access_token_id')
        );

        $clientId = $psr->getAttribute('oauth_client_id');

        // Finally, we will verify if the client that issued this token is still valid and
        // its tokens may still be used. If not, we will bail out since we don't want a
        // user to be able to send access tokens for deleted or revoked applications.
        if ($this->clients->revoked($clientId)) {
            return;
        }

        return $token ? $user->withAccessToken($token) : null;
    }
}
