<?php

namespace StarCore\Service;

use PHPUnit\Framework\TestCase;

class HyperHooksTest extends TestCase
{
    private $hooks;

    protected function setUp(): void
    {
        // Reset the singleton instance before each test
        $this->hooks = HyperHooks::getInstance();
        $this->hooks->clearAll();
    }

    public function testGetInstanceReturnsSingleton()
    {
        $instance1 = HyperHooks::getInstance();
        $instance2 = HyperHooks::getInstance();
        $this->assertSame($instance1, $instance2);
    }

    public function testRegisterAndTriggerAction()
    {
        $executed = false;
        $this->hooks->register('test_action', function () use (&$executed) {
            $executed = true;
        });
        $this->hooks->action('test_action');
        $this->assertTrue($executed);
    }

    public function testTriggerWithParameters()
    {
        $result = '';
        $this->hooks->register('test_trigger', function ($param1, $param2) use (&$result) {
            $result = $param1 . $param2;
            return $result;
        });
        $this->hooks->trigger('test_trigger', ['hello', 'world']);
        $this->assertEquals('helloworld', $result);
    }

    public function testFilterValue()
    {
        $this->hooks->register('test_filter', function ($value) {
            return $value . ' filtered';
        });
        $filteredValue = $this->hooks->filter('test_filter', 'value');
        $this->assertEquals('value filtered', $filteredValue);
    }

    public function testPriorityExecution()
    {
        $result = [];
        $this->hooks->register('test_priority', function () use (&$result) {
            $result[] = 'second';
        }, 20);
        $this->hooks->register('test_priority', function () use (&$result) {
            $result[] = 'first';
        }, 10);
        $this->hooks->action('test_priority');
        $this->assertEquals(['first', 'second'], $result);
    }

    public function testUnregisterHook()
    {
        $executed = false;
        $callback = function () use (&$executed) {
            $executed = true;
        };
        $this->hooks->register('test_unregister', $callback);
        $this->hooks->unregister('test_unregister', $callback);
        $this->hooks->action('test_unregister');
        $this->assertFalse($executed);
    }

    public function testClearHook()
    {
        $executed = false;
        $this->hooks->register('test_clear', function () use (&$executed) {
            $executed = true;
        });
        $this->hooks->clear('test_clear');
        $this->hooks->action('test_clear');
        $this->assertFalse($executed);
    }

    public function testStateManagement()
    {
        $this->hooks->setState('test_key', 'test_value');
        $this->assertEquals('test_value', $this->hooks->getState('test_key'));
    }

    public function testTriggerWithReturnAll()
    {
        $this->hooks->register('test_return_all', function () {
            return 'a';
        });
        $this->hooks->register('test_return_all', function () {
            return 'b';
        });

        $result = $this->hooks->trigger('test_return_all', [], true);
        $this->assertEquals(['a', 'b'], $result);
    }

    public function testTriggerWithSingleReturnValue()
    {
        $this->hooks->register('test_single_return', function () {
            return 'a';
        });

        $result = $this->hooks->trigger('test_single_return');
        $this->assertEquals('a', $result);
    }

    public function testTriggerWithNoReturnValue()
    {
        $this->hooks->register('test_no_return', function () {
            // No return value
        });

        $result = $this->hooks->trigger('test_no_return');
        $this->assertNull($result);
    }
}
