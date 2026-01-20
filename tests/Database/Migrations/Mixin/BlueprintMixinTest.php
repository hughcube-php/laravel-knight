<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2026/1/20
 * Time: 10:00.
 */

namespace HughCube\Laravel\Knight\Tests\Database\Migrations\Mixin;

use HughCube\Laravel\Knight\Database\Migrations\Mixin\BlueprintMixin;
use HughCube\Laravel\Knight\Database\Migrations\Mixin\PostgresGrammarMixin;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\PostgresGrammar as PostgresSchemaGrammar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BlueprintMixin Tests.
 *
 * Tests for Blueprint extension methods provided by BlueprintMixin.
 */
class BlueprintMixinTest extends TestCase
{
    protected static bool $mixinRegistered = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (!self::$mixinRegistered) {
            Blueprint::mixin(new BlueprintMixin());
            PostgresSchemaGrammar::mixin(new PostgresGrammarMixin());
            self::$mixinRegistered = true;
        }

        // Clean up test tables before each test
        $this->cleanupTestTables();
    }

    protected function tearDown(): void
    {
        // Clean up test tables after each test
        $this->cleanupTestTables();
        parent::tearDown();
    }

    protected function cleanupTestTables(): void
    {
        Schema::dropIfExists('knight_mixin_test');
        Schema::dropIfExists('knight_gin_test');
        Schema::dropIfExists('knight_unique_where_test');
        Schema::dropIfExists('knight_index_where_test');
        Schema::dropIfExists('knight_alter_test');

        if ($this->isPgsqlConfigured()) {
            try {
                DB::connection('pgsql')->statement('DROP TABLE IF EXISTS knight_mixin_test_pg');
                DB::connection('pgsql')->statement('DROP TABLE IF EXISTS knight_gin_test_pg');
                DB::connection('pgsql')->statement('DROP TABLE IF EXISTS knight_gin_btree_test');
                DB::connection('pgsql')->statement('DROP TABLE IF EXISTS knight_unique_where_test_pg');
                DB::connection('pgsql')->statement('DROP TABLE IF EXISTS knight_index_where_test_pg');
                DB::connection('pgsql')->statement('DROP TABLE IF EXISTS knight_alter_test_pg');
            } catch (\Throwable $e) {
                // Ignore cleanup errors
            }
        }
    }

    // ==================== knightColumns Tests ====================

    public function testKnightColumnsCreatesExpectedColumns(): void
    {
        Schema::create('knight_mixin_test', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->knightColumns();
        });

        $this->assertTrue(Schema::hasTable('knight_mixin_test'));
        $this->assertTrue(Schema::hasColumn('knight_mixin_test', 'created_at'));
        $this->assertTrue(Schema::hasColumn('knight_mixin_test', 'updated_at'));
        $this->assertTrue(Schema::hasColumn('knight_mixin_test', 'deleted_at'));
        $this->assertTrue(Schema::hasColumn('knight_mixin_test', 'ukey'));
        $this->assertTrue(Schema::hasColumn('knight_mixin_test', 'data_version'));
    }

    public function testKnightColumnsWithPostgres(): void
    {
        $this->skipIfPgsqlNotConfigured();

        $connection = DB::connection('pgsql');
        $schema = $connection->getSchemaBuilder();

        $schema->create('knight_mixin_test_pg', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->knightColumns();
        });

        $this->assertTrue($schema->hasTable('knight_mixin_test_pg'));
        $this->assertTrue($schema->hasColumn('knight_mixin_test_pg', 'created_at'));
        $this->assertTrue($schema->hasColumn('knight_mixin_test_pg', 'updated_at'));
        $this->assertTrue($schema->hasColumn('knight_mixin_test_pg', 'deleted_at'));
        $this->assertTrue($schema->hasColumn('knight_mixin_test_pg', 'ukey'));
        $this->assertTrue($schema->hasColumn('knight_mixin_test_pg', 'data_version'));

        // Verify data_version default value
        $connection->table('knight_mixin_test_pg')->insert(['name' => 'test']);
        $record = $connection->table('knight_mixin_test_pg')->first();
        $this->assertEquals(0, $record->data_version);
    }

    public function testKnightColumnsDataInsert(): void
    {
        Schema::create('knight_mixin_test', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->knightColumns();
        });

        // Insert data without specifying knight columns
        DB::table('knight_mixin_test')->insert(['name' => 'test']);

        $record = DB::table('knight_mixin_test')->first();
        $this->assertEquals('test', $record->name);
        $this->assertEquals(0, $record->data_version);
        $this->assertNull($record->ukey);
        $this->assertNull($record->deleted_at);
    }

    // ==================== knightGin Index Tests ====================

    public function testKnightGinIndex(): void
    {
        $this->skipIfPgsqlNotConfigured();

        $connection = DB::connection('pgsql');
        $schema = $connection->getSchemaBuilder();

        $schema->create('knight_gin_test_pg', function (Blueprint $table) {
            $table->id();
            $table->jsonb('tags')->nullable();
            $table->knightGin('tags');
        });

        $this->assertTrue($schema->hasTable('knight_gin_test_pg'));

        // Verify index exists
        $indexes = $connection->select(
            "SELECT indexname FROM pg_indexes WHERE tablename = 'knight_gin_test_pg' AND indexname LIKE '%_gin'"
        );
        $this->assertNotEmpty($indexes);
        $this->assertEquals('idx_knight_gin_test_pg_tags_gin', $indexes[0]->indexname);
    }

    public function testKnightGinIndexWithCustomName(): void
    {
        $this->skipIfPgsqlNotConfigured();

        $connection = DB::connection('pgsql');
        $schema = $connection->getSchemaBuilder();

        $schema->create('knight_gin_test_pg', function (Blueprint $table) {
            $table->id();
            $table->jsonb('metadata')->nullable();
            $table->knightGin('metadata', 'custom_gin_index');
        });

        // Verify custom index name
        $indexes = $connection->select(
            "SELECT indexname FROM pg_indexes WHERE tablename = 'knight_gin_test_pg' AND indexname = 'custom_gin_index'"
        );
        $this->assertNotEmpty($indexes);
    }

    public function testKnightGinIndexFunctionality(): void
    {
        $this->skipIfPgsqlNotConfigured();

        $connection = DB::connection('pgsql');
        $schema = $connection->getSchemaBuilder();

        $schema->create('knight_gin_test_pg', function (Blueprint $table) {
            $table->id();
            $table->jsonb('tags')->nullable();
            $table->knightGin('tags');
        });

        // Insert test data
        $connection->table('knight_gin_test_pg')->insert([
            ['tags' => json_encode(['php', 'laravel'])],
            ['tags' => json_encode(['python', 'django'])],
            ['tags' => json_encode(['php', 'symfony'])],
        ]);

        // Query using GIN index
        $results = $connection->table('knight_gin_test_pg')
            ->whereRaw("tags @> ?", [json_encode(['php'])])
            ->get();

        $this->assertCount(2, $results);
    }

    // ==================== knightGin Multi-column Tests ====================

    public function testKnightGinIndexMultipleColumns(): void
    {
        $this->skipIfPgsqlNotConfigured();
        $this->skipIfBtreeGinExtensionNotAvailable();

        $connection = DB::connection('pgsql');
        $schema = $connection->getSchemaBuilder();

        $schema->create('knight_gin_btree_test', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id');
            $table->jsonb('tags')->nullable();
            $table->knightGin(['tenant_id', 'tags']);
        });

        $this->assertTrue($schema->hasTable('knight_gin_btree_test'));

        // Verify index exists
        $indexes = $connection->select(
            "SELECT indexname FROM pg_indexes WHERE tablename = 'knight_gin_btree_test' AND indexname LIKE '%_gin'"
        );
        $this->assertNotEmpty($indexes);
    }

    public function testKnightGinIndexThreeColumns(): void
    {
        $this->skipIfPgsqlNotConfigured();
        $this->skipIfBtreeGinExtensionNotAvailable();

        $connection = DB::connection('pgsql');
        $schema = $connection->getSchemaBuilder();

        $schema->create('knight_gin_btree_test', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id');
            $table->string('status');
            $table->jsonb('tags')->nullable();
            $table->knightGin(['tenant_id', 'status', 'tags']);
        });

        $this->assertTrue($schema->hasTable('knight_gin_btree_test'));

        // Verify index exists
        $indexes = $connection->select(
            "SELECT indexname FROM pg_indexes WHERE tablename = 'knight_gin_btree_test' AND indexname LIKE '%_gin'"
        );
        $this->assertNotEmpty($indexes);
    }

    // ==================== knightUniqueWhere Tests ====================

    public function testKnightUniqueWhere(): void
    {
        $this->skipIfPgsqlNotConfigured();

        $connection = DB::connection('pgsql');
        $schema = $connection->getSchemaBuilder();

        $schema->create('knight_unique_where_test_pg', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->timestamp('deleted_at')->nullable();
            $table->knightUniqueWhere('email', 'deleted_at IS NULL');
        });

        $this->assertTrue($schema->hasTable('knight_unique_where_test_pg'));

        // Verify unique index exists
        $indexes = $connection->select(
            "SELECT indexname FROM pg_indexes WHERE tablename = 'knight_unique_where_test_pg' AND indexname LIKE 'uk_%'"
        );
        $this->assertNotEmpty($indexes);
    }

    public function testKnightUniqueWhereEnforcesConstraint(): void
    {
        $this->skipIfPgsqlNotConfigured();

        $connection = DB::connection('pgsql');
        $schema = $connection->getSchemaBuilder();

        $schema->create('knight_unique_where_test_pg', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->timestamp('deleted_at')->nullable();
            $table->knightUniqueWhere('email', 'deleted_at IS NULL');
        });

        // Insert first record
        $connection->table('knight_unique_where_test_pg')->insert(['email' => 'test@example.com']);

        // Attempt to insert duplicate - should fail
        $this->expectException(\Illuminate\Database\QueryException::class);
        $connection->table('knight_unique_where_test_pg')->insert(['email' => 'test@example.com']);
    }

    public function testKnightUniqueWhereAllowsDeletedDuplicates(): void
    {
        $this->skipIfPgsqlNotConfigured();

        $connection = DB::connection('pgsql');
        $schema = $connection->getSchemaBuilder();

        $schema->create('knight_unique_where_test_pg', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->timestamp('deleted_at')->nullable();
            $table->knightUniqueWhere('email', 'deleted_at IS NULL');
        });

        // Insert first record
        $connection->table('knight_unique_where_test_pg')->insert(['email' => 'test@example.com']);

        // Soft delete it
        $connection->table('knight_unique_where_test_pg')
            ->where('email', 'test@example.com')
            ->update(['deleted_at' => now()]);

        // Insert same email - should succeed because first is deleted
        $connection->table('knight_unique_where_test_pg')->insert(['email' => 'test@example.com']);

        $count = $connection->table('knight_unique_where_test_pg')
            ->where('email', 'test@example.com')
            ->count();

        $this->assertEquals(2, $count);
    }

    public function testKnightUniqueWhereMultipleColumns(): void
    {
        $this->skipIfPgsqlNotConfigured();

        $connection = DB::connection('pgsql');
        $schema = $connection->getSchemaBuilder();

        $schema->create('knight_unique_where_test_pg', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id');
            $table->string('email');
            $table->timestamp('deleted_at')->nullable();
            $table->knightUniqueWhere(['tenant_id', 'email'], 'deleted_at IS NULL');
        });

        // Insert records with same email but different tenant
        $connection->table('knight_unique_where_test_pg')->insert([
            ['tenant_id' => 1, 'email' => 'test@example.com'],
            ['tenant_id' => 2, 'email' => 'test@example.com'],
        ]);

        $count = $connection->table('knight_unique_where_test_pg')->count();
        $this->assertEquals(2, $count);
    }

    // ==================== knightIndexWhere Tests ====================

    public function testKnightIndexWhere(): void
    {
        $this->skipIfPgsqlNotConfigured();

        $connection = DB::connection('pgsql');
        $schema = $connection->getSchemaBuilder();

        $schema->create('knight_index_where_test_pg', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->timestamp('deleted_at')->nullable();
            $table->knightIndexWhere('status', "status = 'active'");
        });

        $this->assertTrue($schema->hasTable('knight_index_where_test_pg'));

        // Verify index exists
        $indexes = $connection->select(
            "SELECT indexname FROM pg_indexes WHERE tablename = 'knight_index_where_test_pg' AND indexname LIKE 'idx_%'"
        );
        $this->assertNotEmpty($indexes);
    }

    public function testKnightIndexWhereMultipleColumns(): void
    {
        $this->skipIfPgsqlNotConfigured();

        $connection = DB::connection('pgsql');
        $schema = $connection->getSchemaBuilder();

        $schema->create('knight_index_where_test_pg', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('status');
            $table->timestamp('deleted_at')->nullable();
            $table->knightIndexWhere(['user_id', 'status'], 'deleted_at IS NULL');
        });

        $this->assertTrue($schema->hasTable('knight_index_where_test_pg'));

        // Verify index exists
        $indexes = $connection->select(
            "SELECT indexname FROM pg_indexes WHERE tablename = 'knight_index_where_test_pg' AND indexname LIKE 'idx_%'"
        );
        $this->assertNotEmpty($indexes);
    }

    // ==================== knightUniqueWhereNotDeleted Tests ====================

    public function testKnightUniqueWhereNotDeleted(): void
    {
        $this->skipIfPgsqlNotConfigured();

        $connection = DB::connection('pgsql');
        $schema = $connection->getSchemaBuilder();

        $schema->create('knight_unique_where_test_pg', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->timestamp('deleted_at')->nullable();
            $table->knightUniqueWhereNotDeleted('email');
        });

        $this->assertTrue($schema->hasTable('knight_unique_where_test_pg'));

        // Verify unique index exists
        $indexes = $connection->select(
            "SELECT indexname FROM pg_indexes WHERE tablename = 'knight_unique_where_test_pg' AND indexname LIKE 'uk_%'"
        );
        $this->assertNotEmpty($indexes);
    }

    public function testKnightUniqueWhereNotDeletedMultipleColumns(): void
    {
        $this->skipIfPgsqlNotConfigured();

        $connection = DB::connection('pgsql');
        $schema = $connection->getSchemaBuilder();

        $schema->create('knight_unique_where_test_pg', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id');
            $table->string('email');
            $table->timestamp('deleted_at')->nullable();
            $table->knightUniqueWhereNotDeleted(['tenant_id', 'email']);
        });

        $this->assertTrue($schema->hasTable('knight_unique_where_test_pg'));
    }

    public function testKnightUniqueWhereNotDeletedCustomDeletedAtColumn(): void
    {
        $this->skipIfPgsqlNotConfigured();

        $connection = DB::connection('pgsql');
        $schema = $connection->getSchemaBuilder();

        $schema->create('knight_unique_where_test_pg', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->timestamp('removed_at')->nullable();
            $table->knightUniqueWhereNotDeleted('email', null, 'removed_at');
        });

        // Verify index uses custom deleted_at column
        $indexDef = $connection->selectOne(
            "SELECT indexdef FROM pg_indexes WHERE tablename = 'knight_unique_where_test_pg' AND indexname LIKE 'uk_%'"
        );
        $this->assertStringContainsString('removed_at', $indexDef->indexdef);
    }

    // ==================== knightIndexWhereNotDeleted Tests ====================

    public function testKnightIndexWhereNotDeleted(): void
    {
        $this->skipIfPgsqlNotConfigured();

        $connection = DB::connection('pgsql');
        $schema = $connection->getSchemaBuilder();

        $schema->create('knight_index_where_test_pg', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->timestamp('deleted_at')->nullable();
            $table->knightIndexWhereNotDeleted('user_id');
        });

        $this->assertTrue($schema->hasTable('knight_index_where_test_pg'));

        // Verify index exists
        $indexes = $connection->select(
            "SELECT indexname FROM pg_indexes WHERE tablename = 'knight_index_where_test_pg' AND indexname LIKE 'idx_%'"
        );
        $this->assertNotEmpty($indexes);
    }

    public function testKnightIndexWhereNotDeletedMultipleColumns(): void
    {
        $this->skipIfPgsqlNotConfigured();

        $connection = DB::connection('pgsql');
        $schema = $connection->getSchemaBuilder();

        $schema->create('knight_index_where_test_pg', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('status');
            $table->timestamp('deleted_at')->nullable();
            $table->knightIndexWhereNotDeleted(['user_id', 'status']);
        });

        $this->assertTrue($schema->hasTable('knight_index_where_test_pg'));
    }

    // ==================== Schema Alter Table Tests ====================

    public function testAlterTableAddKnightGinIndex(): void
    {
        $this->skipIfPgsqlNotConfigured();

        $connection = DB::connection('pgsql');
        $schema = $connection->getSchemaBuilder();

        // Create initial table
        $schema->create('knight_alter_test_pg', function (Blueprint $table) {
            $table->id();
            $table->jsonb('tags')->nullable();
        });

        $this->assertTrue($schema->hasTable('knight_alter_test_pg'));

        // Alter table to add GIN index
        $schema->table('knight_alter_test_pg', function (Blueprint $table) {
            $table->knightGin('tags');
        });

        // Verify index exists
        $indexes = $connection->select(
            "SELECT indexname FROM pg_indexes WHERE tablename = 'knight_alter_test_pg' AND indexname LIKE '%_gin'"
        );
        $this->assertNotEmpty($indexes);
    }

    public function testAlterTableAddKnightUniqueWhere(): void
    {
        $this->skipIfPgsqlNotConfigured();

        $connection = DB::connection('pgsql');
        $schema = $connection->getSchemaBuilder();

        // Create initial table
        $schema->create('knight_alter_test_pg', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->timestamp('deleted_at')->nullable();
        });

        $this->assertTrue($schema->hasTable('knight_alter_test_pg'));

        // Alter table to add conditional unique index
        $schema->table('knight_alter_test_pg', function (Blueprint $table) {
            $table->knightUniqueWhere('email', 'deleted_at IS NULL');
        });

        // Verify index exists
        $indexes = $connection->select(
            "SELECT indexname FROM pg_indexes WHERE tablename = 'knight_alter_test_pg' AND indexname LIKE 'uk_%'"
        );
        $this->assertNotEmpty($indexes);
    }

    public function testAlterTableAddKnightIndexWhere(): void
    {
        $this->skipIfPgsqlNotConfigured();

        $connection = DB::connection('pgsql');
        $schema = $connection->getSchemaBuilder();

        // Create initial table
        $schema->create('knight_alter_test_pg', function (Blueprint $table) {
            $table->id();
            $table->string('status');
        });

        $this->assertTrue($schema->hasTable('knight_alter_test_pg'));

        // Alter table to add conditional index
        $schema->table('knight_alter_test_pg', function (Blueprint $table) {
            $table->knightIndexWhere('status', "status = 'active'");
        });

        // Verify index exists
        $indexes = $connection->select(
            "SELECT indexname FROM pg_indexes WHERE tablename = 'knight_alter_test_pg' AND indexname LIKE 'idx_%'"
        );
        $this->assertNotEmpty($indexes);
    }

    public function testAlterTableAddMultipleKnightIndexes(): void
    {
        $this->skipIfPgsqlNotConfigured();
        $this->skipIfBtreeGinExtensionNotAvailable();

        $connection = DB::connection('pgsql');
        $schema = $connection->getSchemaBuilder();

        // Create initial table
        $schema->create('knight_alter_test_pg', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id');
            $table->string('email');
            $table->jsonb('tags')->nullable();
            $table->timestamp('deleted_at')->nullable();
        });

        $this->assertTrue($schema->hasTable('knight_alter_test_pg'));

        // Alter table to add multiple indexes
        $schema->table('knight_alter_test_pg', function (Blueprint $table) {
            $table->knightGin(['tenant_id', 'tags']);
            $table->knightUniqueWhereNotDeleted(['tenant_id', 'email']);
        });

        // Verify GIN index exists
        $ginIndexes = $connection->select(
            "SELECT indexname FROM pg_indexes WHERE tablename = 'knight_alter_test_pg' AND indexname LIKE '%_gin'"
        );
        $this->assertNotEmpty($ginIndexes);

        // Verify unique index exists
        $uniqueIndexes = $connection->select(
            "SELECT indexname FROM pg_indexes WHERE tablename = 'knight_alter_test_pg' AND indexname LIKE 'uk_%'"
        );
        $this->assertNotEmpty($uniqueIndexes);
    }

    public function testAlterTableAddColumnsAndIndex(): void
    {
        $this->skipIfPgsqlNotConfigured();

        $connection = DB::connection('pgsql');
        $schema = $connection->getSchemaBuilder();

        // Create initial table
        $schema->create('knight_alter_test_pg', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        $this->assertTrue($schema->hasTable('knight_alter_test_pg'));

        // Alter table to add columns and knightColumns
        $schema->table('knight_alter_test_pg', function (Blueprint $table) {
            $table->string('email')->nullable();
            $table->knightColumns();
        });

        // Verify columns were added
        $this->assertTrue($schema->hasColumn('knight_alter_test_pg', 'email'));
        $this->assertTrue($schema->hasColumn('knight_alter_test_pg', 'created_at'));
        $this->assertTrue($schema->hasColumn('knight_alter_test_pg', 'updated_at'));
        $this->assertTrue($schema->hasColumn('knight_alter_test_pg', 'deleted_at'));
        $this->assertTrue($schema->hasColumn('knight_alter_test_pg', 'ukey'));
        $this->assertTrue($schema->hasColumn('knight_alter_test_pg', 'data_version'));
    }

    // ==================== Helper Methods ====================

    protected function skipIfBtreeGinExtensionNotAvailable(): void
    {
        if (!$this->isPgsqlConfigured()) {
            return;
        }

        try {
            $connection = DB::connection('pgsql');
            $result = $connection->selectOne(
                "SELECT EXISTS(SELECT 1 FROM pg_extension WHERE extname = 'btree_gin') as exists"
            );

            if (!$result->exists) {
                // Try to create extension
                try {
                    $connection->statement('CREATE EXTENSION IF NOT EXISTS btree_gin');
                } catch (\Throwable $e) {
                    $this->markTestSkipped('btree_gin extension is not available: ' . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped('Could not check btree_gin extension: ' . $e->getMessage());
        }
    }
}
