<?php
if (!class_exists('CareersPostType')) {
    class CareersPostType
    {
        public function __construct()
        {
            add_action('init', [$this, 'register_post_type']);
            add_action('acf/init', [$this, 'register_acf_fields']);
        }

        public function register_post_type()
        {
            $labels = [
                'name'               => __('Careers', 'textdomain'),
                'singular_name'      => __('Career', 'textdomain'),
                'add_new'            => __('Add New', 'textdomain'),
                'add_new_item'       => __('Add New Career', 'textdomain'),
                'edit_item'          => __('Edit Career', 'textdomain'),
                'new_item'           => __('New Career', 'textdomain'),
                'view_item'          => __('View Career', 'textdomain'),
                'search_items'       => __('Search Careers', 'textdomain'),
                'not_found'          => __('No careers found', 'textdomain'),
                'not_found_in_trash' => __('No careers found in Trash', 'textdomain'),
                'menu_name'          => __('Careers', 'textdomain'),
            ];

            $args = [
                'labels'              => $labels,
                'public'              => true,
                'has_archive'         => false,
                'show_in_menu'        => true,
                'show_in_rest'        => true,
                'supports'            => ['title'],
                'rewrite'             => ['slug' => 'careers'],
                'menu_icon'           => 'dashicons-businessperson',
            ];

            register_post_type('careers', $args);
        }

        public function register_acf_fields()
        {
            if (function_exists('acf_add_local_field_group')) {
                acf_add_local_field_group([
                    'key' => 'group_careers_fields',
                    'title' => __('Careers Fields', 'textdomain'),
                    'fields' => [
                        [
                            'key' => 'field_location',
                            'label' => __('Location', 'textdomain'),
                            'name' => 'location',
                            'type' => 'text',
                        ],
                        [
                            'key' => 'field_team_description',
                            'label' => __('Team Description', 'textdomain'),
                            'name' => 'team_description',
                            'type' => 'wysiwyg',
                        ],
                        [
                            'key' => 'field_role_description',
                            'label' => __('Role Description', 'textdomain'),
                            'name' => 'role_description',
                            'type' => 'wysiwyg',
                        ],
                        [
                            'key' => 'field_candidate_background',
                            'label' => __('Candidate Background', 'textdomain'),
                            'name' => 'candidate_background',
                            'type' => 'wysiwyg',
                        ],
                        [
                            'key' => 'field_compensation',
                            'label' => __('Compensation', 'textdomain'),
                            'name' => 'compensation',
                            'type' => 'wysiwyg',
                        ],
                        [
                            'key' => 'field_application',
                            'label' => __('Application Link', 'textdomain'),
                            'name' => 'application',
                            'type' => 'url',
                        ],
                    ],
                    'location' => [
                        [
                            [
                                'param' => 'post_type',
                                'operator' => '==',
                                'value' => 'careers',
                            ],
                        ],
                    ],
                ]);
            }
        }
    }

    new CareersPostType();
}
function display_global_acf_fields() {
	$fields = [
		'why_wikitongues' => 'Why Wikitongues',
		'about_wikitongues' => 'About Wikitongues',
		'dei' => 'Diversity, Equity, and Inclusion',
	];

	foreach ($fields as $field_key => $label) {
			$value = get_field($field_key, 'option');
			if ($value) {
					echo '<div class="' . esc_attr($field_key) . '">';
					echo '<h2>' . esc_html($label) . '</h2>';
					echo wp_kses_post($value);
					echo '</div>';
			}
	}
}
