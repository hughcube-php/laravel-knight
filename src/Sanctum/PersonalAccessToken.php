<?php

namespace HughCube\Laravel\Knight\Sanctum;

use Carbon\Carbon;
use HughCube\Laravel\Knight\Database\Eloquent\Traits\Model;
use HughCube\Laravel\Knight\Support\Carbon as KnightCarbon;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * Class PersonalAccessToken
 *
 * @property int $id
 * @property string $tokenable_type
 * @property int $tokenable_id
 * @property string $name
 * @property string $token
 * @property string|array|null $abilities
 * @property Carbon|null $last_used_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PersonalAccessToken extends \Laravel\Sanctum\PersonalAccessToken
{
    use Model;

    public function onChangeRefreshCacheKeys(): array
    {
        return [
            [$this->getKeyName() => $this->getKey()],
            ['token' => $this->token],
        ];
    }

    public static function findToken($token)
    {
        if (!str_contains($token, '|')) {
            $token = static::query()->findUniqueRow(['token' => hash('sha256', $token)]);
            return $token instanceof static ? $token : null;
        }

        [$id, $token] = explode('|', $token, 2);
        $instance = static::findById($id);
        if ($instance instanceof self) {
            return hash_equals($instance->token, hash('sha256', $token)) ? $instance : null;
        }

        return null;
    }

    public function tokenable(): Attribute
    {
        return Attribute::make(function ($value, $attributes) {
            $class = $attributes['tokenable_type'];
            return $class::findById($attributes['tokenable_id']);
        });
    }

    public function skipSave($hours = 24): bool
    {
        $dirty = $this->getDirty();

        /** 在app的api情况下, 如果只是修改last_used_at属性, 24个小时内只操作一次 */
        $originalLastUsedAt = KnightCarbon::tryParse($this->getOriginal('last_used_at'));

        return 1 === count($dirty)
            && isset($dirty['last_used_at'])
            && $originalLastUsedAt instanceof Carbon
            && $originalLastUsedAt->clone()->addHours($hours)->isFuture();
    }

    public function save(array $options = []): bool
    {
        if ($this->skipSave()) {
            return true;
        }

        return parent::save($options);
    }
}
