<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/8
 * Time: 16:18.
 */

namespace HughCube\Laravel\Knight\Tests\Support;

use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Support\Collection;

class CollectionTest extends TestCase
{
    public function testHasByCallable()
    {
        $items = [1, 2, 3, 4, 5, 6, 7];

        $collection = Collection::make($items);
        foreach ($items as $i) {
            $this->assertTrue($collection->hasByCallable(function ($item) use ($i) {
                return $i === $item;
            }));
        }
        foreach (range(10, 1000) as $i) {
            $this->assertFalse($collection->hasByCallable(function ($item) use ($i) {
                return $i === $item;
            }));
        }
    }

    public function testIsIndexed()
    {
        $this->assertTrue(Collection::make([1, 2, 3, 4, 5, 6, 7])->isIndexed());
        $this->assertFalse(Collection::make([1, 2, 3, 4, 5, 6, 9 => 7])->isIndexed());
        $this->assertTrue(Collection::make([1, 2, 3, 4, 5, 6, 9 => 7])->isIndexed(false));
    }

    public function testFilterWithStop()
    {
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
}
