<?php
/**
 * OAuth 2.0 Password grant modified for multiauth.
 *
 * @author      Mohamed Hamed <m.hamed@nezam.io>
 * @copyright   Copyright (c) Mohamed Hamed
 * @license     http://mit-license.org/
 *
 * @link        https://github.com/hamedov/passport-multiauth
 */

namespace Hamedov\PassportMultiauth\Bridge;

use League\OAuth2\Server\Grant\PasswordGrant as BasePasswordGrant;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestEvent;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Password grant class.
 */
class PasswordGrant extends BasePasswordGrant
{
    /**
     * @param ServerRequestInterface $request
     * @param ClientEntityInterface  $client
     *
     * @throws OAuthServerException
     *
     * @return UserEntityInterface
     */
    protected function validateUser(ServerRequestInterface $request, ClientEntityInterface $client)
    {
        $username = $this->getRequestParameter('username', $request);
        if (is_null($username)) {
            throw OAuthServerException::invalidRequest('username');
        }

        $password = $this->getRequestParameter('password', $request);
        if (is_null($password)) {
            throw OAuthServerException::invalidRequest('password');
        }

        $guard = $this->getRequestParameter('guard', $request);
        if (is_null($guard))
        {
            $guard = 'api';
        }

        $user = $this->userRepository->getUserEntityByUserCredentials(
            $username,
            $password,
            $this->getIdentifier(),
            $client,
            $guard
        );
        
        if ($user instanceof UserEntityInterface === false) {
            $this->getEmitter()->emit(new RequestEvent(RequestEvent::USER_AUTHENTICATION_FAILED, $request));

            throw OAuthServerException::invalidCredentials();
        }

        return $user;
    }
}
