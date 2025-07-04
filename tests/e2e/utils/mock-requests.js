const proxyFulfill = ( instance, options ) => {
	return new Proxy( instance.originalTarget || instance, {
		get( target, property ) {
			if ( property === 'originalTarget' ) {
				return target;
			}

			if ( property === 'previousOptions' ) {
				return options;
			}

			const value = Reflect.get( ...arguments );

			if ( property === 'fulfillRequest' ) {
				return function ( url, payload, status, methods ) {
					const mergedOpts = {
						...instance.previousOptions,
						...options,
					};
					const args = [ url, payload, status, methods, mergedOpts ];
					return value.apply( target, args );
				};
			}

			return value;
		},
	} );
};

/**
 * MockRequests - A general-purpose utility to mock HTTP requests via Playwright.
 */
export default class MockRequests {
	/**
	 * @param {import('@playwright/test').Page} page
	 */
	constructor( page ) {
		this.page = page;
	}

	/**
	 * Intercepts a request and fulfills it with a given payload and optional options.
	 *
	 * @param {RegExp|string} url URL pattern or string to intercept.
	 * @param {Object} payload The response payload.
	 * @param {number} [status=200] HTTP status code.
	 * @param {Array<string>} [methods=[]] HTTP methods to intercept.
	 * @param {Object} [options={}] Additional options like `times` or `beforeFulfill`.
	 * @return {Promise<void>}
	 */
	async fulfillRequest(
		url,
		payload,
		status = 200,
		methods = [],
		options = {}
	) {
		const handler = async ( route ) => {
			if (
				methods.length === 0 ||
				methods.includes( route.request().method() )
			) {
				const fulfillOptions = {
					status,
					contentType: 'application/json',
					headers: { 'Access-Control-Allow-Origin': '*' },
					body: JSON.stringify( payload ),
				};

				const { beforeFulfill = Promise.resolve() } = options;

				return beforeFulfill.then( () =>
					route.fulfill( fulfillOptions )
				);
			}
			return route.fallback();
		};

		await this.page.route( url, handler, { times: options.times } );
	}

	/**
	 * Chainable method to fulfill a request multiple times.
	 *
	 * @param {number} times
	 * @return {this}
	 */
	withFulfillTimes( times ) {
		return proxyFulfill( this, { times } );
	}

	/**
	 * Defer fulfillment of intercepted requests until manually triggered.
	 *
	 * @return {this} Proxied instance with `continueFulfill` callback.
	 */
	withFulfillDeferred() {
		let continueFulfill;
		const beforeFulfill = new Promise( ( resolve ) => {
			continueFulfill = resolve;
		} );

		const proxiedInstance = proxyFulfill( this, { beforeFulfill } );
		proxiedInstance.continueFulfill = continueFulfill;

		return proxiedInstance;
	}

	/**
	 * Fulfill the JetPack Connection request.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillJetPackConnection( payload ) {
		await this.fulfillRequest( /\/wc\/sfw\/jetpack\/connected\b/, payload );
	}

	/**
	 * Fulfill the request to connect Jetpack.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillConnectJetPack( payload ) {
		await this.fulfillRequest( /\/wc\/sfw\/jetpack\/connect\b/, payload );
	}

	/**
	 * Mock the request to connect Jetpack
	 *
	 * @param {string} url
	 */
	async mockJetpackConnect( url ) {
		await this.fulfillConnectJetPack( { url } );
	}

	/**
	 * Mock Jetpack as connected.
	 *
	 * @param {string} displayName
	 * @param {string} email
	 */
	async mockJetpackConnected(
		displayName = 'John',
		email = 'mail@example.com'
	) {
		await this.fulfillJetPackConnection( {
			active: 'yes',
			owner: 'yes',
			displayName,
			email,
		} );
	}

	/**
	 * Mock Jetpack as not connected.
	 */
	async mockJetpackNotConnected() {
		await this.fulfillJetPackConnection( {
			active: 'no',
			displayName: '',
			email: '',
		} );
	}

	/**
	 * Fulfill the Snapchat Connection request.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillSnapchatConnection( payload ) {
		await this.fulfillRequest( /\/wc\/sfw\/snapchat\/connected\b/, payload );
	}

	/**
	 * Fulfill the request to connect Snapchat.
	 *
	 * @param {Object} payload
	 * @return {Promise<void>}
	 */
	async fulfillConnectSnapchat( payload ) {
		await this.fulfillRequest( /\/wc\/sfw\/snapchat\/connect\b/, payload );
	}
}
