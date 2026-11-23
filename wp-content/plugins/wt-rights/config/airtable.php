<?php
/**
 * Airtable bases the exporter can read, keyed by name.
 *
 * Field IDs, not names, so a renamed column changes nothing. A copied base
 * gets new IDs, so each base has its own map. WT_RIGHTS_AIRTABLE_BASE picks
 * one (default: sandbox). Production is added when its token is lent.
 *
 * @package WT\Rights
 */

return array(
	'sandbox' => array(
		'base'       => 'app7PsW1jeK3D2OA5',
		'reports'    => array(
			'table'  => 'tblHkzxWQ9AaC5WOP',
			'fields' => array(
				'name'        => 'fldnfQE4OlLxvNhX6',
				'status'      => 'fldvUkdmnysqT8aYd',
				'report_link' => 'fld3tir7GmJDDXSPh',
				'region'      => 'fldsvUj3CILMG5HwY',
				'nation'      => 'fld3DZkXaj1N1A1Lm',
				'i1'          => 'fldxJazLO6GJVpk9o',
				'i2'          => 'fldSSx0HBZASYMBjy',
				'i3'          => 'fldDQ3Sj3UEqtu0f0',
				'i4'          => 'fldu1wsUQ8Q6ioeGW',
				'i5'          => 'fldOs6bUsl51ifXhR',
				'ante'        => 'fld90a6GSjxqfb35p',
				'trend'       => 'fld5X0Mgz3KcZYoWq',
			),
		),
		'publishing' => array(
			'table'  => 'tblIIDk1suyJJryF1',
			'fields' => array(
				'name'         => 'flddZ9AtUi3nZiETP',
				'kind'         => 'fldRiVRz88EhDeSI0',
				'region'       => 'fldlpYe63EhZEkSJO',
				'doc_link'     => 'fldMlHmffgwH1qAE5',
				'release_date' => 'fldU5Jztx2DEJEpa6',
			),
		),
		'changes'    => array(
			'table'  => 'tbl2qGRJDr1EIe0xZ',
			'fields' => array(
				'note'   => 'fldStYd9z1vdccyOc',
				'report' => 'fldxGk6m9NgWV3Ea3',
				'date'   => 'fld8gP9gVavV6d1v9',
			),
		),
	),
);
