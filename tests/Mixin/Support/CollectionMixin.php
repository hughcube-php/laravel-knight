<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/7/2
 * Time: 21:34
 */

namespace HughCube\Laravel\Knight\Tests\Mixin\Support;

use HughCube\Laravel\Knight\Ide\Support\KIdeCollection;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Support\Collection;

class CollectionMixin extends TestCase
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
        $this->assertSame([3, 4, 5], $collection->filterWithStop(function ($item) {
            return $item === 3;
        })->values()->toArray());

        /** @var KIdeCollection $collection */
        $collection = Collection::make([1, 2, 3, 4, 5]);
        $this->assertSame([], $collection->filterWithStop(function ($item) {
            return $item === 0;
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

    /**
     * 收集指定数组keys, 组合成一个新的collection
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
}
