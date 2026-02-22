<?php
/**
 * Field maps for wt-airtable-sync.
 *
 * Each top-level key is a WP CPT slug. The controller reads this file to:
 *   1. Validate that an incoming post_type has a map defined.
 *   2. Know which payload keys to write, how to write them, and whether
 *      to resolve Airtable titles to WP post IDs first.
 *
 * Payload shape expected from Make.com for each CPT:
 *   - 'airtable_id'  (string)  Airtable record ID (recXXXXX) — used for upsert
 *   - 'post_title'   (string)  WP post title
 *   - 'post_status'  (string)  WP post status (publish / draft / etc.)
 *   - All field keys listed in the 'meta' sub-array below
 *
 * Field entry shape:
 *   'payload_key' => [
 *       'meta_key'  => string,       WP postmeta key (ACF uses the field name as key)
 *       'acf'       => bool,         true  → update_field(); false → update_post_meta()
 *       'acf_type'  => string|null,  ACF field type — drives resolver selection
 *       'post_type' => string|null,  Target CPT for post_object resolution; null otherwise
 *   ]
 *
 * post_object fields receive comma-separated titles (or an array) from the
 * payload; Field_Resolver::resolve() converts them to WP post IDs before writing.
 *
 * Phase 1: languages only.
 * Phase 2: videos, captions, lexicons.
 * Deferred: resources (Airtable/WP count mismatch — see docs/make-audit-findings.md F3).
 *
 * @return array<string, array<string, array<string, mixed>>>
 */
return array(

	'languages' => array(

		// --- Scalar fields (written directly) ---

		'standard_name'        => array(
			'meta_key'  => 'standard_name',
			'acf'       => true,
			'acf_type'  => 'text',
			'post_type' => null,
		),
		'alternate_names'      => array(
			'meta_key'  => 'alternate_names',
			'acf'       => true,
			'acf_type'  => 'text',
			'post_type' => null,
		),
		'nations_of_origin'    => array(
			'meta_key'  => 'nations_of_origin',
			'acf'       => true,
			'acf_type'  => 'text',
			'post_type' => null,
		),
		'writing_systems'      => array(
			'meta_key'  => 'writing_systems',
			'acf'       => true,
			'acf_type'  => 'text',
			'post_type' => null,
		),
		'linguistic_genealogy' => array(
			'meta_key'  => 'linguistic_genealogy',
			'acf'       => true,
			'acf_type'  => 'text',
			'post_type' => null,
		),
		'iso_code'             => array(
			'meta_key'  => 'iso_code',
			'acf'       => true,
			'acf_type'  => 'text',
			'post_type' => null,
		),
		'glottocode'           => array(
			'meta_key'  => 'glottocode',
			'acf'       => true,
			'acf_type'  => 'text',
			'post_type' => null,
		),
		'olac_url'             => array(
			'meta_key'  => 'olac_url',
			'acf'       => true,
			'acf_type'  => 'url',
			'post_type' => null,
		),
		'wikipedia_url'        => array(
			'meta_key'  => 'wikipedia_url',
			'acf'       => true,
			'acf_type'  => 'url',
			'post_type' => null,
		),

		// --- post_object fields (titles resolved to WP post IDs before writing) ---
		// post_type is the WP CPT searched for each title value.
		// Confirmed from ACF field group group_614a2f1facd00.json.

		'speakers_recorded'    => array(
			'meta_key'  => 'speakers_recorded',
			'acf'       => true,
			'acf_type'  => 'post_object',
			'post_type' => 'videos',
		),
		'lexicon_source'       => array(
			'meta_key'  => 'lexicon_source',
			'acf'       => true,
			'acf_type'  => 'post_object',
			'post_type' => 'lexicons',
		),
		'lexicon_target'       => array(
			'meta_key'  => 'lexicon_target',
			'acf'       => true,
			'acf_type'  => 'post_object',
			'post_type' => 'lexicons',
		),
		'external_resources'   => array(
			'meta_key'  => 'external_resources',
			'acf'       => true,
			'acf_type'  => 'post_object',
			'post_type' => 'resources',
		),
	),

);
