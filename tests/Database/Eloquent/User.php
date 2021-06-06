<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/5
 * Time: 2:50 下午.
 */

namespace HughCube\Laravel\Knight\Tests\Database\Eloquent;

use HughCube\Laravel\Knight\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\CacheInterface;

class User extends Model
{
    const CACHE = 'array';

    protected $table = 'users';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'nickname',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $hidden = [];

    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
        'deleted_at' => 'timestamp',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * 获取缓存.
     *
     * @return CacheInterface;
     */
    public function getCache()
    {
        return Cache::store('array');
    }
}
