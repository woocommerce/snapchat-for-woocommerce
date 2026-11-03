<?php
/**
 * Unit tests for the ChannelVisibilityMetaBox class.
 *
 * @package SnapchatForWooCommerce\Tests\Unit\Admin\MetaBox
 */

namespace SnapchatForWooCommerce\Tests\Unit\Admin\MetaBox;

use WP_UnitTestCase;
use SnapchatForWooCommerce\Admin\MetaBox\ChannelVisibilityMetaBox;
use SnapchatForWooCommerce\Utils\Helper;

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
		$_POST         = array();
		parent::tear_down();
	}

	/**
	 * With no shared box present, Snapchat registers the shared box on the product screen.
	 */
	public function test_standalone_mode_registers_shared_meta_box(): void {
		global $wp_meta_boxes;
		$wp_meta_boxes = array();

		$this->sut->maybe_register( 'product', null );

		$this->assertArrayHasKey(
			ChannelVisibilityMetaBox::SHARED_BOX_ID,
			$wp_meta_boxes['product']['side']['default']
		);

		$box = $wp_meta_boxes['product']['side']['default'][ ChannelVisibilityMetaBox::SHARED_BOX_ID ];
		$this->assertSame( 'channel_visibility', $box['id'] );
		$this->assertSame( 'Channel visibility', $box['title'] );
		$this->assertSame( array( $this->sut, 'render' ), $box['callback'] );
	}

	/**
	 * Once Snapchat has registered the shared box, a second run leaves it as it is,
	 * the same check any other channel plugin running later makes.
	 */
	public function test_standalone_box_is_detected_as_shared_box(): void {
		global $wp_meta_boxes;
		$wp_meta_boxes = array();

		$this->sut->maybe_register( 'product', null );
		$registered = $wp_meta_boxes;

		$this->sut->maybe_register( 'product', null );

		$this->assertSame( $registered, $wp_meta_boxes );
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

		$this->assertSame( array( 'default' ), array_keys( $wp_meta_boxes['product']['side'] ) );
		$this->assertSame( '__return_false', $wp_meta_boxes['product']['side']['default']['channel_visibility']['callback'] );
	}

	/**
	 * render() emits exactly the mount-point div.
	 */
	public function test_render_emits_mount_point_div(): void {
		ob_start();
		$this->sut->render();
		$output = ob_get_clean();

		$this->assertSame( '<div id="snapchat-channel-visibility-box" data-service="snapchat"></div>', $output );
	}

	/**
	 * save_meta() stores '1' when the toggle submits '1'.
	 */
	public function test_save_meta_stores_enabled_value(): void {
		$product_id = self::factory()->post->create( array( 'post_type' => 'product' ) );
		$meta_key   = Helper::with_prefix( ChannelVisibilityMetaBox::CATALOG_ITEM );

		$_POST[ $meta_key ] = '1';
		$this->sut->save_meta( $product_id );

		$this->assertSame( '1', get_post_meta( $product_id, $meta_key, true ) );
	}

	/**
	 * save_meta() stores '0' when the toggle submits '0'.
	 */
	public function test_save_meta_stores_disabled_value(): void {
		$product_id = self::factory()->post->create( array( 'post_type' => 'product' ) );
		$meta_key   = Helper::with_prefix( ChannelVisibilityMetaBox::CATALOG_ITEM );
		update_post_meta( $product_id, $meta_key, '1' );

		$_POST[ $meta_key ] = '0';
		$this->sut->save_meta( $product_id );

		$this->assertSame( '0', get_post_meta( $product_id, $meta_key, true ) );
	}

	/**
	 * save_meta() stores '0' for any value other than '1'.
	 */
	public function test_save_meta_normalizes_unexpected_value(): void {
		$product_id = self::factory()->post->create( array( 'post_type' => 'product' ) );
		$meta_key   = Helper::with_prefix( ChannelVisibilityMetaBox::CATALOG_ITEM );

		$_POST[ $meta_key ] = 'yes';
		$this->sut->save_meta( $product_id );

		$this->assertSame( '0', get_post_meta( $product_id, $meta_key, true ) );
	}

	/**
	 * save_meta() leaves existing meta untouched when the toggle was not rendered.
	 */
	public function test_save_meta_preserves_value_when_field_missing(): void {
		$product_id = self::factory()->post->create( array( 'post_type' => 'product' ) );
		$meta_key   = Helper::with_prefix( ChannelVisibilityMetaBox::CATALOG_ITEM );
		update_post_meta( $product_id, $meta_key, '1' );

		$this->sut->save_meta( $product_id );

		$this->assertSame( '1', get_post_meta( $product_id, $meta_key, true ) );
	}

	/**
	 * save_meta() does not create meta when the field is missing, keeping the default-on read.
	 */
	public function test_save_meta_does_not_create_meta_when_field_missing(): void {
		$product_id = self::factory()->post->create( array( 'post_type' => 'product' ) );
		$meta_key   = Helper::with_prefix( ChannelVisibilityMetaBox::CATALOG_ITEM );

		$this->sut->save_meta( $product_id );

		$this->assertFalse( metadata_exists( 'post', $product_id, $meta_key ) );
	}

	/**
	 * register_hooks() wires save_meta() to the WooCommerce product save.
	 */
	public function test_register_hooks_adds_save_meta_action(): void {
		$this->sut->register_hooks();

		$this->assertNotFalse( has_action( 'woocommerce_process_product_meta', array( $this->sut, 'save_meta' ) ) );
	}
}
