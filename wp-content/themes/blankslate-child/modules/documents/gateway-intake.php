<?php
/**
 * Intake field set: documents
 *
 * Registered as the 'documents' named set for the gateway_intake_fields filter.
 * Assign this set to the documents CPT via Gateway > Settings > Intake forms.
 */

add_filter(
	'gateway_intake_fields',
	function ( array $sets ): array {
		$sets['documents'] = array(
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
				'key'   => 'organization',
				'label' => 'Are you affiliated with an organization? (optional)',
				'type'  => 'text',
			),
			array(
				'key'     => 'community',
				'label'   => 'Are you a member of the Wikitongues community?',
				'type'    => 'radio',
				'options' => array(
					'speaker'      => 'I\'m a speaker or learner of this language',
					'contributor'  => 'I contribute to Wikitongues',
					'discovering'  => 'I\'m discovering endangered languages',
					'professional' => 'No. I work with language data professionally',
				),
			),
		);
		return $sets;
	}
);
