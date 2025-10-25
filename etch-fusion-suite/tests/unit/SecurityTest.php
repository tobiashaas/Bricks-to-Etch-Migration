<?php

declare(strict_types=1);

namespace Bricks2Etch\Tests\Unit;

use Bricks2Etch\Security\EFS_Audit_Logger;
use Bricks2Etch\Security\EFS_CORS_Manager;
use Bricks2Etch\Security\EFS_Environment_Detector;
use Bricks2Etch\Security\EFS_Input_Validator;
use Bricks2Etch\Security\EFS_Rate_Limiter;
use Bricks2Etch\Security\EFS_Security_Headers;
use WP_UnitTestCase;

class SecurityTest extends WP_UnitTestCase {
	/** @var \Bricks2Etch\Container\EFS_Service_Container */
	private $container;

	protected function setUp(): void {
		parent::setUp();

		$this->container = \efs_container();

		delete_option( 'efs_cors_allowed_origins' );
		delete_option( 'efs_security_settings' );
		delete_option( 'efs_security_log' );
	}

	public function test_cors_manager_returns_default_origins_when_not_configured(): void {
		/** @var EFS_CORS_Manager $manager */
		$manager = $this->container->get( 'cors_manager' );

		$origins = $manager->get_allowed_origins();

		$this->assertContains( 'http://localhost:8888', $origins );
		$this->assertContains( 'http://localhost:8889', $origins );
	}

	public function test_rate_limiter_enforces_limit_within_window(): void {
		/** @var EFS_Rate_Limiter $rateLimiter */
		$rateLimiter = $this->container->get( 'rate_limiter' );

		$identifier = 'security-test-' . wp_rand();
		$action     = 'unit_security_limit';
		$limit      = 5;
		$window     = 60;

		$transientKey = 'efs_rate_limit_' . $action . '_' . md5( $identifier );
		delete_transient( $transientKey );

		for ( $i = 0; $i < $limit; $i++ ) {
			$rateLimiter->record_request( $identifier, $action, $window );
		}

		$this->assertTrue(
			$rateLimiter->check_rate_limit( $identifier, $action, $limit, $window ),
			'Rate limiter should block once the limit is reached.'
		);
	}

	public function test_input_validator_rejects_invalid_url(): void {
		/** @var EFS_Input_Validator $validator */
		$validator = $this->container->get( 'input_validator' );

		$this->expectException( \InvalidArgumentException::class );
		$validator->validate_url( 'nota:url', true );
	}

	public function test_security_headers_builds_admin_csp_policy(): void {
		if ( ! function_exists( 'set_current_screen' ) ) {
			require_once ABSPATH . 'wp-admin/includes/screen.php';
		}

		set_current_screen( 'dashboard' );

		$headers = new EFS_Security_Headers();
		$csp     = $headers->get_csp_policy();

		$this->assertStringContainsString( "script-src", $csp );
		$this->assertStringContainsString( "'unsafe-inline'", $csp );
	}

	public function test_audit_logger_persists_security_events(): void {
		/** @var EFS_Audit_Logger $logger */
		$logger = $this->container->get( 'audit_logger' );

		$logger->log_security_event( 'unit_test_event', 'low', 'Unit test event recorded.' );

		$stored = get_option( 'efs_security_log', array() );
		$this->assertNotEmpty( $stored );
		$this->assertSame( 'unit_test_event', $stored[0]['event_type'] );
	}

	public function test_environment_detector_identifies_local_environment(): void {
		/** @var EFS_Environment_Detector $detector */
		$detector = $this->container->get( 'environment_detector' );

		$original_host = $_SERVER['HTTP_HOST'] ?? null;
		$_SERVER['HTTP_HOST'] = 'test.local';

		$this->assertTrue( $detector->is_local_environment() );

		if ( null === $original_host ) {
			unset( $_SERVER['HTTP_HOST'] );
		} else {
			$_SERVER['HTTP_HOST'] = $original_host;
		}
	}
}
