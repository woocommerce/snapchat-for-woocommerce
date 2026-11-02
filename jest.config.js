/**
 * External dependencies
 */
const path = require( 'path' );

module.exports = {
	// `@wordpress/scripts` no longer ships a Jest config, so use the preset it used to extend.
	preset: '@wordpress/jest-preset-default',
	rootDir: '.',
	transform: {
		// The project has no Babel config, so pass the WordPress preset to babel-jest here
		// rather than adding one that the webpack build would also pick up.
		'\\.[jt]sx?$': [
			'babel-jest',
			{ presets: [ '@wordpress/babel-preset-default' ] },
		],
	},
	moduleNameMapper: {
		'\\.svg$': path.join( __dirname, 'tests/mocks/assets/svgFileMock.js' ),
		'^~/(.*)$': path.join( __dirname, 'js/src/$1' ),
	},
	testMatch: [ '<rootDir>/tests/js/**/*.test.js' ],
};
