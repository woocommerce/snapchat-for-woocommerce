/**
 * External dependencies
 */
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/jest-unit.config.js' );

module.exports = {
	...defaultConfig,
	rootDir: '.',
	moduleNameMapper: {
		'\\.svg$': path.join( __dirname, 'tests/js/mocks/asset-stub.js' ),
		'^~/(.*)$': path.join( __dirname, 'js/src/$1' ),
	},
	testMatch: [ '<rootDir>/tests/js/**/*.test.js' ],
};
