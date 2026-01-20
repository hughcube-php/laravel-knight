<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/7/2
 * Time: 21:34.
 */

namespace HughCube\Laravel\Knight\Tests\Mixin\Support;

use HughCube\Laravel\Knight\Ide\Support\KIdeCollection;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Support\Collection;

class CollectionMixinTest extends TestCase
{
    public function testHasByCallable()
    {
        /** @var KIdeCollection $collection */
        $collection = Collection::make([1, 2, 3, 4, 5]);

        $this->assertTrue($collection->hasByCallable(function ($item) {
            return 4 === $item;
        }));

        $this->assertFalse($collection->hasByCallable(function ($item) {
            return 10 === $item;
        }));
    }

    public function testIsIndexed()
    {
        /** @var KIdeCollection $collection */
        $collection = Collection::make([1, 2, 3, 4, 5]);
        $this->assertTrue($collection->isIndexed());
        $this->assertTrue($collection->isIndexed(false));

        /** @var KIdeCollection $collection */
        $collection = Collection::make([10 => 1, 11 => 2, 3, 4, 5]);
        $this->assertFalse($collection->isIndexed());
        $this->assertTrue($collection->isIndexed(false));

        /** @var KIdeCollection $collection */
        $collection = Collection::make(['10' => 1, 2, 3, 4, 5]);
        $this->assertFalse($collection->isIndexed());
        $this->assertTrue($collection->isIndexed(false));

        /** @var KIdeCollection $collection */
        $collection = Collection::make(['a' => 1, 2, 3, 4, 5]);
        $this->assertFalse($collection->isIndexed());
        $this->assertFalse($collection->isIndexed(false));
    }

    public function testFilterWithStop()
    {
        /** @var KIdeCollection $collection */
        $collection = Collection::make([1, 2, 3, 4, 5]);
        $this->assertSame(
            [3, 4, 5],
            $collection
                ->filterWithStop(function ($item) {
                    return $item === 3;
                }, true)
                ->values()
                ->toArray()
        );

        /** @var KIdeCollection $collection */
        $collection = Collection::make([1, 2, 3, 4, 5]);
        $this->assertSame(
            [],
            $collection
                ->filterWithStop(function ($item) {
                    return $item === 0;
                }, true)
                ->values()
                ->toArray()
        );

        $this->assertSame([5, 6, 7], Collection::make([1, 2, 3, 4, 5, 6, 7])->filterWithStop(function ($item) {
            return 4 === $item;
        })->values()->toArray());

        $this->assertSame([6, 7], Collection::make([1, 2, 3, 4, 5, 6, 7])->filterWithStop(function ($item) {
            return 5 === $item;
        })->values()->toArray());

        $this->assertSame([7], Collection::make([1, 2, 3, 4, 5, 6, 7])->filterWithStop(function ($item) {
            return 6 === $item;
        })->values()->toArray());

        $this->assertSame([], Collection::make([1, 2, 3, 4, 5, 6, 7])->filterWithStop(function ($item) {
            return 7 === $item;
        })->values()->toArray());
    }

    public function testPluckAndMergeSetColumn()
    {
        /** @var KIdeCollection $collection */
        $collection = Collection::make([
            ['a' => '1,2,9', 'b' => 0],
            ['a' => '3,4,8,', 'b' => 0],
            ['a' => '5,8,1', 'b' => 0],
        ]);

        $this->assertSame(
            array_map('strval', [1, 2, 3, 4, 5, 8, 9]),
            $collection->pluckAndMergeSetColumn('a')->toArray()
        );
    }

    public function testPluckAndMergeArrayColumn()
    {
        /** @var KIdeCollection $collection */
        $collection = Collection::make([
            ['a' => [1, 2, 3, 4]],
            ['a' => [1, 2, 4]],
        ]);

        $this->assertSame(
            [1, 2, 3, 4, 1, 2, 4],
            $collection->pluckAndMergeArrayColumn('a')->toArray()
        );
    }

    public function testPluckAndMergeArrayColumnHandlesMissingOrNonArray()
    {
        /** @var KIdeCollection $collection */
        $collection = Collection::make([
            ['a' => [1, 2]],
            ['b' => [3]],
            ['a' => '3'],
            ['a' => null],
            ['a' => Collection::make([4, 5])],
        ]);

        $this->assertSame(
            [1, 2, '3', 4, 5],
            $collection->pluckAndMergeArrayColumn('a')->toArray()
        );
    }

