<?php
declare(strict_types=1);

namespace SnapchatForWooCommerce\Tests\Unit\Tracking;

use PHPUnit\Framework\TestCase;

/**
 * @covers \SnapchatForWooCommerce\Tracking\ConversionEventInterface
 */
final class ConversionEventInterfaceTest extends TestCase {

	public function test_interface_exists(): void {
		$this->assertTrue(
			interface_exists( \SnapchatForWooCommerce\Tracking\ConversionEvent\ConversionEventInterface::class ),
			'ConversionEventInterface should exist'
		);
	}

	public function test_interface_has_build_payload_method(): void {
		$reflection = new \ReflectionClass( \SnapchatForWooCommerce\Tracking\ConversionEvent\ConversionEventInterface::class );

		$this->assertTrue(
			$reflection->hasMethod( 'build_payload' ),
			'ConversionEventInterface must define method build_payload()'
		);

		$method = $reflection->getMethod( 'build_payload' );

		$this->assertTrue(
			$method->isPublic(),
			'build_payload() must be public'
		);

		$returnType = $method->getReturnType();
		$this->assertNotNull( $returnType, 'build_payload() must have a return type' );
		$this->assertSame( 'array', $returnType->getName(), 'build_payload() must return an array' );
	}
}
