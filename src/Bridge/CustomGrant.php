<?php

namespace Hamedov\PassportMultiauth\Bridge;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Laravel\Passport\Bridge\User;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AbstractGrant;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\RequestEvent;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;


abstract class CustomGrant extends AbstractGrant
{
    /**
     * @param RefreshTokenRepositoryInterface $refreshTokenRepository
     */
    public function __construct(RefreshTokenRepositoryInterface $refreshTokenRepository)
    {
        $this->setRefreshTokenRepository($refreshTokenRepository);

        $this->refreshTokenTTL = new \DateInterval('P1M');
    }

    /**
     * {@inheritdoc}
     */
    public function respondToAccessTokenRequest(ServerRequestInterface $request, ResponseTypeInterface $responseType, \DateInterval $accessTokenTTL)
    {
        // Validate request
        $client = $this->validateClient($request);
        $scopes = $this->validateScopes($this->getRequestParameter('scope', $request));
        $user = $this->validateUser($request, $client);

        // Finalize the requested scopes
        $scopes = $this->scopeRepository->finalizeScopes($scopes, $this->getIdentifier(), $client, $user->getIdentifier());

        // Issue and persist new tokens
        $accessToken = $this->issueAccessToken($accessTokenTTL, $client, $user->getIdentifier(), $scopes);
        $refreshToken = $this->issueRefreshToken($accessToken);

        // Inject tokens into response
        $responseType->setAccessToken($accessToken);
        $responseType->setRefreshToken($refreshToken);

        return $responseType;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ClientEntityInterface $client
     *
     * @return UserEntityInterface
     * @throws OAuthServerException|RuntimeException
     */
    protected function validateUser(ServerRequestInterface $request, ClientEntityInterface $client)
    {
        if ( ! isset($this->request_params) || empty($this->request_params))
        {
            throw new RuntimeException('No request parameters specified fot this grant type.');
        }

        // Store parameter values in an array to pass to get user method.
        $request_params = [];

        // Check if all required parameters are present.
        foreach ($this->getRequestParams() as $param) {
            $param_value = $this->getRequestParameter($param, $request);
            if ( ! isset($param_value))
            {
                throw OAuthServerException::invalidRequest($param);
            }

            $request_params[$param] = $param_value;
        }

        // Get the model being authenticated
        $guard = $this->getRequestParameter('guard', $request);
        if (is_null($guard))
        {
            $guard = 'api';
        }

        $provider = config("auth.guards.{$guard}.provider");

        if (is_null($model = config('auth.providers.'.$provider.'.model'))) {
            throw new RuntimeException('Unable to determine authentication model from configuration.');
        }


        $user = $this->getUserEntityByRequestParams(new $model, $request_params, $guard, $this->getIdentifier(), $client);

        if ($user instanceof UserEntityInterface === false) {
            $this->getEmitter()->emit(new RequestEvent(RequestEvent::USER_AUTHENTICATION_FAILED, $request));

            throw OAuthServerException::invalidCredentials();
        }

        return $user;
    }

    /**
     *  Retrieve a user by the given parameters.
     *
     * @param Model  $model          The model being authenticated
     * @param array  $request_params Request parameters used to authenticate the user
     * @param string $guard          The guard used for authentication
     * @param string $grantType
     * @param ClientEntityInterface  $clientEntity
     *
     * @return \Laravel\Passport\Bridge\User|null
     * @throws OAuthServerException
     */
    abstract protected function getUserEntityByRequestParams(Model $model, $request_params,
        $guard, $grantType, ClientEntityInterface $clientEntity);


    /**
     * Get required request parameters for this grant.
     * @return array
     */
    public function getRequestParams()
    {
        return $this->request_params;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }
}
