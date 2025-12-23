<?php

namespace StarCore\Config;

use CodeIgniter\Test\CIUnitTestCase;
use StarCore\Service\HyperHooks;

class ServicesTest extends CIUnitTestCase
{
    public function testHooksReturnsHyperHooksInstance()
    {
        $hooks = Services::hooks();
        $this->assertInstanceOf(HyperHooks::class, $hooks);
    }

    public function testHooksReturnsSharedInstance()
    {
        $hooks1 = Services::hooks();
        $hooks2 = Services::hooks();
        $this->assertSame($hooks1, $hooks2);
    }

    public function testHooksReturnsNewInstanceWhenSharedIsFalse()
    {
        $hooks1 = Services::hooks();
        $hooks2 = Services::hooks(false);
        $this->assertSame($hooks1, $hooks2);
        $this->assertInstanceOf(HyperHooks::class, $hooks2);
    }
}