    /**
     * 收集指定数组keys, 组合成一个新的collection.
     */
    public function testOnlyArrayKeys()
    {
        /** @var KIdeCollection $collection */
        $collection = Collection::make([1, 'a' => 2, 'b' => 3, 4, 5]);

        $this->assertSame(['a' => 2, 'b' => 3], $collection->onlyArrayKeys(['a', 'b', 'c', '$'])->toArray());
    }

    public function testOnlyColumnValues()
    {
        /** @var KIdeCollection $collection */
        $collection = Collection::make([
            ['a' => '1,2,9', 'b' => 0.01],
            ['a' => '3,4,8,', 'b' => 'a'],
            ['a' => '5,8,1', 'b' => '1'],
        ]);

        $this->assertSame(
            [
                1 => ['a' => '3,4,8,', 'b' => 'a'],
                2 => ['a' => '5,8,1', 'b' => '1'],
            ],
            $collection->onlyColumnValues(['a', 1], 'b')->toArray()
        );

        $this->assertSame(
            [
                1 => ['a' => '3,4,8,', 'b' => 'a'],
            ],
            $collection->onlyColumnValues(['a', 1], 'b', true)->toArray()
        );
    }

    public function testHasAnyAndAllValues()
    {
        /** @var KIdeCollection $collection */
        $collection = Collection::make([1, 2, 3]);

        $this->assertTrue($collection->hasAnyValues([3, 9]));
        $this->assertFalse($collection->hasAnyValues([9]));
        $this->assertTrue($collection->hasAnyValues([], true));

        $this->assertTrue($collection->hasAllValues([1, 2, 3]));
        $this->assertFalse($collection->hasAllValues([1, 4]));
        $this->assertTrue($collection->hasAllValues([], true));

        $this->assertFalse(Collection::make()->hasAnyValues([1]));
        $this->assertFalse(Collection::make()->hasAllValues([1]));
    }

    public function testHasValue()
    {
        /** @var KIdeCollection $collection */
        $collection = Collection::make([1, 2, 3]);

        $this->assertTrue($collection->hasValue('2'));
        $this->assertFalse($collection->hasValue('2', true));
    }

    public function testAfterItems()
    {
        /** @var KIdeCollection $collection */
        $collection = Collection::make([1, 2, 3, 2, 4]);

        $this->assertSame([3, 2, 4], $collection->afterFirstItems(2)->values()->toArray());
        $this->assertSame([2, 3, 2, 4], $collection->afterFirstItems(2, true)->values()->toArray());
        $this->assertSame([3, 2, 4], $collection->afterLastItems(2)->values()->toArray());
        $this->assertSame([2, 3, 2, 4], $collection->afterLastItems(2, true)->values()->toArray());
    }

    public function testWhenFilter()
    {
        /** @var KIdeCollection $collection */
        $collection = Collection::make([1, 2, 3]);

        $this->assertSame([2, 3], $collection->whenFilter(true, function ($item) {
            return $item > 1;
        })->values()->toArray());

        $this->assertSame([1, 2, 3], $collection->whenFilter(false, function () {
            return false;
        })->values()->toArray());
    }

    public function testMapIntAndString()
    {
        /** @var KIdeCollection $collection */
        $collection = Collection::make(['1', 2, '3']);

        $this->assertSame([1, 2, 3], $collection->mapInt()->values()->toArray());

        /** @var KIdeCollection $collection */
        $collection = Collection::make([1, 2]);

        $this->assertSame(['1', '2'], $collection->mapString()->values()->toArray());
    }

    public function testSplitHelpers()
    {
        /** @var KIdeCollection $collection */
        $collection = Collection::make();

        $this->assertSame(['a', 'b', 'c'], $collection->explode(',', 'a,b,c')->toArray());
        $this->assertSame(['a', 'b', 'c'], $collection->splitWhitespace('a  b   c')->toArray());
        $this->assertSame(['a', 'b', 'c'], $collection->splitComma('a,b,c')->toArray());
        $this->assertSame(['a', 'b', 'c'], $collection->splitSlash('a/b/c')->toArray());
    }
}
