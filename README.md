# Passport multiauth
This package simply enables you to authenticate models other than App\User using laravel passport.

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
- Of course all models must extend `Illuminate\Foundation\Auth\User` and use `Laravel\Passport\HasApiTokens` Trait
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
- This feature is still in development.

# License
Released under the Mit license, see [LICENSE](https://github.com/hamedov93/passport-multiauth/blob/master/LICENSE)
