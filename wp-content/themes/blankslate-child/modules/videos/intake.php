<?php
/**
 * Intake field set: videos
 *
 * Registered as the 'videos' named set for the gateway_intake_fields filter.
 * Assign this set to the videos CPT via Gateway > Settings > Intake forms.
 */

add_filter(
	'gateway_intake_fields',
	function ( array $sets ): array {
		$sets['videos'] = array(
			array(
				'key'     => 'use_case',
				'label'   => 'How will you use this resource?',
				'type'    => 'select',
				'options' => array(
					'research'    => 'Personal or academic research',
					'teaching'    => 'Teaching or curriculum development',
					'documentary' => 'Documentary or journalism',
					'advocacy'    => 'Advocacy or policy work',
					'personal'    => 'Personal interest',
					'other'       => 'Other',
				),
			),
			array(
				'key'     => 'community',
				'label'   => 'Are you a member of the Wikitongues community?',
				'type'    => 'radio',
				'options' => array(
					'speaker'      => 'Yes — I\'m a speaker or learner of this language',
					'contributor'  => 'Yes — I contribute to Wikitongues',
					'discovering'  => 'No — I\'m discovering endangered languages',
					'professional' => 'No — I work with language data professionally',
				),
			),
		);
		return $sets;
	}
);
