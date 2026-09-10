<?php
/**
 * Unit tests for the ChannelVisibilityMetaBox class.
 *
 * @package SnapchatForWooCommerce\Tests\Unit\Admin\MetaBox
 */

namespace SnapchatForWooCommerce\Tests\Unit\Admin\MetaBox;

use WP_UnitTestCase;
use SnapchatForWooCommerce\Admin\MetaBox\ChannelVisibilityMetaBox;

/**
 * @covers \SnapchatForWooCommerce\Admin\MetaBox\ChannelVisibilityMetaBox
 */
final class ChannelVisibilityMetaBoxTest extends WP_UnitTestCase {

	/**
	 * Instance under test.
	 *
	 * @var ChannelVisibilityMetaBox
	 */
	private ChannelVisibilityMetaBox $sut;

	public function set_up(): void {
		parent::set_up();
		$this->sut = new ChannelVisibilityMetaBox();
	}

	public function tear_down(): void {
		global $wp_meta_boxes;
		$wp_meta_boxes = array();
		parent::tear_down();
	}

	/**
	 * With no shared box present, Snapchat registers its own box on the product screen.
	 */
	public function test_standalone_mode_registers_own_meta_box(): void {
		global $wp_meta_boxes;
		$wp_meta_boxes = array();

		$this->sut->maybe_register( 'product', null );

		$this->assertArrayHasKey(
			'snapchat-channel-visibility',
			$wp_meta_boxes['product']['side']['default']
		);

		$box = $wp_meta_boxes['product']['side']['default']['snapchat-channel-visibility'];
		$this->assertSame( 'snapchat-channel-visibility', $box['id'] );
		$this->assertSame( 'Channel visibility', $box['title'] );
	}

	/**
	 * maybe_register does nothing for non-product post types.
	 */
	public function test_ignores_non_product_post_type(): void {
		global $wp_meta_boxes;
		$wp_meta_boxes = array();

		$this->sut->maybe_register( 'page', null );

		$this->assertEmpty( $wp_meta_boxes );
	}

	/**
	 * When a shared channel_visibility box already exists, Snapchat registers no box.
	 */
	public function test_cohabit_mode_does_not_register_second_box(): void {
		global $wp_meta_boxes;
		$wp_meta_boxes = array(
			'product' => array(
				'side' => array(
					'default' => array(
						'channel_visibility' => array(
							'id'       => 'channel_visibility',
							'title'    => 'Channel visibility',
							'callback' => '__return_false',
							'args'     => null,
						),
					),
				),
			),
		);

		$this->sut->maybe_register( 'product', null );

		foreach ( $wp_meta_boxes['product'] as $contexts ) {
			foreach ( $contexts as $priorities ) {
				$this->assertArrayNotHasKey( 'snapchat-channel-visibility', $priorities );
			}
		}
	}

	/**
	 * render() emits exactly the mount-point div.
	 */
	public function test_render_emits_mount_point_div(): void {
		ob_start();
		$this->sut->render();
		$output = ob_get_clean();

		$this->assertSame( '<div id="snapchat-channel-visibility-box"></div>', $output );
	}
}
