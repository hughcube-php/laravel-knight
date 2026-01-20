<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2024/1/16
 * Time: 10:00.
 */

namespace HughCube\Laravel\Knight\Tests\Mixin\Database\Query;

use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Database\PostgresConnection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * PostgreSQL Array Query Tests.
 *
 * Tests for whereArrayContains, whereArrayContainedBy, whereArrayOverlaps
 * and their variants (or, not, orNot).
 */
class PgArrayTest extends TestCase
{
    protected static bool $arrayTablePrepared = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (!$this->isPgsqlConfigured() || self::$arrayTablePrepared) {
            return;
        }

        $this->setUpArrayTable();
        self::$arrayTablePrepared = true;
    }

    protected function getTestQuery(bool $requiresConnection = true)
    {
        if ($requiresConnection) {
            $this->skipIfPgsqlNotConfigured();

            return DB::connection('pgsql')->table('knight_array_test');
        }

        return $this->getSqlOnlyQuery();
    }

    protected function getSqlOnlyQuery()
    {
        $connection = new PostgresConnection(
            function () {
                throw new \RuntimeException('PDO connection is not available for SQL-only tests.');
            },
            '',
            '',
            ['driver' => 'pgsql']
        );

        return $connection->table('knight_array_test');
    }

    protected function parsePgArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value === null || $value === '{}' || $value === '') {
            return [];
        }

        $items = str_getcsv(substr($value, 1, -1), ',', '"', '\\');

        return array_map(function ($item) {
            return preg_replace('/\\\\(.)/', '$1', $item);
        }, $items);
    }

    protected function setUpArrayTable(): void
    {
        $connection = DB::connection('pgsql');

        $connection->statement('DROP TABLE IF EXISTS knight_array_test');
        $connection->statement(
            'CREATE TABLE knight_array_test (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                tags TEXT[] NOT NULL,
                scores INTEGER[] NOT NULL,
                prices DOUBLE PRECISION[] NOT NULL,
                flags BOOLEAN[] NOT NULL
            )'
        );

        foreach ($this->seedRows() as $row) {
            $this->insertRow($connection, $row);
        }
    }

    protected function seedRows(): array
    {
        return [
            [
                'id'     => 1,
                'name'   => 'record1',
                'tags'   => ['php', 'laravel', 'mysql'],
                'scores' => [80, 90],
                'prices' => [1.5, 2.5],
                'flags'  => ['true', 'false'],
            ],
            [
                'id'     => 2,
                'name'   => 'record2',
                'tags'   => ['java', 'spring'],
                'scores' => [95, 100],
                'prices' => [3.5],
                'flags'  => ['true'],
            ],
            [
                'id'     => 3,
                'name'   => 'record3',
                'tags'   => ['php', 'symfony', 'django'],
                'scores' => [70, 85],
                'prices' => [4.0],
                'flags'  => ['false'],
            ],
            [
                'id'     => 4,
                'name'   => 'record4',
                'tags'   => ['php', 'laravel'],
                'scores' => [1, 2, 3],
                'prices' => [1.0, 2.0],
                'flags'  => ['true', 'false'],
            ],
        ];
    }

    protected function insertRow($connection, array $row): void
    {
        [$tagsSql, $tagsBindings] = $this->buildArraySql($row['tags']);
        [$scoresSql, $scoresBindings] = $this->buildArraySql($row['scores']);
        [$pricesSql, $pricesBindings] = $this->buildArraySql($row['prices']);
        [$flagsSql, $flagsBindings] = $this->buildArraySql($row['flags']);

        $sql = sprintf(
            'INSERT INTO knight_array_test (id, name, tags, scores, prices, flags)
             VALUES (?, ?, %s::text[], %s::integer[], %s::double precision[], %s::boolean[])',
            $tagsSql,
            $scoresSql,
            $pricesSql,
            $flagsSql
        );

        $bindings = array_merge(
            [$row['id'], $row['name']],
            $tagsBindings,
            $scoresBindings,
            $pricesBindings,
            $flagsBindings
        );

        $connection->statement($sql, $bindings);
    }

    protected function buildArraySql(array $values): array
    {
        if ($values === []) {
            return ['ARRAY[]', []];
        }

        $placeholders = implode(', ', array_fill(0, count($values), '?'));

        return ['ARRAY['.$placeholders.']', array_values($values)];
    }

    // ==================== whereArrayContains (@>) ====================

    public function testWhereArrayContains()
    {
        // tags contains ['php', 'laravel']
        $results = Collection::make(
            $this->getTestQuery()
                ->whereArrayContains('tags', ['php', 'laravel'])
                ->get()
        );

        $results->each(function ($item) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertTrue(in_array('php', $tags));
            $this->assertTrue(in_array('laravel', $tags));
        });
    }

    public function testWhereArrayContainsSingle()
    {
        // tags contains ['php']
        $results = Collection::make(
            $this->getTestQuery()
                ->whereArrayContains('tags', ['php'])
                ->get()
        );

        $results->each(function ($item) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertTrue(in_array('php', $tags));
        });
    }

    public function testWhereArrayContainsInteger()
    {
        // scores contains [80, 90]
        $results = Collection::make(
            $this->getTestQuery()
                ->whereArrayContains('scores', [80, 90])
                ->get()
        );

        $results->each(function ($item) {
            $scores = $this->parsePgArray($item->scores);
            $this->assertTrue(in_array('80', $scores));
            $this->assertTrue(in_array('90', $scores));
        });
    }

    public function testOrWhereArrayContains()
    {
        // name = 'record3' OR tags contains ['symfony']
        $results = Collection::make(
            $this->getTestQuery()
                ->where('name', 'record3')
                ->orWhereArrayContains('tags', ['symfony'])
                ->get()
        );

        $results->each(function ($item) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertTrue($item->name === 'record3' || in_array('symfony', $tags));
        });
    }

    public function testWhereNotArrayContains()
    {
        // tags NOT contains ['php']
        $results = Collection::make(
            $this->getTestQuery()
                ->whereNotArrayContains('tags', ['php'])
                ->get()
        );

        $results->each(function ($item) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertFalse(in_array('php', $tags));
        });
    }

    public function testOrWhereNotArrayContains()
    {
        // name = 'record1' OR tags NOT contains ['java']
        $results = Collection::make(
            $this->getTestQuery()
                ->where('name', 'record1')
                ->orWhereNotArrayContains('tags', ['java'])
                ->get()
        );

        $results->each(function ($item) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertTrue($item->name === 'record1' || !in_array('java', $tags));
        });
    }

    // ==================== whereArrayContainedBy (<@) ====================

    public function testWhereArrayContainedBy()
    {
        // tags is contained by ['php', 'laravel']
        $allowed = ['php', 'laravel'];
        $results = Collection::make(
            $this->getTestQuery()
                ->whereArrayContainedBy('tags', $allowed)
                ->get()
        );

        $results->each(function ($item) use ($allowed) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertSame([], array_diff($tags, $allowed));
        });
    }

    public function testWhereArrayContainedByLarger()
    {
        // tags is contained by ['php', 'laravel', 'mysql', 'symfony']
        $allowed = ['php', 'laravel', 'mysql', 'symfony'];
        $results = Collection::make(
            $this->getTestQuery()
                ->whereArrayContainedBy('tags', $allowed)
                ->get()
        );

        $results->each(function ($item) use ($allowed) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertSame([], array_diff($tags, $allowed));
        });
    }

    public function testOrWhereArrayContainedBy()
    {
        // name = 'record3' OR tags contained by ['php', 'laravel']
        $allowed = ['php', 'laravel'];
        $results = Collection::make(
            $this->getTestQuery()
                ->where('name', 'record3')
                ->orWhereArrayContainedBy('tags', $allowed)
                ->get()
        );

        $results->each(function ($item) use ($allowed) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertTrue($item->name === 'record3' || [] === array_diff($tags, $allowed));
        });
    }

    public function testWhereNotArrayContainedBy()
    {
        // tags NOT contained by ['php', 'laravel', 'mysql', 'symfony']
        $allowed = ['php', 'laravel', 'mysql', 'symfony'];
        $results = Collection::make(
            $this->getTestQuery()
                ->whereNotArrayContainedBy('tags', $allowed)
                ->get()
        );

        $results->each(function ($item) use ($allowed) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertNotSame([], array_diff($tags, $allowed));
        });
    }

    public function testOrWhereNotArrayContainedBy()
    {
        // name = 'record1' OR tags NOT contained by ['java', 'spring']
        $allowed = ['java', 'spring'];
        $results = Collection::make(
            $this->getTestQuery()
                ->where('name', 'record1')
                ->orWhereNotArrayContainedBy('tags', $allowed)
                ->get()
        );

        $results->each(function ($item) use ($allowed) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertTrue($item->name === 'record1' || [] !== array_diff($tags, $allowed));
        });
    }

    // ==================== whereArrayOverlaps (&&) ====================

    public function testWhereArrayOverlaps()
    {
        // tags overlaps ['laravel', 'django']
        $overlaps = ['laravel', 'django'];
        $results = Collection::make(
            $this->getTestQuery()
                ->whereArrayOverlaps('tags', $overlaps)
                ->get()
        );

        $results->each(function ($item) use ($overlaps) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertNotEmpty(array_intersect($tags, $overlaps));
        });
    }

    public function testWhereArrayOverlapsSingle()
    {
        // tags overlaps ['java']
        $results = Collection::make(
            $this->getTestQuery()
                ->whereArrayOverlaps('tags', ['java'])
                ->get()
        );

        $results->each(function ($item) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertTrue(in_array('java', $tags));
        });
    }

    public function testWhereArrayOverlapsInteger()
    {
        // scores overlaps [95, 100]
        $overlaps = ['95', '100'];
        $results = Collection::make(
            $this->getTestQuery()
                ->whereArrayOverlaps('scores', [95, 100])
                ->get()
        );

        $results->each(function ($item) use ($overlaps) {
            $scores = $this->parsePgArray($item->scores);
            $this->assertNotEmpty(array_intersect($scores, $overlaps));
        });
    }

    public function testOrWhereArrayOverlaps()
    {
        // name = 'record2' OR tags overlaps ['java']
        $overlaps = ['java'];
        $results = Collection::make(
            $this->getTestQuery()
                ->where('name', 'record2')
                ->orWhereArrayOverlaps('tags', $overlaps)
                ->get()
        );

        $results->each(function ($item) use ($overlaps) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertTrue($item->name === 'record2' || !empty(array_intersect($tags, $overlaps)));
        });
    }

    public function testWhereNotArrayOverlaps()
    {
        // tags NOT overlaps ['php']
        $results = Collection::make(
            $this->getTestQuery()
                ->whereNotArrayOverlaps('tags', ['php'])
                ->get()
        );

        $results->each(function ($item) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertFalse(in_array('php', $tags));
        });
    }

    public function testOrWhereNotArrayOverlaps()
    {
        // name = 'record1' OR tags NOT overlaps ['java', 'python']
        $blocked = ['java', 'python'];
        $results = Collection::make(
            $this->getTestQuery()
                ->where('name', 'record1')
                ->orWhereNotArrayOverlaps('tags', $blocked)
                ->get()
        );

        $results->each(function ($item) use ($blocked) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertTrue($item->name === 'record1' || empty(array_intersect($tags, $blocked)));
        });
    }

    // ==================== Combined queries ====================

    public function testCombinedArrayQueries()
    {
        // tags contains ['php'] AND tags overlaps ['laravel']
        $results = Collection::make(
            $this->getTestQuery()
                ->whereArrayContains('tags', ['php'])
                ->whereArrayOverlaps('tags', ['laravel'])
                ->get()
        );

        $results->each(function ($item) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertTrue(in_array('php', $tags));
            $this->assertTrue(in_array('laravel', $tags));
        });
    }

    public function testArrayWithOtherConditions()
    {
        // id > 2 AND tags overlaps ['php']
        $results = Collection::make(
            $this->getTestQuery()
                ->where('id', '>', 2)
                ->whereArrayOverlaps('tags', ['php'])
                ->get()
        );

        $results->each(function ($item) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertGreaterThan(2, $item->id);
            $this->assertTrue(in_array('php', $tags));
        });
    }

    // ==================== SQL generation tests ====================

    public function testSqlGeneration()
    {
        $query = $this->getTestQuery(false)
            ->whereArrayContains('tags', ['php', 'laravel']);

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $this->assertStringContainsString('@>', $sql);
        $this->assertStringContainsString('ARRAY[?, ?]::text[]', $sql);
        $this->assertEquals(['php', 'laravel'], $bindings);
    }

    public function testSqlGenerationInteger()
    {
        $query = $this->getTestQuery(false)
            ->whereArrayContains('scores', [80, 90]);

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $this->assertStringContainsString('@>', $sql);
        $this->assertStringContainsString('ARRAY[?, ?]::integer[]', $sql);
        $this->assertEquals([80, 90], $bindings);
    }

    public function testSqlGenerationCustomType()
    {
        $query = $this->getTestQuery(false)
            ->whereArrayContains('scores', [80, 90], 'and', false, 'bigint');

        $sql = $query->toSql();

        $this->assertStringContainsString('ARRAY[?, ?]::bigint[]', $sql);
    }

    public function testSqlGenerationCustomTypeForArrayContainsVariants()
    {
        $orSql = $this->getTestQuery(false)
            ->where('id', 1)
            ->orWhereArrayContains('tags', ['php'], 'uuid')
            ->toSql();

        $this->assertStringContainsString('ARRAY[?]::uuid[]', $orSql);

        $notSql = $this->getTestQuery(false)
            ->whereNotArrayContains('tags', ['php'], 'and', 'uuid')
            ->toSql();

        $this->assertStringContainsString('ARRAY[?]::uuid[]', $notSql);

        $orNotSql = $this->getTestQuery(false)
            ->where('id', 1)
            ->orWhereNotArrayContains('tags', ['php'], 'uuid')
            ->toSql();

        $this->assertStringContainsString('ARRAY[?]::uuid[]', $orNotSql);
    }

    public function testSqlGenerationCustomTypeForArrayContainedByVariants()
    {
        $orSql = $this->getTestQuery(false)
            ->where('id', 1)
            ->orWhereArrayContainedBy('scores', [1, 2], 'bigint')
            ->toSql();

        $this->assertStringContainsString('ARRAY[?, ?]::bigint[]', $orSql);

        $notSql = $this->getTestQuery(false)
            ->whereNotArrayContainedBy('scores', [1, 2], 'and', 'bigint')
            ->toSql();

        $this->assertStringContainsString('ARRAY[?, ?]::bigint[]', $notSql);

        $orNotSql = $this->getTestQuery(false)
            ->where('id', 1)
            ->orWhereNotArrayContainedBy('scores', [1, 2], 'bigint')
            ->toSql();

        $this->assertStringContainsString('ARRAY[?, ?]::bigint[]', $orNotSql);
    }

    public function testSqlGenerationCustomTypeForArrayOverlapsVariants()
    {
        $orSql = $this->getTestQuery(false)
            ->where('id', 1)
            ->orWhereArrayOverlaps('tags', ['php'], 'citext')
            ->toSql();

        $this->assertStringContainsString('ARRAY[?]::citext[]', $orSql);

        $notSql = $this->getTestQuery(false)
            ->whereNotArrayOverlaps('tags', ['php'], 'and', 'citext')
            ->toSql();

        $this->assertStringContainsString('ARRAY[?]::citext[]', $notSql);

        $orNotSql = $this->getTestQuery(false)
            ->where('id', 1)
            ->orWhereNotArrayOverlaps('tags', ['php'], 'citext')
            ->toSql();

        $this->assertStringContainsString('ARRAY[?]::citext[]', $orNotSql);
    }

    // ==================== Empty array tests ====================

    public function testEmptyArrayContains()
    {
        // Empty array should match all (any array contains empty array)
        $allIds = $this->getTestQuery()
            ->pluck('id')
            ->toArray();

        $results = $this->getTestQuery()
            ->whereArrayContains('tags', [])
            ->pluck('id')
            ->toArray();

        $this->assertSame([], array_diff($allIds, $results));
        $this->assertSame([], array_diff($results, $allIds));
    }

    public function testEmptyArrayOverlaps()
    {
        // Empty array overlaps nothing
        $results = Collection::make(
            $this->getTestQuery()
                ->whereArrayOverlaps('tags', [])
                ->get()
        );

        $results->each(function ($item) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertNotEmpty(array_intersect($tags, []));
        });
    }

    // ==================== Special characters tests ====================

    public function testArrayWithCommas()
    {
        // Test values containing commas: '1,2,4', 'a,b,c'
        $results = Collection::make(
            $this->getTestQuery()
                ->whereArrayContains('tags', ['1,2,4'])
                ->get()
        );

        $results->each(function ($item) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertTrue(in_array('1,2,4', $tags));
        });
    }

    public function testArrayWithMultipleCommaValues()
    {
        // Test multiple values with commas
        $results = Collection::make(
            $this->getTestQuery()
                ->whereArrayContains('tags', ['1,2,4', 'a,b,c'])
                ->get()
        );

        $results->each(function ($item) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertTrue(in_array('1,2,4', $tags));
            $this->assertTrue(in_array('a,b,c', $tags));
        });
    }

    public function testArrayOverlapsWithCommas()
    {
        // Test overlaps with comma-containing values
        $overlaps = ['a,b,c', 'nonexistent'];
        $results = Collection::make(
            $this->getTestQuery()
                ->whereArrayOverlaps('tags', $overlaps)
                ->get()
        );

        $results->each(function ($item) use ($overlaps) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertNotEmpty(array_intersect($tags, $overlaps));
        });
    }

    public function testArrayWithSpaces()
    {
        // Test values with spaces
        $results = Collection::make(
            $this->getTestQuery()
                ->whereArrayOverlaps('tags', ['hello world'])
                ->get()
        );

        $results->each(function ($item) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertTrue(in_array('hello world', $tags));
        });
    }

    public function testArrayWithQuotes()
    {
        // Test values with double quotes
        $results = Collection::make(
            $this->getTestQuery()
                ->whereArrayOverlaps('tags', ['has"quote'])
                ->get()
        );

        $results->each(function ($item) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertTrue(in_array('has"quote', $tags));
        });
    }

    public function testArrayWithSingleQuotes()
    {
        // Test values with single quotes
        $results = Collection::make(
            $this->getTestQuery()
                ->whereArrayOverlaps('tags', ["has'single"])
                ->get()
        );

        $results->each(function ($item) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertTrue(in_array("has'single", $tags));
        });
    }

    public function testArrayWithBackslash()
    {
        // Test values with backslashes
        $results = Collection::make(
            $this->getTestQuery()
                ->whereArrayOverlaps('tags', ['has\\backslash'])
                ->get()
        );

        $results->each(function ($item) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertTrue(in_array('has\\backslash', $tags));
        });
    }

    public function testArrayWithBraces()
    {
        // Test values with curly braces
        $results = Collection::make(
            $this->getTestQuery()
                ->whereArrayOverlaps('tags', ['has{brace}'])
                ->get()
        );

        $results->each(function ($item) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertTrue(in_array('has{brace}', $tags));
        });
    }

    public function testArrayWithBrackets()
    {
        // Test values with square brackets
        $results = Collection::make(
            $this->getTestQuery()
                ->whereArrayOverlaps('tags', ['has[bracket]'])
                ->get()
        );

        $results->each(function ($item) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertTrue(in_array('has[bracket]', $tags));
        });
    }

    // ==================== SQL Injection prevention tests ====================

    public function testSqlInjectionPrevention()
    {
        // Test that SQL injection is prevented
        $maliciousInput = "test'; DROP TABLE users; --";

        $results = Collection::make(
            $this->getTestQuery()
                ->whereArrayOverlaps('tags', [$maliciousInput])
                ->get()
        );

        $results->each(function ($item) use ($maliciousInput) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertTrue(in_array($maliciousInput, $tags));
        });

        // Verify the table still exists (injection was prevented)
        $count = $this->getTestQuery()->count();
        $this->assertIsInt($count);
    }

    public function testSqlInjectionInContains()
    {
        // Another injection attempt via whereArrayContains
        $maliciousInput = "'); DELETE FROM knight_array_test; --";

        // This should not cause any SQL errors or deletions
        $results = Collection::make(
            $this->getTestQuery()
                ->whereArrayContains('tags', [$maliciousInput])
                ->get()
        );

        $results->each(function ($item) use ($maliciousInput) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertTrue(in_array($maliciousInput, $tags));
        });

        // Verify data is still intact
        $count = $this->getTestQuery()->count();
        $this->assertIsInt($count);
    }

    public function testSqlInjectionWithMultipleValues()
    {
        // Injection attempt with multiple values
        $overlaps = [
            'normal',
            "test' OR '1'='1",
            'test"; DROP TABLE users; --',
        ];

        $results = Collection::make(
            $this->getTestQuery()
                ->whereArrayOverlaps('tags', $overlaps)
                ->get()
        );

        $results->each(function ($item) use ($overlaps) {
            $tags = $this->parsePgArray($item->tags);
            $this->assertNotEmpty(array_intersect($tags, $overlaps));
        });
    }

    public function testBindingsAreProperlyEscaped()
    {
        $query = $this->getTestQuery(false)
            ->whereArrayContains('tags', ["test'; DROP TABLE users; --", 'normal']);

        $bindings = $query->getBindings();

        // Each value should be a separate binding
        $this->assertCount(2, $bindings);
        $this->assertEquals("test'; DROP TABLE users; --", $bindings[0]);
        $this->assertEquals('normal', $bindings[1]);
    }

    // ==================== Type coercion tests ====================

    public function testIntegerArrayTypeInference()
    {
        $query = $this->getTestQuery(false)
            ->whereArrayContains('scores', [1, 2, 3]);

        $sql = $query->toSql();

        // Should infer integer type
        $this->assertStringContainsString('::integer[]', $sql);
    }

    public function testFloatArrayTypeInference()
    {
        $query = $this->getTestQuery(false)
            ->whereArrayContains('prices', [1.5, 2.5]);

        $sql = $query->toSql();

        // Should infer double precision type
        $this->assertStringContainsString('::double precision[]', $sql);
    }

    public function testBooleanArrayTypeInference()
    {
        $query = $this->getTestQuery(false)
            ->whereArrayContains('flags', [true, false]);

        $sql = $query->toSql();

        // Should infer boolean type
        $this->assertStringContainsString('::boolean[]', $sql);
    }

    public function testStringArrayTypeInference()
    {
        $query = $this->getTestQuery(false)
            ->whereArrayContains('tags', ['php', 'laravel']);

        $sql = $query->toSql();

        // Should infer text type
        $this->assertStringContainsString('::text[]', $sql);
    }
}
