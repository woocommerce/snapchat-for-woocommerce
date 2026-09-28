/**
 * External dependencies
 */
import { createRequire } from 'node:module';
import woocommerce from '@woocommerce/eslint-plugin';

const require = createRequire( import.meta.url );
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

export default [
	{
		ignores: [
			'build/**',
			'build-dev/**',
			'build-module/**',
			'coverage/**',
			'js/build/**',
			'languages/**',
			'legacy/**',
			'vendor/**',
		],
	},
	...woocommerce.configs.recommended,
	{
		languageOptions: {
			globals: {
				getComputedStyle: 'readonly',
				wp_has_consent: 'readonly',
				jQuery: 'readonly',
			},
		},
		settings: {
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
		rules: {
			'@wordpress/i18n-text-domain': [
				'error',
				{ allowedTextDomain: 'snapchat-for-woocommerce' },
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
			// Turn it off temporarily because it involves a lot of re-alignment. We can revisit it later.
			'jsdoc/check-line-alignment': 'off',
			// The flat config of @wordpress/eslint-plugin no longer lists `JSX` as a known type.
			'jsdoc/no-undefined-types': [
				'error',
				{ definedTypes: [ 'JSX' ] },
			],
			// Keep ESLint 8's default of not reporting unused `catch` bindings.
			'@typescript-eslint/no-unused-vars': [
				'error',
				{ caughtErrors: 'none', ignoreRestSiblings: true },
			],
		},
	},
	{
		// Node-side config files and the Playwright suite are CommonJS.
		files: [ '*.js', 'tests/**/*.js' ],
		rules: {
			'@typescript-eslint/no-require-imports': 'off',
		},
	},
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
		settings: {
			// E2E specs run on Playwright, so there is no local jest install for eslint-plugin-jest to detect.
			jest: { version: 30 },
		},
		rules: {
			'jest/no-done-callback': 'off',
			'jest/expect-expect': [
				'warn',
				{ assertFunctionNames: [ 'expect', 'expect[A-Z]\\w*' ] },
			],
		},
	},
];
