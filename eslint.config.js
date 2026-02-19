const js = require( '@eslint/js' );
const globals = require( 'globals' );

module.exports = [
	js.configs.recommended,
	{
		languageOptions: {
			ecmaVersion: 2020,
			// WordPress JS is enqueued as scripts, not ES modules.
			sourceType: 'script',
			globals: {
				...globals.browser,
				jQuery: 'readonly',
				$: 'readonly',
				wp: 'readonly',
				// Plugin/theme localized script globals (wp_localize_script)
				custom_gallery_ajax_params: 'readonly',
				ajax_object: 'readonly',
			},
		},
		rules: {
			'no-unused-vars': 'warn',
			'no-console': 'warn',
		},
	},
];
