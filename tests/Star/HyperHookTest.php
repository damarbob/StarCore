<?php

namespace StarCore\Star;

use PHPUnit\Framework\TestCase;

class HyperHookTest extends TestCase
{
    public function testConstructorAndGetters()
    {
        $hook = new HyperHook('test_name', 'Test Label', 'Test Description');
        $this->assertEquals('test_name', $hook->getName());
        $this->assertEquals('Test Label', $hook->getLabel());
        $this->assertEquals('Test Description', $hook->getDescription());
    }

    public function testJsonSerialize()
    {
        $hook = new HyperHook('test_name', 'Test Label', 'Test Description');
        $json = json_encode($hook);
        $this->assertJsonStringEqualsJsonString('{"name":"test_name","label":"Test Label","description":"Test Description"}', $json);
    }

    public function testJsonSerializeWithAliases()
    {
        HyperHook::setFieldAliases(['name' => 'hook_name', 'label' => 'hook_label']);
        $hook = new HyperHook('test_name', 'Test Label', 'Test Description');
        $json = json_encode($hook);
        $this->assertJsonStringEqualsJsonString('{"hook_name":"test_name","hook_label":"Test Label","description":"Test Description"}', $json);
        // Reset aliases for other tests
        HyperHook::setFieldAliases([]);
    }
}
