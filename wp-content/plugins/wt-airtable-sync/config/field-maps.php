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
 * Phase 2: videos, captions, lexicons (this file).
 * Deferred: resources (Airtable/WP count mismatch — see docs/make-audit-findings.md F3).
 *           video_thumbnail_v2 (requires media sideload — out of scope for sync endpoint).
 *           metadata group sub-fields width/height (ACF group write requires field key, not name).
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

	// -------------------------------------------------------------------------
	// videos
	// -------------------------------------------------------------------------
	// ACF field group: group_614b82766af00.json
	// Airtable table: Oral Histories
	//
	// Omitted fields (require special handling beyond this endpoint's scope):
	//   metadata.width / metadata.height — ACF group sub-fields; write via
	//     field key rather than field name (deferred to a future phase).
	// -------------------------------------------------------------------------

	'videos'    => array(

		// --- Scalar fields ---

		'video_title'            => array(
			'meta_key'  => 'video_title',
			'acf'       => true,
			'acf_type'  => 'text',
			'post_type' => null,
		),
		'video_description'      => array(
			'meta_key'  => 'video_description',
			'acf'       => true,
			'acf_type'  => 'textarea',
			'post_type' => null,
		),
		'video_license'          => array(
			'meta_key'  => 'video_license',
			'acf'       => true,
			'acf_type'  => 'text',
			'post_type' => null,
		),
		'license_link'           => array(
			'meta_key'  => 'license_link',
			'acf'       => true,
			'acf_type'  => 'url',
			'post_type' => null,
		),
		'public_status'          => array(
			'meta_key'  => 'public_status',
			'acf'       => true,
			'acf_type'  => 'select',
			'post_type' => null,
		),
		'dropbox_link'           => array(
			'meta_key'  => 'dropbox_link',
			'acf'       => true,
			'acf_type'  => 'url',
			'post_type' => null,
		),
		'wikimedia_commons_link' => array(
			'meta_key'  => 'wikimedia_commons_link',
			'acf'       => true,
			'acf_type'  => 'url',
			'post_type' => null,
		),
		'youtube_publish_date'   => array(
			'meta_key'  => 'youtube_publish_date',
			'acf'       => true,
			'acf_type'  => 'text',
			'post_type' => null,
		),
		'youtube_id'             => array(
			'meta_key'  => 'youtube_id',
			'acf'       => true,
			'acf_type'  => 'text',
			'post_type' => null,
		),
		// ACF type is 'link' (stores url/title/target array), but Make.com sends
		// a plain URL string. update_field() accepts a URL string for link fields.
		'youtube_link'           => array(
			'meta_key'  => 'youtube_link',
			'acf'       => true,
			'acf_type'  => 'url',
			'post_type' => null,
		),

		// ACF image field expects a WP attachment ID (integer).
		// Make.com's Import Oral Histories scenario (Module 34) already creates/finds
		// the attachment and sends the ID — no media sideload needed in this plugin.
		'video_thumbnail_v2'     => array(
			'meta_key'  => 'video_thumbnail_v2',
			'acf'       => true,
			'acf_type'  => 'image',
			'post_type' => null,
		),

		// --- post_object fields ---
		// Confirmed from group_614b82766af00.json: featured_languages → languages CPT.

		'featured_languages'     => array(
			'meta_key'  => 'featured_languages',
			'acf'       => true,
			'acf_type'  => 'post_object',
			'post_type' => 'languages',
		),
	),

	// -------------------------------------------------------------------------
	// captions
	// -------------------------------------------------------------------------
	// ACF field group: group_677c053fbaada.json
	// Airtable table: Oral History Captions
	// -------------------------------------------------------------------------

	'captions'  => array(

		// --- Scalar fields ---

		'creator'         => array(
			'meta_key'  => 'creator',
			'acf'       => true,
			'acf_type'  => 'text',
			'post_type' => null,
		),
		'file_url'        => array(
			'meta_key'  => 'file_url',
			'acf'       => true,
			'acf_type'  => 'url',
			'post_type' => null,
		),

		// --- post_object fields ---
		// Confirmed from group_677c053fbaada.json.

		'source_video'    => array(
			'meta_key'  => 'source_video',
			'acf'       => true,
			'acf_type'  => 'post_object',
			'post_type' => 'videos',
		),
		'source_language' => array(
			'meta_key'  => 'source_language',
			'acf'       => true,
			'acf_type'  => 'post_object',
			'post_type' => 'languages',
		),
	),

	// -------------------------------------------------------------------------
	// lexicons
	// -------------------------------------------------------------------------
	// ACF field group: group_614b856757f84.json
	// Airtable table: Lexicons
	// -------------------------------------------------------------------------

	'lexicons'  => array(

		// --- Scalar fields ---

		'dropbox_link'     => array(
			'meta_key'  => 'dropbox_link',
			'acf'       => true,
			'acf_type'  => 'url',
			'post_type' => null,
		),
		'external_link'    => array(
			'meta_key'  => 'external_link',
			'acf'       => true,
			'acf_type'  => 'url',
			'post_type' => null,
		),

		// --- post_object fields ---
		// Confirmed from group_614b856757f84.json: both targets → languages CPT.

		'source_languages' => array(
			'meta_key'  => 'source_languages',
			'acf'       => true,
			'acf_type'  => 'post_object',
			'post_type' => 'languages',
		),
		'target_languages' => array(
			'meta_key'  => 'target_languages',
			'acf'       => true,
			'acf_type'  => 'post_object',
			'post_type' => 'languages',
		),
	),

);
