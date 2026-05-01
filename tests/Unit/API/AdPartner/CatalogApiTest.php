<?php
/**
 * Unit tests for CatalogApi.
 *
 * Covers the SNAPWOO-75 fix: `find_or_create()` reuses a stored catalog ID
 * only when it still resolves on the Ad Partner side AND matches the
 * currently-connected organization and pixel, and falls back to `create()`
 * in every other case (no stored ID, 404, transient error, malformed 2xx,
 * org mismatch, pixel mismatch).
 *
 * @package SnapchatForWooCommerce\Tests\Unit\API\AdPartner
 */

namespace SnapchatForWooCommerce\Tests\Unit\API\AdPartner;

use WP_Error;
use WP_REST_Response;
use WP_UnitTestCase;
use SnapchatForWooCommerce\API\AdPartner\CatalogApi;
use SnapchatForWooCommerce\Connection\WcsClient;
use SnapchatForWooCommerce\Utils\Storage\Options;
use SnapchatForWooCommerce\Utils\Storage\OptionDefaults;

/**
 * @covers \SnapchatForWooCommerce\API\AdPartner\CatalogApi
 */
class CatalogApiTest extends WP_UnitTestCase {

	/**
	 * Reset options that the tests mutate.
	 */
	public function set_up(): void {
		parent::set_up();

		Options::delete( OptionDefaults::CATALOG_ID );
		Options::delete( OptionDefaults::ORGANIZATION_ID );
		Options::delete( OptionDefaults::PIXEL_ID );
	}

	/**
	 * Test: `get_catalog()` returns WP_Error for an empty catalog ID without hitting WCS.
	 */
	public function test_get_catalog_returns_error_for_empty_id(): void {
		$wcs = $this->createMock( WcsClient::class );
		$wcs->expects( $this->never() )->method( 'proxy_get' );

		$api    = new CatalogApi( $wcs );
		$result = $api->get_catalog( '' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'catalog_id_empty', $result->get_error_code() );
	}

	/**
	 * Test: `get_catalog()` proxies GET to `/ads/v1/catalogs/{id}` with the ID URL-encoded.
	 */
	public function test_get_catalog_proxies_to_correct_endpoint(): void {
		$wcs = $this->createMock( WcsClient::class );
		$wcs->expects( $this->once() )
			->method( 'proxy_get' )
			->with( '/ads/v1/catalogs/abc-123' )
			->willReturn(
				new WP_REST_Response(
					array(
						'catalogs' => array(
							array(
								'sub_request_status' => 'SUCCESS',
								'catalog'            => array( 'id' => 'abc-123' ),
							),
						),
					),
					200
				)
			);

		$api    = new CatalogApi( $wcs );
		$result = $api->get_catalog( 'abc-123' );

		$this->assertInstanceOf( WP_REST_Response::class, $result );
	}

	/**
	 * Test: `find_or_create()` skips the lookup and calls `create()` when no
	 * `CATALOG_ID` is stored (first-time connect).
	 */
	public function test_find_or_create_calls_create_when_no_stored_id(): void {
		$wcs = $this->createMock( WcsClient::class );

		$api = $this->getMockBuilder( CatalogApi::class )
			->setConstructorArgs( array( $wcs ) )
			->onlyMethods( array( 'get_catalog', 'create' ) )
			->getMock();

		$api->expects( $this->never() )->method( 'get_catalog' );

		$create_response = new WP_REST_Response(
			array(
				'catalogs' => array(
					array(
						'catalog' => array( 'id' => 'new-id' ),
					),
				),
			)
		);
		$api->expects( $this->once() )->method( 'create' )->willReturn( $create_response );

		$result = $api->find_or_create();

		$this->assertSame( $create_response, $result );
	}

