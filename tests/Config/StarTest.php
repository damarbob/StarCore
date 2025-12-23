<?php

namespace StarCore\Config;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Config\Factories;
use ReflectionClass;

class StarTest extends CIUnitTestCase
{
    private function setProtectedProperty($object, $property, $value)
    {
        $reflection = new ReflectionClass($object);
        $reflection_property = $reflection->getProperty($property);
        $reflection_property->setValue($object, $value);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Factories::reset();
    }

    public function testGetActiveModulesDefault()
    {
        $config = new Star();
        $this->assertEquals([], $config->getActiveModules());
    }

    public function testGetActiveModulesWithCustom()
    {
        $config = new Star();
        $this->setProtectedProperty($config, 'activeModules', 'Blog,Shop');
        $config->__construct(); // Re-run constructor to apply changes
        $this->assertEquals(['Blog', 'Shop'], $config->getActiveModules());
    }

    public function testGetActiveModulesMergesWithDefault()
    {
        $config = new Star();
        $this->setProtectedProperty($config, 'defaultActiveModules', 'Core,Api');
        $this->setProtectedProperty($config, 'activeModules', 'Blog,Shop');
        $config->__construct();
        $this->assertEquals(['Core', 'Api', 'Blog', 'Shop'], $config->getActiveModules());
    }

    public function testGetActiveModulesHandlesDuplicates()
    {
        $config = new Star();
        $this->setProtectedProperty($config, 'defaultActiveModules', 'Core,Api');
        $this->setProtectedProperty($config, 'activeModules', 'Blog,Core');
        $config->__construct();
        $this->assertEquals(['Core', 'Api', 'Blog'], $config->getActiveModules());
    }

    public function testGetActiveDevModules()
    {
        $config = new Star();
        $this->setProtectedProperty($config, 'activeDevModules', 'DebugToolbar,Generator');
        $this->assertEquals(['DebugToolbar', 'Generator'], $config->getActiveDevModules());
    }

    public function testSafeModeDisablesAllModules()
    {
        $config = new Star();
        $config->safeMode = true;
        $this->setProtectedProperty($config, 'defaultActiveModules', 'Core,Api');
        $this->setProtectedProperty($config, 'activeModules', 'Blog,Shop');
        $this->setProtectedProperty($config, 'activeDevModules', 'DebugToolbar');
        $config->__construct();

        $this->assertEquals([], $config->getActiveModules());
        $this->assertEquals([], $config->getActiveDevModules());
    }
}
