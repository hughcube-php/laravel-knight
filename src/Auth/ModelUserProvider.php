<?php

namespace HughCube\Laravel\Knight\Auth;

use HughCube\Laravel\Knight\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;

class ModelUserProvider implements UserProvider
{

    /**
     * The hasher implementation.
     *
     * @var HasherContract
     */
    protected HasherContract $hasher;

    /**
     * The Eloquent user model.
     *
     * @var class-string<Model> $model
     */
    protected string $model;

    /**
     * Create a new database user provider.
     *
     * @param HasherContract $hasher
     * @param string $model
     */
    public function __construct(HasherContract $hasher, string $model)
    {
        $this->model = $model;
        $this->hasher = $hasher;
    }

    /**
     * @param $identifier
     * @return Authenticatable|null
     */
    protected function findUser($identifier): ?Authenticatable
    {
        $class = $this->model;

        /** @var Authenticatable|null $model */
        $model = $class::findById($identifier);

        return $model;
    }

    /**
     * @inheritDoc
     */
    public function retrieveById($identifier)
    {
        return $this->findUser($identifier);
    }

    /**
     * @inheritDoc
     */
    public function retrieveByToken($identifier, $token)
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
    }

    /**
     * @inheritDoc
     */
    public function retrieveByCredentials(array $credentials)
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false)
    {
    }
}
