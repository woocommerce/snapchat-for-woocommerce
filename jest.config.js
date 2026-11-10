module.exports = {
	// `@wordpress/scripts` 36 no longer ships a Jest config, so use the published
	// preset and the Babel transform it used to provide.
	// Ref: https://github.com/WordPress/gutenberg/blob/%40wordpress/scripts%4036.0.0/packages/scripts/docs/vitest-migration.md#keep-an-existing-jest-suite
	preset: '@wordpress/jest-preset-default',
	transform: {
		'\\.[jt]sx?$': [
			'babel-jest',
			{ presets: [ '@wordpress/babel-preset-default' ] },
		],
	},
	reporters: [ 'default', 'github-actions' ],
	testEnvironment: 'jsdom',
	testPathIgnorePatterns: [ '/node_modules/', '<rootDir>/tests/e2e/' ],
	coveragePathIgnorePatterns: [ '/node_modules/', '<rootDir>/tests/' ],
	watchPathIgnorePatterns: [ '<rootDir>/js/build/' ],
};
