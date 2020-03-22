<?php

namespace Hamedov\PassportMultiauth\Bridge;

use Laravel\Passport\Bridge\AccessToken as PassportAccessToken;

use League\OAuth2\Server\CryptKey;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;


class AccessToken extends PassportAccessToken
{
    /**
     * Generate a JWT from the access token
     *
     * @param CryptKey $privateKey
     *
     * @return Token
     */
    public function convertToJWT(CryptKey $privateKey)
    {
        $guard = request()->get('guard') ?: 'api';
        
        return (new Builder())
            ->setAudience($this->getClient()->getIdentifier())
            ->setId($this->getIdentifier(), true)
            ->setIssuedAt(time())
            ->setNotBefore(time())
            ->setExpiration($this->getExpiryDateTime()->getTimestamp())
            ->setSubject($this->getUserIdentifier())
            ->set('scopes', $this->getScopes())
            ->set('guard', $guard)
            ->sign(new Sha256(), new Key($privateKey->getKeyPath(), $privateKey->getPassPhrase()))
            ->getToken();
    }
}
