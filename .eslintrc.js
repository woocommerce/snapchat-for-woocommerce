const jsdocConfig = require( '@wordpress/eslint-plugin/configs/jsdoc' );
const webpackConfig = require( './webpack.config' );

const webpackResolver = {
	config: {
		resolve: {
			...webpackConfig.resolve,
			/**
			 * Make eslint correctly resolve files that omit the .js extensions.
			 * The default value `'...'` doesn't work before the current eslint support for webpack v5.
			 * Ref: https://webpack.js.org/configuration/resolve/#resolveextensions
			 */
			extensions: [ '.js' ],
		},
	},
};

module.exports = {
	extends: [
		'plugin:@woocommerce/eslint-plugin/recommended',
		'plugin:import/recommended',
	],
	settings: {
		jsdoc: {
			mode: 'typescript',
		},
		'import/core-modules': [
			'webpack',
			'stylelint',
			'@woocommerce/product-editor',
			'@woocommerce/block-templates',
			'@wordpress/stylelint-config',
			'@pmmmwh/react-refresh-webpack-plugin',
			'react-transition-group',
			'jquery',
		],
		'import/resolver': { webpack: webpackResolver },
	},
	globals: {
		getComputedStyle: 'readonly',
		wp_has_consent: 'readonly',
		jQuery: 'readonly',
	},
	rules: {
		'@wordpress/i18n-text-domain': [
			'error',
			{ allowedTextDomain: 'snapchat-for-woo' },
		],
		'@wordpress/no-unsafe-wp-apis': 1,
		'react/react-in-jsx-scope': 'off',
		'react-hooks/exhaustive-deps': [
			'warn',
			{
				additionalHooks: 'useSelect',
			},
		],
		// compatibility-code "WC < 7.6"
		//
		// Turn it off because:
		// - `import { CurrencyFactory } from '@woocommerce/currency';`
		//   It's supported only since WC 7.6.0
		// - `import { userEvent } from '@testing-library/user-event';`
		//   It works but the official documentation also recommends using the default export
		'import/no-named-as-default': 'off',
		'jest/expect-expect': [
			'warn',
			{ assertFunctionNames: [ 'expect', 'expect[A-Z]\\w*' ] },
		],
		// Turn it off temporarily because it involves a lot of re-alignment. We can revisit it later.
		'jsdoc/check-line-alignment': 'off',
		// Originally, `@fires` tag indicates that when a method is called, it fires
		// a specified type of event that can be listened to, e.g. a native `CustomEvent`.
		// The JS package `tracking-jsdoc` changes the definition of the `@fires` tag to
		// be able to indicate a tracking event will be sent. Therefore, here we list
		// shared `@event` names to avoid false alarms.
		'jsdoc/no-undefined-types': [
			'error',
			{
				definedTypes: [
					...jsdocConfig.rules[ 'jsdoc/no-undefined-types' ][ 1 ]
						.definedTypes,
				],
			},
		],
	},
	overrides: [
		{
			files: [ 'js/src/components/external/woocommerce/**' ],
			rules: {
				'@wordpress/i18n-text-domain': [
					'error',
					{ allowedTextDomain: 'woocommerce' },
				],
			},
		},
		{
			files: [ 'js/src/components/external/wordpress/**' ],
			rules: {
				'@wordpress/i18n-text-domain': [
					'error',
					{ allowedTextDomain: '' },
				],
			},
		},
		{
			files: [ 'tests/e2e/**/*.js' ],
			rules: {
				'jest/no-done-callback': [ 'off' ],
			},
		},
	],
};
