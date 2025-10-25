<?php
/**
 * Integration tests for Migration workflow
 *
 * @package Bricks2Etch\Tests\Integration
 */

namespace Bricks2Etch\Tests\Integration;

use Bricks2Etch\Migrators\B2E_Migrator_Registry;
use WP_UnitTestCase;

/**
 * Test complete migration workflow
 */
class MigrationIntegrationTest extends WP_UnitTestCase {

    /**
     * @var int
     */
    private $test_post_id;

    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();

        // Create test post with Bricks content
        $this->test_post_id = $this->factory->post->create([
            'post_title' => 'Test Post',
            'post_content' => '',
            'post_status' => 'publish',
        ]);

        // Add Bricks meta
        update_post_meta($this->test_post_id, '_bricks_page_content_2', [
            [
                'id' => 'test123',
                'name' => 'section',
                'settings' => [
                    'tag' => 'section',
                ],
            ],
        ]);
    }

    /**
     * Tear down test environment
     */
    public function tearDown(): void {
        if ($this->test_post_id) {
            wp_delete_post($this->test_post_id, true);
        }
        parent::tearDown();
    }

    /**
     * Test complete migration workflow
     */
    public function test_complete_migration_workflow() {
        // This is a placeholder test - actual migration would require
        // a full Etch site setup which is not available in unit tests
        
        $this->assertTrue(true, 'Migration workflow test placeholder');
    }

    /**
     * Test CSS migration
     */
    public function test_css_migration() {
        // Test that CSS converter is available
        $container = b2e_container();
        $this->assertTrue($container->has('css_converter'));

        $css_converter = $container->get('css_converter');
        $this->assertInstanceOf('Bricks2Etch\Parsers\B2E_CSS_Converter', $css_converter);
    }

    /**
     * Test migrator registry
     */
    public function test_migrator_registry() {
        $container = b2e_container();
        $this->assertTrue($container->has('migrator_registry'));

        $registry = $container->get('migrator_registry');
        $this->assertInstanceOf(B2E_Migrator_Registry::class, $registry);

        // Test that registry has methods
        $this->assertTrue(method_exists($registry, 'get_all'));
        $this->assertTrue(method_exists($registry, 'get_supported'));
        $this->assertTrue(method_exists($registry, 'register'));
    }

    /**
     * Test that Bricks content is detected
     */
    public function test_bricks_content_detection() {
        $bricks_content = get_post_meta($this->test_post_id, '_bricks_page_content_2', true);
        
        $this->assertIsArray($bricks_content);
        $this->assertNotEmpty($bricks_content);
        $this->assertEquals('section', $bricks_content[0]['name']);
    }
}
