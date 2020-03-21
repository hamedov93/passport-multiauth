<?php
namespace Hamedov\PassportMultiauth\Bridge;

use App;
use Illuminate\Http\Request;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use Laravel\Passport\Bridge\UserRepository as PassportUserRepository;
use Laravel\Passport\Bridge\User;
use RuntimeException;


class UserRepository extends PassportUserRepository
{
    /**
     * {@inheritdoc}
     */
    public function getUserEntityByUserCredentials($username, $password, $grantType, ClientEntityInterface $clientEntity, $guard = 'api')
    {
        $provider = config("auth.guards.{$guard}.provider");


        if (is_null($model = config("auth.providers.{$provider}.model"))) {
            throw new RuntimeException('Unable to determine user model from configuration.');
        }


        if (method_exists($model, 'findForPassport')) {
            $user = (new $model)->findForPassport($username);
        } else {
            $user = (new $model)->where('email', $username)->first();
        }


        if (! $user ) {
            return;
        } elseif (method_exists($user, 'validateForPassportPasswordGrant')) {
            if (! $user->validateForPassportPasswordGrant($password)) {
                return;
            }
        } elseif (! $this->hasher->check($password, $user->getAuthPassword())) {
            return;
        }

        return new User($user->getAuthIdentifier());
    }
}