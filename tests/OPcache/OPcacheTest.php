<?php

namespace HughCube\Laravel\Knight\Tests\OPcache;

use HughCube\Laravel\Knight\OPcache\OPcache;
use HughCube\Laravel\Knight\Tests\TestCase;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;

class OPcacheTest extends TestCase
{
    public function testGetPHPParserStmtClasses()
    {
        $stmts = [
            new Class_(new Identifier('RootClass')),
            new Namespace_(new Name('App\\Sub'), [
                new Class_(new Identifier('NamespacedClass')),
            ]),
        ];

        $classes = OPcache::i()->getPHPParserStmtClasses($stmts)->toArray();

        $this->assertSame(['RootClass', 'App\\Sub\\NamespacedClass'], $classes);
    }

    public function testGetUrlReplacesLocalhostWithAppUrl()
    {
        config(['app.url' => 'https://example.test:8443']);

        $url = OPcache::i()->getUrl('http://127.0.0.1/health', true);

        $this->assertSame('example.test', $url->getHost());
        $this->assertSame(8443, $url->getPort());
        $this->assertSame('/health', $url->getPath());
    }
}
