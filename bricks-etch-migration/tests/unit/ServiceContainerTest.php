<?php
/**
 * Unit tests for Service Container
 *
 * @package Bricks2Etch\Tests\Unit
 */

namespace Bricks2Etch\Tests\Unit;

use Bricks2Etch\Container\B2E_Service_Container;
use WP_UnitTestCase;

/**
 * Test Service Container functionality
 */
class ServiceContainerTest extends WP_UnitTestCase {

    /**
     * @var B2E_Service_Container
     */
    private $container;

    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        $this->container = new B2E_Service_Container();
    }

    /**
     * Tear down test environment
     */
    public function tearDown(): void {
        $this->container = null;
        parent::tearDown();
    }

    /**
     * Test that container returns same instance (singleton behavior)
     */
    public function test_container_singleton() {
        $container1 = b2e_container();
        $container2 = b2e_container();

        $this->assertInstanceOf(B2E_Service_Container::class, $container1);
        $this->assertSame($container1, $container2, 'Container should return same instance');
    }

    /**
     * Test service registration
     */
    public function test_service_registration() {
        // Test singleton registration
        $this->container->singleton('test_service', function() {
            return new \stdClass();
        });

        $this->assertTrue($this->container->has('test_service'));

        // Test factory registration
        $this->container->factory('test_factory', function() {
            return new \stdClass();
        });

        $this->assertTrue($this->container->has('test_factory'));
    }

    /**
     * Test service resolution
     */
    public function test_service_resolution() {
        $this->container->singleton('test_service', function() {
            $obj = new \stdClass();
            $obj->value = 'test';
            return $obj;
        });

        $service = $this->container->get('test_service');
        $this->assertInstanceOf(\stdClass::class, $service);
        $this->assertEquals('test', $service->value);

        // Singleton should return same instance
        $service2 = $this->container->get('test_service');
        $this->assertSame($service, $service2);
    }

    /**
     * Test autowiring
     */
    public function test_autowiring() {
        // Register a simple class
        $this->container->singleton(\stdClass::class, function() {
            return new \stdClass();
        });

        // Get should resolve it
        $instance = $this->container->get(\stdClass::class);
        $this->assertInstanceOf(\stdClass::class, $instance);
    }

    /**
     * Test PSR-11 compliance
     */
    public function test_psr11_compliance() {
        $this->container->singleton('test', function() {
            return 'value';
        });

        // Test has() method
        $this->assertTrue($this->container->has('test'));
        $this->assertFalse($this->container->has('nonexistent'));

        // Test get() method
        $this->assertEquals('value', $this->container->get('test'));
    }
}
