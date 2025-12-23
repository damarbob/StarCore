<?php

namespace StarCore\Helpers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Autoloader\FileLocator;
use Config\Services;
use StarCore\Star\HyperHook;

class SecurityTest extends CIUnitTestCase
{
    private $mockLocator;
    private $testHookFile;

    protected function setUp(): void
    {
        parent::setUp();
        helper('star');

        // Create a temporary file OUTSIDE of a Hooks directory structure simulation
        $this->testHookFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'Malicious.php';
        $hooksContent = "<?php
        return ['malicious' => new \StarCore\Star\HyperHook('hacked', 'Hacked', 'Hacked')];";
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

    public function testHookPathTraversal()
    {
        // Spy on the argument
        $capturedPath = null;
        $this->mockLocator->method('search')
            ->willReturnCallback(function ($path) use (&$capturedPath) {
                $capturedPath = $path;
                return [$this->testHookFile];
            });

        $result = hook('../Malicious.malicious');

        // Expectation:
        // 1. Locator search should NOT be called (or at least not with the traversal path)
        // because the validator stops it.
        $this->assertNull($capturedPath, 'FileLocator::search should not have been called with traversal path');

        // 2. Result should be empty string
        $this->assertSame('', $result);
    }

    public function testHookValidPathWithSlash()
    {
        // Spy on the argument
        $capturedPath = null;
        $this->mockLocator->method('search')
            ->willReturnCallback(function ($path) use (&$capturedPath) {
                $capturedPath = $path;
                return [$this->testHookFile];
            });

        // Valid path 'Admin/User' should pass validation
        // (We won't get a valid result here because our mock returns a file that doesn't 
        // match 'Admin/User.hooks', but we just want to verify validation passed)
        hook('Admin/User.save');

        $this->assertEquals('Hooks/Admin/User.php', $capturedPath);
    }

    public function testDumpHooksPathTraversal()
    {
        // Spy on the argument
        $capturedPath = null;
        $this->mockLocator->method('search')
            ->willReturnCallback(function ($path) use (&$capturedPath) {
                $capturedPath = $path;
                return [$this->testHookFile];
            });

        $result = dump_hooks('../Malicious');

        // Expectation:
        // 1. Locator search should NOT be called
        $this->assertNull($capturedPath, 'FileLocator::search should not have been called with traversal path in dump_hooks');

        // 2. Result should be empty array
        $this->assertEmpty($result);
    }

    public function testDumpHooksValidPath()
    {
        // Spy on the argument
        $capturedPath = null;
        $this->mockLocator->method('search')
            ->willReturnCallback(function ($path) use (&$capturedPath) {
                $capturedPath = $path;
                return [$this->testHookFile];
            });

        // Valid path 'Admin/User' should pass validation
        dump_hooks('Admin/User');

        $this->assertEquals('Hooks/Admin/User.php', $capturedPath);
    }
}
