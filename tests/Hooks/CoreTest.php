<?php

namespace StarCore\Hooks;

use CodeIgniter\Test\CIUnitTestCase;
use StarCore\Star\HyperHook;

class CoreTest extends CIUnitTestCase
{
    public function testCoreHooksConfiguration()
    {
        $hooks = require dirname(__DIR__, 2) . '/src/Hooks/Core.php';

        $this->assertIsArray($hooks);
        $this->assertArrayHasKey('modules:init', $hooks);
        $this->assertInstanceOf(HyperHook::class, $hooks['modules:init']);
        $this->assertEquals('core:modules:init', $hooks['modules:init']->getName());
    }
}
