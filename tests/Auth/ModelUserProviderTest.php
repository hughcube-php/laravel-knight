<?php

namespace HughCube\Laravel\Knight\Tests\Auth;

use HughCube\Laravel\Knight\Auth\ModelUserProvider;
use HughCube\Laravel\Knight\Database\Eloquent\Model;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Contracts\Auth\Authenticatable;

class ModelUserProviderTest extends TestCase
{
    public function testDelegatesToModelStaticMethods()
    {
        TestUserModel::resetCalls();

        $provider = new ModelUserProvider(TestUserModel::class);
        $user = new TestAuthenticatable();

        $this->assertSame('id-42', $provider->retrieveById(42));
        $this->assertSame('token-7-abc', $provider->retrieveByToken(7, 'abc'));
        $this->assertSame('remember-xyz', $provider->updateRememberToken($user, 'xyz'));

        $credentials = ['email' => 'user@example.com', 'password' => 'secret'];
        $this->assertSame(['credentials' => $credentials], $provider->retrieveByCredentials($credentials));
        $this->assertTrue($provider->validateCredentials($user, $credentials));

        $badCredentials = ['email' => 'user@example.com', 'password' => 'bad'];
        $this->assertFalse($provider->validateCredentials($user, $badCredentials));
        $this->assertSame('forced', $provider->rehashPasswordIfRequired($user, $credentials, true));

        $this->assertSame(
            [
                ['retrieveById', [42]],
                ['retrieveByToken', [7, 'abc']],
                ['updateRememberToken', [$user, 'xyz']],
                ['retrieveByCredentials', [$credentials]],
                ['validateCredentials', [$user, $credentials]],
                ['validateCredentials', [$user, $badCredentials]],
                ['rehashPasswordIfRequired', [$user, $credentials, true]],
            ],
            TestUserModel::$calls
        );
    }
}

class TestUserModel extends Model
{
    public static array $calls = [];

    public static function resetCalls(): void
    {
        self::$calls = [];
    }

    private static function record(string $method, array $args): void
    {
        self::$calls[] = [$method, $args];
    }

    public static function retrieveById($identifier)
    {
        self::record(__FUNCTION__, func_get_args());

        return 'id-'.$identifier;
    }

    public static function retrieveByToken($identifier, $token)
    {
        self::record(__FUNCTION__, func_get_args());

        return 'token-'.$identifier.'-'.$token;
    }

    public static function updateRememberToken(Authenticatable $user, $token)
    {
        self::record(__FUNCTION__, func_get_args());

        return 'remember-'.$token;
    }

    public static function retrieveByCredentials(array $credentials)
    {
        self::record(__FUNCTION__, func_get_args());

        return ['credentials' => $credentials];
    }

    public static function validateCredentials(Authenticatable $user, array $credentials)
    {
        self::record(__FUNCTION__, func_get_args());

        return ($credentials['password'] ?? null) === 'secret';
    }

    public static function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false)
    {
        self::record(__FUNCTION__, func_get_args());

        return $force ? 'forced' : 'skipped';
    }
}

class TestAuthenticatable implements Authenticatable
{
    public function getAuthIdentifierName()
    {
        return 'id';
    }

    public function getAuthIdentifier()
    {
        return 1;
    }

    public function getAuthPasswordName()
    {
        return 'password';
    }

    public function getAuthPassword()
    {
        return 'secret';
    }

    public function getRememberToken()
    {
        return null;
    }

    public function setRememberToken($value)
    {
    }

    public function getRememberTokenName()
    {
        return 'remember_token';
    }
}
