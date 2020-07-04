# Passport multiauth
This package simply enables you to authenticate models other than App\User using laravel passport.

> **Note**: Starting from version 9.x multiauth is built in in laravel passport, therefore this package is usable until version 8.x.

# Installation
`composer require hamedov/passport-multiauth`

# Usage

- Add your guards and providers to config/auth.php
```
'guards' => [
    ...
    'admin' => [
        'driver' => 'passport',
        'provider' => 'admins',
        'hash' => false,
    ],
],

'providers' => [
    ...
    'admins' => [
        'driver' => 'eloquent',
        'model' => App\Admin::class,
    ],
],

```
- Add `guard` parameter to your token request
<br />Example using guzzle http
```
$http = new \GuzzleHttp\Client;
$response = $http->post(config('app.url') . '/oauth/token', [
    'form_params' => [
        'grant_type' => 'password',
        'client_id' => config('api.password_client_id'),
        'client_secret' => config('api.password_client_secret'),
        'username' => 'username@example.com',
        'password' => 'userpassword',
        'scope' => '',
        'guard' => 'admin',
    ],
]);

$data = json_decode((string)$response->getBody(), true);

```
- Authenticate your models using auth middleware with the desired guard as follows:
```
Route::middleware('auth:admin')->get('/user', function (Request $request) {
    return $request->user();
});
```

- No extra parameters required for refresh token requests.
- Of course all models must extend `Illuminate\Foundation\Auth\User` and use `Laravel\Passport\HasApiTokens` Trait.
- That's all.

# Revoke access token
```
$model = auth()->user();
// Get model/user access token
$accessToken = $model->token();
// Revoke access token
DB::table('oauth_refresh_tokens')->where('access_token_id', $accessToken->id)->update([
    'revoked' => true
]);

$accessToken->revoke();
```

# Warning
- Relationships that depend on `oauth_access_tokens` table such as `$user->tokens()` and `$token->user();` are not yet implemented for multiauth and won't work, You wouldn't need them anyway. However they will be implemented in a future release.

# Custom grants:
- Create a new custom grant:

We will be using facebook login as an example here

```
php artisan make:grant FacebookGrant
```

This will create a new grant class in App\Grants folder

- Specify unique identifier for your grant
```
protected $identifier = 'facebook';
```
- Specify The parameters you will be sending in access token request for user authentication instead of username and password
```
protected $request_params = [
    'facebook_access_token',
];
```
The access token request should look like this:
```
$response = $http->post(env('APP_URL') . '/oauth/token', [
    'form_params' => [
        'grant_type' => 'facebook',
        'client_id' => config('api.password_client_id'),
        'client_secret' => config('api.password_client_secret'),
        'facebook_access_token' => 'facebook access token',
        'scope' => '',
        'guard' => 'api',
    ],
]);
```

- Next add your authentication logic to `getUserEntityByRequestParams` method

You will receive an empty instance of the authenticated model as first parameter.

The second parameter is an associative array containing values of parameters specified in `request_params` property.

- The method implementation should be something like this:
```
protected function getUserEntityByRequestParams(Model $model, $request_params,
    $guard, $grantType, ClientEntityInterface $clientEntity)
{
    // Do your logic to authenticate the user.
    // Return false or void if authentication fails.
    // This will throw OAuthServerException.
    $facebook_access_token = $request_params['facebook_access_token'];
    // Contact facebook server to make sure the token is valid and get the corresponding user profile.
    $profile = file_get_contents('https://graph.facebook.com/me?fields=name,email&access_token='.$facebook_access_token);
    $profile = (array) json_decode($profile);
    if ( ! isset($profile['email']))
    {
        // We cannot identify the user without his email address
        return;
    }
    
    // Retrieve user or any authenticatable model by email or create new one.
    $user = $model->firstOrCreate(['email' => $profile['email']], ['name' => $profile['name']]);

    return new User($user->getAuthIdentifier());
}
```

- You can use the previous example for any authenticatable entity without any difference, you just need to provide the guard parameter in token request and implement your authentication logic and return void/false if authentication fails or an instance of Laravel\Passport\Bridge\User for success.

# License
Released under the Mit license, see [LICENSE](https://github.com/hamedov93/passport-multiauth/blob/master/LICENSE)
