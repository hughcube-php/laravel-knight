<?php

namespace HughCube\Laravel\Knight\Tests\Database;

use HughCube\Laravel\Knight\Database\MultipleModelUniqueKeyQueryProxy;
use HughCube\Laravel\Knight\Tests\TestCase;

class MultipleModelUniqueKeyQueryProxyTest extends TestCase
{
    public function testAddUniqueKeysMergesKeys()
    {
        $proxy = new MultipleModelUniqueKeyQueryProxy();

        $result = $proxy
            ->addUniqueKeys('User', ['email'])
            ->addUniqueKeys('User', ['phone']);

        $this->assertSame($proxy, $result);

        $keys = self::getProperty($proxy, 'uniqueKeys');

        $this->assertSame(['email', 'phone'], $keys['User']['keys']);
    }

    public function testAddUniqueKeysSeparatesModels()
    {
        $proxy = new MultipleModelUniqueKeyQueryProxy();

        $proxy->addUniqueKeys('User', ['email']);
        $proxy->addUniqueKeys('Order', ['number']);

        $keys = self::getProperty($proxy, 'uniqueKeys');

        $this->assertSame(['email'], $keys['User']['keys']);
        $this->assertSame(['number'], $keys['Order']['keys']);
    }

    public function testQueryReturnsNull()
    {
        $proxy = new MultipleModelUniqueKeyQueryProxy();

        $this->assertNull($proxy->query());
    }
}