	/**
	 * Test: `find_or_create()` reuses the stored `CATALOG_ID` when `get_catalog()`
	 * confirms it still exists AND the catalog's organization + pixel match the
	 * currently-connected values. Snap's GET envelope already matches the POST
	 * envelope, so the response is passed straight through to the caller.
	 */
	public function test_find_or_create_reuses_existing_id_when_get_catalog_succeeds(): void {
		Options::set( OptionDefaults::CATALOG_ID, 'existing-id' );
		Options::set( OptionDefaults::ORGANIZATION_ID, 'org-xyz' );
		Options::set( OptionDefaults::PIXEL_ID, 'pixel-abc' );

		$wcs = $this->createMock( WcsClient::class );

		$api = $this->getMockBuilder( CatalogApi::class )
			->setConstructorArgs( array( $wcs ) )
			->onlyMethods( array( 'get_catalog', 'create' ) )
			->getMock();

		$catalog_payload = array(
			'id'              => 'existing-id',
			'name'            => 'Example Store',
			'vertical'        => 'COMMERCE',
			'organization_id' => 'org-xyz',
			'event_sources'   => array(
				array(
					'id'   => 'pixel-abc',
					'type' => 'PIXEL',
				),
			),
		);

		// Real Snap `GET /v1/catalogs/{id}` shape matches POST: plural `catalogs`
		// array with each entry wrapping a singular `catalog` object.
		$get_response = new WP_REST_Response(
			array(
				'request_status' => 'SUCCESS',
				'request_id'     => 'req-abc',
				'catalogs'       => array(
					array(
						'sub_request_status' => 'SUCCESS',
						'catalog'            => $catalog_payload,
					),
				),
			),
			200
		);

		$api->expects( $this->once() )
			->method( 'get_catalog' )
			->with( 'existing-id' )
			->willReturn( $get_response );

		$api->expects( $this->never() )->method( 'create' );

		$result = $api->find_or_create();

		// The GET response is returned verbatim — no re-wrapping needed.
		$this->assertSame( $get_response, $result );

		$data = $result->get_data();
		$this->assertSame( $catalog_payload, $data['catalogs'][0]['catalog'] );
	}

	/**
	 * Test: `find_or_create()` creates a new catalog when the stored catalog's
	 * `organization_id` does not match the currently-connected organization.
	 *
	 * Covers the reconnect-against-a-different-org case where blindly reusing
	 * the stored ID would leave local state pointing at a catalog that belongs
	 * to someone else's org.
	 */
	public function test_find_or_create_creates_new_when_organization_mismatches(): void {
		Options::set( OptionDefaults::CATALOG_ID, 'existing-id' );
		Options::set( OptionDefaults::ORGANIZATION_ID, 'org-current' );
		Options::set( OptionDefaults::PIXEL_ID, 'pixel-current' );

		$wcs = $this->createMock( WcsClient::class );

		$api = $this->getMockBuilder( CatalogApi::class )
			->setConstructorArgs( array( $wcs ) )
			->onlyMethods( array( 'get_catalog', 'create' ) )
			->getMock();

		$api->method( 'get_catalog' )->willReturn(
			new WP_REST_Response(
				array(
					'catalogs' => array(
						array(
							'sub_request_status' => 'SUCCESS',
							'catalog'            => array(
								'id'              => 'existing-id',
								'organization_id' => 'org-different',
								'event_sources'   => array(
									array(
										'id'   => 'pixel-current',
										'type' => 'PIXEL',
									),
								),
							),
						),
					),
				),
				200
			)
		);

		$create_response = new WP_REST_Response(
			array(
				'catalogs' => array(
					array(
						'catalog' => array( 'id' => 'fresh-id' ),
					),
				),
			)
		);
		$api->expects( $this->once() )->method( 'create' )->willReturn( $create_response );

		$result = $api->find_or_create();

		$this->assertSame( $create_response, $result );
	}

	/**
	 * Test: `find_or_create()` creates a new catalog when the current `PIXEL_ID`
	 * is not listed in the stored catalog's `event_sources`.
	 *
	 * Covers the reconnect-with-different-pixel case: the stored catalog still
	 * exists and is in the right org, but it is wired to a pixel the user is
	 * no longer using.
	 */
	public function test_find_or_create_creates_new_when_pixel_not_in_event_sources(): void {
		Options::set( OptionDefaults::CATALOG_ID, 'existing-id' );
		Options::set( OptionDefaults::ORGANIZATION_ID, 'org-current' );
		Options::set( OptionDefaults::PIXEL_ID, 'pixel-new' );

		$wcs = $this->createMock( WcsClient::class );

		$api = $this->getMockBuilder( CatalogApi::class )
			->setConstructorArgs( array( $wcs ) )
			->onlyMethods( array( 'get_catalog', 'create' ) )
			->getMock();

		$api->method( 'get_catalog' )->willReturn(
			new WP_REST_Response(
				array(
					'catalogs' => array(
						array(
							'sub_request_status' => 'SUCCESS',
							'catalog'            => array(
								'id'              => 'existing-id',
								'organization_id' => 'org-current',
								'event_sources'   => array(
									array(
										'id'   => 'pixel-old',
										'type' => 'PIXEL',
									),
								),
							),
						),
					),
				),
				200
			)
		);

		$create_response = new WP_REST_Response(
			array(
				'catalogs' => array(
					array(
						'catalog' => array( 'id' => 'fresh-id' ),
					),
				),
			)
		);
		$api->expects( $this->once() )->method( 'create' )->willReturn( $create_response );

		$result = $api->find_or_create();

		$this->assertSame( $create_response, $result );
	}

