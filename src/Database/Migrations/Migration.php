<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2026/1/19
 * Time: 19:08
 */

namespace HughCube\Laravel\Knight\Database\Migrations;

use HughCube\Laravel\Knight\Database\Migrations\Mixin\BlueprintMixin;
use HughCube\Laravel\Knight\Database\Migrations\Mixin\PostgresGrammarMixin;
use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration as IlluminateMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\PostgresGrammar as PostgresSchemaGrammar;
use Illuminate\Support\Facades\DB;
use ReflectionException;

/**
 * 迁移文件基类
 *
 * 继承此类后，Blueprint 将自动获得以下扩展方法:
 * - knightColumnsReversed(): 添加常用字段 (created_at, updated_at, deleted_at, ukey, data_version)
 * - knightColumns(): 添加常用字段 (ukey, data_version, created_at, updated_at, deleted_at)
 * - knightGin($columns, $indexName): 创建 GIN 索引，支持单列或多列 (PostgreSQL)
 * - knightUniqueWhere($columns, $where, $indexName): 创建条件唯一索引 (PostgreSQL)
 * - knightIndexWhere($columns, $where, $indexName): 创建条件索引 (PostgreSQL)
 * - knightUniqueWhereNotDeleted($columns, $indexName): 创建未删除记录的唯一索引 (PostgreSQL)
 * - knightIndexWhereNotDeleted($columns, $indexName): 创建未删除记录的索引 (PostgreSQL)
 *
 * 使用示例:
 *   Schema::create('users', function (Blueprint $table) {
 *       $table->id();
 *       $table->string('name');
 *       $table->knightColumns();       // 或 $table->knightColumnsReversed();
 *       $table->knightUniqueWhereNotDeleted('email');
 *       $table->knightGin(['tenant_id', 'tags']); // 多列 GIN 索引需要 btree_gin 扩展
 *   });
 */
class Migration extends IlluminateMigration
{
    protected static bool $mixinRegistered = false;

    /**
     * @throws ReflectionException
     */
    public function __construct()
    {
        static::registerMixin();
    }

    /**
     * 注册 Migration Mixin
     *
     * @throws ReflectionException
     */
    protected static function registerMixin(): void
    {
        if (static::$mixinRegistered) {
            return;
        }

        Blueprint::mixin(new BlueprintMixin());
        PostgresSchemaGrammar::mixin(new PostgresGrammarMixin());

        static::$mixinRegistered = true;
    }

    /**
     * 获取数据库连接实例
     *
     * @return Connection
     */
    protected function getDB(): Connection
    {
        /** @var Connection $connection */
        $connection = DB::connection($this->getConnection());

        return $connection;
    }
}
