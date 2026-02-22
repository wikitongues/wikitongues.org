<?php
/**
 * Field maps for wt-airtable-sync.
 *
 * Maps Airtable field names to WP meta keys for each supported CPT.
 * Used by Sync_API to validate the post_type route parameter, and by
 * the upsert handler (Phase 1+) to drive field writes.
 *
 * Structure per field entry:
 *   'airtable_field' => [
 *       'meta_key'  => string,   // wp_postmeta key (ACF uses the field name)
 *       'acf'       => bool,     // true = write via update_field(); false = update_post_meta()
 *       'acf_type'  => string|null,  // ACF field type; drives resolver selection
 *       'transform' => string|null,  // named transform: 'resolve_post_ids', 'upload_media', or null
 *   ]
 *
 * Phase 0: empty — all post_type route params are rejected with 400.
 * Phase 1: 'languages' entry added.
 * Phase 2: 'videos', 'captions', 'lexicons' entries added.
 *          'resources' deferred until Airtable/WP count mismatch is resolved.
 *
 * Full field map draft: docs/make-audit-findings.md § 9
 *
 * @return array<string, array<string, array<string, mixed>>>
 */
return array();