	/**
	 * Test: `find_or_create()` creates a new catalog when `get_catalog()` returns
	 * a 2xx response that is missing the `catalog` object (malformed upstream).
	 *
	 * We can't trust a "valid" lookup without the catalog payload, so we fall
	 * through to `create()` rather than persisting partial state.
	 */
	public function test_find_or_create_creates_new_when_response_missing_catalog_key(): void {
		Options::set( OptionDefaults::CATALOG_ID, 'existing-id' );
		Options::set( OptionDefaults::ORGANIZATION_ID, 'org-current' );
		Options::set( OptionDefaults::PIXEL_ID, 'pixel-current' );

		$wcs = $this->createMock( WcsClient::class );

		$api = $this->getMockBuilder( CatalogApi::class )
			->setConstructorArgs( array( $wcs ) )
			->onlyMethods( array( 'get_catalog', 'create' ) )
			->getMock();

		$api->method( 'get_catalog' )->willReturn(
			new WP_REST_Response( array( 'request_status' => 'SUCCESS' ), 200 )
		);

		$create_response = new WP_REST_Response(
			array(
				'catalogs' => array(
					array(
						'catalog' => array( 'id' => 'fresh-id' ),
					),
				),
			)
		);
		$api->expects( $this->once() )->method( 'create' )->willReturn( $create_response );

		$result = $api->find_or_create();

		$this->assertSame( $create_response, $result );
	}

	/**
	 * Test: `find_or_create()` falls back to `create()` when `get_catalog()` returns
	 * a WP_Error simulating HTTP 404 (catalog deleted remotely).
	 */
	public function test_find_or_create_falls_back_to_create_on_get_catalog_404(): void {
		Options::set( OptionDefaults::CATALOG_ID, 'stale-id' );

		$wcs = $this->createMock( WcsClient::class );

		$api = $this->getMockBuilder( CatalogApi::class )
			->setConstructorArgs( array( $wcs ) )
			->onlyMethods( array( 'get_catalog', 'create' ) )
			->getMock();

		$api->expects( $this->once() )
			->method( 'get_catalog' )
			->with( 'stale-id' )
			->willReturn( new WP_Error( 'snapchat_for_woocommerce_request_failed', 'Request failed' ) );

		$create_response = new WP_REST_Response(
			array(
				'catalogs' => array(
					array(
						'catalog' => array( 'id' => 'fresh-id' ),
					),
				),
			)
		);
		$api->expects( $this->once() )->method( 'create' )->willReturn( $create_response );

		$result = $api->find_or_create();

		$this->assertSame( $create_response, $result );
	}

	/**
	 * Test: `find_or_create()` falls back to `create()` when `get_catalog()` returns
	 * a WP_Error from a transient failure (network / auth).
	 *
	 * Documents the current "fail-open" behaviour: any WP_Error from `get_catalog()`
	 * is treated as "missing, create new". If we later decide to fail-closed on
	 * transient errors, this test will need to be revisited.
	 */
	public function test_find_or_create_falls_back_to_create_on_get_catalog_transient_error(): void {
		Options::set( OptionDefaults::CATALOG_ID, 'existing-id' );

		$wcs = $this->createMock( WcsClient::class );

		$api = $this->getMockBuilder( CatalogApi::class )
			->setConstructorArgs( array( $wcs ) )
			->onlyMethods( array( 'get_catalog', 'create' ) )
			->getMock();

		$api->expects( $this->once() )
			->method( 'get_catalog' )
			->with( 'existing-id' )
			->willReturn( new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' ) );

		$create_response = new WP_REST_Response(
			array(
				'catalogs' => array(
					array(
						'catalog' => array( 'id' => 'new-id' ),
					),
				),
			)
		);
		$api->expects( $this->once() )->method( 'create' )->willReturn( $create_response );

		$result = $api->find_or_create();

		$this->assertSame( $create_response, $result );
	}
}
