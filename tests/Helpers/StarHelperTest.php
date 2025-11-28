<?php

namespace StarCore\Helpers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Autoloader\FileLocator;
use Config\Services;
use StarCore\Star\HyperHook;

class StarHelperTest extends CIUnitTestCase
{
    private $mockLocator;
    private $testHookFile;

    protected function setUp(): void
    {
        parent::setUp();
        helper('star');

        // Create a temporary hook file for testing
        $this->testHookFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'TestHooks.php';
        $hooksContent = "<?php
        use StarCore\Star\HyperHook;
        return [
            'test_hook' => new HyperHook('test_hook_name', 'Test Label', 'Test Description'),
            'param_hook' => new HyperHook('param_hook_{id}', 'Param Label', 'Param Description'),
        ];";
        file_put_contents($this->testHookFile, $hooksContent);

        // Mock FileLocator
        $this->mockLocator = $this->createMock(FileLocator::class);
        Services::injectMock('locator', $this->mockLocator);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (file_exists($this->testHookFile)) {
            unlink($this->testHookFile);
        }
    }

    public function testHookReturnsEmptyStringForInvalidKey()
    {
        $this->assertSame('', hook('InvalidKeyFormat'));
    }

    public function testHookReturnsEmptyStringWhenFileNotFound()
    {
        $this->mockLocator->method('search')->willReturn([]);
        $this->assertSame('', hook('Group.key'));
    }

    public function testHookReturnsNameForValidKey()
    {
        $this->mockLocator->method('search')->willReturn([$this->testHookFile]);

        // We need to clear the static cache in the helper function. 
        // Since we can't easily access the static variable, we rely on the fact 
        // that different groups are cached separately.
        $this->assertSame('test_hook_name', hook('TestHooks.test_hook'));
    }

    public function testHookReplacesParameters()
    {
        $this->mockLocator->method('search')->willReturn([$this->testHookFile]);
        $this->assertSame('param_hook_123', hook('TestHooks.param_hook', ['id' => '123']));
    }

    public function testDumpHooksReturnsAllHooks()
    {
        $this->mockLocator->method('listFiles')->willReturn([$this->testHookFile]);

        // Mock pathinfo to return 'TestHooks' for our temp file
        // Since we can't mock built-in functions easily, we'll rely on the file name
        // The helper uses pathinfo($file, PATHINFO_FILENAME)

        // Note: dump_hooks uses require, so we need to make sure the file returns an array
        // Our temp file does that.

        $hooks = dump_hooks();
        $this->assertIsArray($hooks);
        // The key will be the filename without extension. 
        // Since we used sys_get_temp_dir(), the filename is TestHooks.php
        $this->assertArrayHasKey('TestHooks', $hooks);
        $this->assertArrayHasKey('test_hook', $hooks['TestHooks']);
    }

    public function testDumpHooksReturnsSpecificGroup()
    {
        $this->mockLocator->method('search')->willReturn([$this->testHookFile]);

        $hooks = dump_hooks('TestHooks');
        $this->assertIsArray($hooks);
        $this->assertArrayHasKey('TestHooks', $hooks);
        $this->assertArrayHasKey('test_hook', $hooks['TestHooks']);
    }
}
