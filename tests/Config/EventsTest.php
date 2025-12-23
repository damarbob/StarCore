<?php

namespace StarCore\Config;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Config\Factories;
use CodeIgniter\Autoloader\Autoloader;
use Config\Services;
use CodeIgniter\Events\Events as CIEvents;

class EventsTest extends CIUnitTestCase
{
    private $tempDir;
    private $modulesDir;
    private $devModulesDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'StarEventsTest_' . bin2hex(random_bytes(4));
        mkdir($this->tempDir);

        $this->modulesDir = $this->tempDir . DIRECTORY_SEPARATOR . 'Modules';
        $this->devModulesDir = $this->tempDir . DIRECTORY_SEPARATOR . 'DevModules';

        mkdir($this->modulesDir);
        mkdir($this->devModulesDir);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Factories::reset();
        $this->deleteDirectory($this->tempDir);
        CIEvents::removeAllListeners('pre_system');
    }

    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->deleteDirectory("$dir/$file") : unlink("$dir/$file");
        }
        rmdir($dir);
    }

    public function testEventsBootstrapSafeMode()
    {
        // Mock Config
        $config = new Star();
        $config->safeMode = true;
        Factories::injectMock('config', 'StarCore\Config\Star', $config);

        // Mock Autoloader - should NOT be called
        $autoloader = $this->createMock(Autoloader::class);
        $autoloader->expects($this->never())->method('addNamespace');
        $autoloader->method('getNamespace')->willReturn([]);
        Services::injectMock('autoloader', $autoloader);

        // Include the file
        include dirname(__DIR__, 2) . '/src/Config/Events.php';
    }

    public function testEventsBootstrapLoadsModules()
    {
        // Setup Module Structure
        // ModuleA: Standard module with init.php
        mkdir($this->modulesDir . '/ModuleA');
        file_put_contents($this->modulesDir . '/ModuleA/init.php', '<?php define("MODULE_A_INIT", true);');

        // ModuleB: Standard module with Events.php
        mkdir($this->modulesDir . '/ModuleB/Config', 0777, true);
        file_put_contents($this->modulesDir . '/ModuleB/Config/Events.php', '<?php define("MODULE_B_EVENTS", true);');

        // DevModuleC: Dev module
        mkdir($this->devModulesDir . '/DevModuleC');

        // Mock Config
        // We use a Mock Object for Config to control getActiveModules completely.

        // Since we can't easily set protected properties on the real config object if dependencies aren't accessible,
        // we'll rely on the fact that Star config allows public property setting or we use reflection or a mock.
        // Better to use a Mock Object for Config to control getActiveModules completely.
        $mockConfig = $this->createMock(Star::class);
        $mockConfig->modulesPath = $this->modulesDir . DIRECTORY_SEPARATOR;
        $mockConfig->devModulesPath = $this->devModulesDir . DIRECTORY_SEPARATOR;
        $mockConfig->safeMode = false;
        $mockConfig->log = false; // Disable logging to avoid clutter

        $mockConfig->method('getActiveModules')->willReturn(['ModuleA', 'ModuleB']);
        $mockConfig->method('getActiveDevModules')->willReturn(['DevModuleC']);

        Factories::injectMock('config', 'StarCore\Config\Star', $mockConfig);

        // Mock Autoloader
        $autoloader = $this->createMock(Autoloader::class);
        $autoloader->method('getNamespace')->willReturn([]); // No existing namespaces

        // Expect namespaces to be registered
        $capturedCalls = [];
        $autoloader->expects($this->any())
            ->method('addNamespace')
            ->willReturnCallback(function ($key, $value) use (&$capturedCalls) {
                $capturedCalls[] = [$key, $value];
            });

        Services::injectMock('autoloader', $autoloader);

        // Execute
        include dirname(__DIR__, 2) . '/src/Config/Events.php';

        // Verify loaded namespaces
        $this->assertCount(3, $capturedCalls, 'addNamespace should be called 3 times');
        $this->assertEquals(
            ['ModuleA', $this->modulesDir . DIRECTORY_SEPARATOR . 'ModuleA' . DIRECTORY_SEPARATOR],
            $capturedCalls[0]
        );
        $this->assertEquals(
            ['ModuleB', $this->modulesDir . DIRECTORY_SEPARATOR . 'ModuleB' . DIRECTORY_SEPARATOR],
            $capturedCalls[1]
        );
        $this->assertEquals(
            ['DevModuleC', $this->devModulesDir . DIRECTORY_SEPARATOR . 'DevModuleC' . DIRECTORY_SEPARATOR],
            $capturedCalls[2]
        );

        // Verify side effects
        // 1. Constants defined by include
        $this->assertTrue(defined('MODULE_A_INIT'), 'ModuleA init.php was not loaded');
        $this->assertTrue(defined('MODULE_B_EVENTS'), 'ModuleB Events.php was not loaded');

        // 2. Pre-system event registered
        // Trigger the pre_system event to verify our hook logic is attached
        // The src/Config/Events.php registers a closure that calls HyperHooks::trigger

        // Create a mock hook to verify trigger is called
        $hooks = \StarCore\Service\HyperHooks::getInstance();
        $hooks->clearAll();

        $triggered = false;
        $hooks->register('core:modules:init', function () use (&$triggered) {
            $triggered = true;
        });

        CIEvents::trigger('pre_system');

        $this->assertTrue($triggered, 'pre_system event did not trigger core:modules:init hook');
    }
}
