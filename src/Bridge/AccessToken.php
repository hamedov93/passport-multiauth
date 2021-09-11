<?php

namespace Hamedov\PassportMultiauth\Bridge;

use DateTimeImmutable;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

use League\OAuth2\Server\CryptKey;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;

class AccessToken  implements AccessTokenEntityInterface
{
    use AccessTokenTrait, EntityTrait, TokenEntityTrait;

    /**
     * Create a new token instance.
     *
     * @param  string  $userIdentifier
     * @param  array  $scopes
     * @param  \League\OAuth2\Server\Entities\ClientEntityInterface  $client
     * @return void
     */
    public function __construct($userIdentifier, array $scopes, ClientEntityInterface $client)
    {
        $this->setUserIdentifier($userIdentifier);

        foreach ($scopes as $scope) {
            $this->addScope($scope);
        }

        $this->setClient($client);
    }

    /* Generate a JWT from the access token
     *
     * @param CryptKey $privateKey
     *
     * @return Token
     */
    private function convertToJWT()
    {
        $guard = request()->get('guard') ?: 'api';

        $this->initJwtConfiguration();
        
        return $this->jwtConfiguration->builder()
            ->permittedFor($this->getClient()->getIdentifier())
            ->identifiedBy($this->getIdentifier())
            ->issuedAt(new DateTimeImmutable())
            ->canOnlyBeUsedAfter(new DateTimeImmutable())
            ->expiresAt($this->getExpiryDateTime())
            ->relatedTo((string) $this->getUserIdentifier())
            ->withClaim('scopes', $this->getScopes())
            ->withClaim('guard', $guard)
            ->getToken($this->jwtConfiguration->signer(), $this->jwtConfiguration->signingKey());
    }
}