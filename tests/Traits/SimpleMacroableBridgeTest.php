<?php

namespace HughCube\Laravel\Knight\Tests\Traits;

use HughCube\Laravel\Knight\Tests\TestCase;
use HughCube\Laravel\Knight\Traits\SimpleMacroableBridge;
use Illuminate\Support\Traits\Macroable;

class SimpleMacroableBridgeTest extends TestCase
{
    public function testMixinAddsMacros()
    {
        SimpleMacroBridge::mixin(SimpleMacroTargetOne::class);

        $this->assertSame('Hello Bob', SimpleMacroTargetOne::greet('Bob'));
        $this->assertSame(3, SimpleMacroTargetOne::sum(1, 2));
    }

    public function testMixinReplaceControlsOverrides()
    {
        SimpleMacroTargetTwo::macro('greet', function ($name) {
            return 'Original '.$name;
        });

        SimpleMacroBridge::mixin(SimpleMacroTargetTwo::class, false);
        $this->assertSame('Original Bob', SimpleMacroTargetTwo::greet('Bob'));

        SimpleMacroBridge::mixin(SimpleMacroTargetTwo::class, true);
        $this->assertSame('Hello Bob', SimpleMacroTargetTwo::greet('Bob'));
    }

    public function testMixinWithNoMacrosDoesNothing()
    {
        EmptyMacroTarget::macro('greet', function ($name) {
            return 'Original '.$name;
        });

        EmptyMacroBridge::mixin(EmptyMacroTarget::class);

        $this->assertTrue(EmptyMacroTarget::hasMacro('greet'));
        $this->assertSame([], $this->callMethod(EmptyMacroBridge::class, 'getMacros'));
    }
}

class SimpleMacroBridge
{
    use SimpleMacroableBridge;

    protected static function getMacros(): array
    {
        return ['greet', 'sum'];
    }

    public static function greet()
    {
        return function ($name) {
            return 'Hello '.$name;
        };
    }

    public static function sum()
    {
        return function ($left, $right) {
            return $left + $right;
        };
    }
}

class SimpleMacroTargetOne
{
    use Macroable;
}

class SimpleMacroTargetTwo
{
    use Macroable;
}

class EmptyMacroBridge
{
    use SimpleMacroableBridge;
}

class EmptyMacroTarget
{
    use Macroable;
}
