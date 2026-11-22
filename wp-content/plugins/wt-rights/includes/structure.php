<?php
/**
 * The shape of a country report Doc, as data.
 *
 * The parser and the templates read this instead of assuming six sections and
 * five questions, so a renamed heading is one alias and a new section is one
 * entry. It describes the Docs as they are written (research template v10.2026):
 * sections are Heading 1, questions are plain paragraphs matched by their
 * wording, and the answers sit on "Your answer:" lines.
 *
 * @package WT\Rights
 */

/**
 * The report structure.
 *
 * - `title_prefix`: the Doc's Title line, before the country's name.
 * - `sections`: in order. `type` is `prose` (paragraphs and sources),
 *   `dimensions` (the five questions) or `rating` (the overall rating).
 *   `optional` sections may be empty. `aliases` are older headings still accepted.
 * - `indicators`: the five questions, in order, with the answers each allows
 *   and any older wording still accepted.
 * - `ratings`: the overall rating's two answers, found by a phrase in their line.
 * - `statuses`: each Airtable Research Status and what it means for a release.
 * - `wordpress_tab`: the title of the optional tab holding the story card.
 *
 * @return array{
 *     title_prefix: string,
 *     sections: list<array{key: string, heading: string, type: string, optional?: bool, aliases?: list<string>}>,
 *     indicators: list<array{key: string, label: string, question: string, answers: list<string>, aliases?: list<string>}>,
 *     ratings: list<array{key: string, label: string, phrase: string, answers: list<string>}>,
 *     statuses: array<string, string>,
 *     wordpress_tab: string,
 *     story_card_max_words: int
 * }
 */
function wt_rights_structure(): array {
	$yes_no_unclear = array( 'yes', 'no', 'unclear' );
	$direction      = array( 'negative', 'neutral', 'positive' );

	return array(
		'title_prefix'         => 'The State of Language Rights in ',
		'sections'             => array(
			array(
				'key'     => 'inventory',
				'heading' => 'Language inventory',
				'type'    => 'prose',
			),
			array(
				'key'     => 'history',
				'heading' => 'Historical context',
				'type'    => 'prose',
			),
			array(
				'key'     => 'politics',
				'heading' => 'Current political context',
				'type'    => 'prose',
			),
			array(
				'key'     => 'dimensions',
				'heading' => 'Language rights dimensions',
				'type'    => 'dimensions',
			),
			array(
				'key'     => 'rating',
				'heading' => 'Overall Rating',
				'type'    => 'rating',
			),
			array(
				'key'      => 'testimonies',
				'heading'  => 'Testimonies',
				'type'     => 'prose',
				'optional' => true,
			),
		),
		'indicators'           => array(
			array(
				'key'      => 'i1',
				'label'    => 'Restrictions on language use',
				'question' => 'Does the government prohibit or significantly hinder the use of any language?',
				'answers'  => $yes_no_unclear,
				'aliases'  => array( 'Has the country prohibited or significantly hindered the use of minority languages?' ),
			),
			array(
				'key'      => 'i2',
				'label'    => 'Serious repression',
				'question' => 'Does the government seriously and unfairly repress people because of their language use, linguistic identity, or language advocacy?',
				'answers'  => $yes_no_unclear,
				'aliases'  => array( 'Has the country seriously and unfairly repressed minority language speakers?' ),
			),
			array(
				'key'      => 'i3',
				'label'    => 'Official or co-official recognition',
				'question' => 'Does the country grant official or co-official legal recognition to historically rooted languages beyond those already dominant in state institutions?',
				'answers'  => $yes_no_unclear,
				'aliases'  => array(
					'Has the country officially recognized minority languages as official or co-official?',
					'Does the country grant official or co-official legal recognition to historically rooted languages other than the dominant state language?',
				),
			),
			array(
				'key'      => 'i4',
				'label'    => 'Public support for transmission',
				'question' => 'Does the country facilitate the transmission and development of historically rooted languages through state funding or public institutions?',
				'answers'  => $yes_no_unclear,
				'aliases'  => array( 'Has the country facilitated the cultural transmission of minority languages through state funding or public institutions?' ),
			),
			array(
				'key'      => 'i5',
				'label'    => 'Targeted revitalization',
				'question' => 'Does the country support revitalization initiatives for endangered historically rooted languages?',
				'answers'  => array( 'yes', 'no', 'unclear', 'not applicable' ),
				'aliases'  => array( 'Has the country supported revitalization initiatives for endangered minority languages?' ),
			),
		),
		'ratings'              => array(
			array(
				'key'     => 'ante',
				'label'   => 'Status quo ante',
				'phrase'  => 'status quo ante',
				'answers' => $direction,
			),
			array(
				'key'     => 'trend',
				'label'   => 'Trending direction',
				'phrase'  => 'trending',
				'answers' => $direction,
			),
		),
		// Kristen's Research Status options, unchanged, and what each means for a release.
		// publish: enters the release. held / not_started: left out, listed as forthcoming.
		// blocking: left out, and its region can't be released.
		'statuses'             => array(
			'Unitiated'      => 'not_started',
			'In progress'    => 'blocking',
			'Pending review' => 'blocking',
			'Fact checking'  => 'blocking',
			'Completed'      => 'publish',
			'Held'           => 'held',
		),
		'wordpress_tab'        => 'WordPress',
		'story_card_max_words' => 60,
	);
}
