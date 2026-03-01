# Airtable → WordPress Sync Infrastructure

This document describes the data integration between Airtable and WordPress for the Wikitongues site. It is intended for contributors, including anyone maintaining Make.com scenarios or extending the plugin.

---

## Table of Contents

- [Overview](#overview)
- [Architecture](#architecture)
- [Make.com Scenarios](#makecom-scenarios)
  - [Shared conventions](#shared-conventions)
  - [Languages](#languages)
  - [Videos (Oral Histories)](#videos-oral-histories)
  - [Captions (Oral History Captions)](#captions-oral-history-captions)
  - [Lexicons](#lexicons)
- [Plugin: wt-airtable-sync](#plugin-wt-airtable-sync)
  - [Authentication](#authentication)
  - [Endpoint](#endpoint)
  - [Upsert logic](#upsert-logic)
  - [Field maps](#field-maps)
  - [Dry-run mode](#dry-run-mode)
  - [Logging](#logging)
- [WordPress Application Passwords](#wordpress-application-passwords)
- [Key Rotation](#key-rotation)
- [WP-CLI Backfill](#wp-cli-backfill)
- [Record Gaps](#record-gaps)
- [Troubleshooting](#troubleshooting)
- [Retired Infrastructure](#retired-infrastructure)
- [Deferred Work](#deferred-work)

---

## Overview

Airtable is the content authoring system. WordPress is the public-facing site. Make.com acts as the transport layer: it watches Airtable for record changes and POSTs the raw payload to a WordPress REST endpoint. The WordPress plugin (`wt-airtable-sync`) owns all field mapping, relationship resolution, and database writes.

**Design principle:** Make.com is a dumb HTTP transport. It does not transform data, resolve relationships, or make business logic decisions. All of that lives in PHP, in version control, and is testable.

---

## Architecture

```
Airtable record modified
        │
        ▼
Make.com scenario (Watch Records trigger, 15-min schedule)
        │
        │  POST /wp-json/wikitongues/v1/sync/{post_type}
        │  Headers: X-WT-Sync-Key, X-WT-Dry-Run
        │  Body: raw Airtable field values (JSON)
        │
        ▼
wt-airtable-sync plugin (WordPress)
        │
        ├─ Authenticate (X-WT-Sync-Key vs WT_SYNC_API_KEY constant)
        ├─ Load field map for post_type (config/field-maps.php)
        ├─ Find existing post (_airtable_record_id → iso_code → title)
        ├─ Create or update WP post
        ├─ Stamp _airtable_record_id postmeta
        └─ Write mapped fields (update_field / update_post_meta)
```

There are **separate Make.com scenario instances for staging and production**. They share the same blueprint structure but have different `wp_base_url` values and different keychains. Never point a production scenario at staging or vice versa.

---

## Make.com Scenarios

Blueprint files are stored at `temp/make.com wt-sync/`. These are exported snapshots — the source of truth is the live Make.com workspace.

### Shared conventions

Every scenario follows the same structural pattern:

1. **Airtable trigger** (`airtable:TriggerWatchRecords`) — watches for records modified since the last run using a `last_modified` formula field. Runs on a 15-minute schedule.
2. **SetVariables module** (`util:SetVariables`) — centralises per-environment config:
   - `dry_run` — `0` for live writes, `1` to preview without touching the DB
   - `wp_base_url` — full origin of the WordPress instance (e.g. `https://wikitongues.org`)
   - `sync_key` is **not** in SetVariables — it lives in a Make.com keychain (API key type, header name `X-WT-Sync-Key`)
3. **HTTP module(s)** — POST to `{{wp_base_url}}/wp-json/wikitongues/v1/sync/{post_type}` with JSON body.

**Record limit (`maxRecords`):** Controls how many Airtable records are processed per run. Make.com uses a cursor — on each run it fetches up to `maxRecords` records modified after the last processed timestamp, then saves the new cursor. If more records have changed than the limit allows, they are processed in subsequent 15-minute runs. Languages is set to 100; other CPTs should be set to 50–100.

### Languages

- **Airtable table:** Languages
- **WP CPT:** `languages`
- **Blueprint file:** `WT Sync - Languages.blueprint.json`
- **Flow:** Trigger → SetVariables → POST `/sync/languages`
- **maxRecords:** 100

### Videos (Oral Histories)

- **Airtable table:** Oral Histories
- **WP CPT:** `videos`
- **Blueprint file:** `WT Sync - Videos.blueprint.json`
- **Flow:** Trigger → SetVariables → BasicRouter

The Videos scenario uses a `builtin:BasicRouter` to branch on whether a thumbnail attachment is present in the Airtable record:

**Route 1 — thumbnail exists:**
1. `http:ActionGetFile` (M25) — downloads the thumbnail binary from the Airtable attachment URL. Filter: thumbnail URL exists.
2. `http:MakeRequest` POST `/wp/v2/media` (M27) — uploads the binary to the WordPress media library. Uses `contentType: custom` (raw binary body — `contentType: json` causes a buffer-to-string conversion error). Authentication: WordPress Application Password (basicAuth keychain). Content-Disposition: `attachment; filename=thumbnail_{{lower(Identifier)}}.{{last(split(type; "/"))}}` — the extension is derived from the Airtable MIME type field, not the original filename, to produce a predictable WordPress media slug.
3. `http:MakeRequest` POST `/sync/videos` (M5) — syncs all video fields. `video_thumbnail_v2` is set to `{{27.data.id}}` (the newly uploaded attachment ID).

**Route 2 — no thumbnail:**
1. `http:MakeRequest` POST `/sync/videos` (M29) — syncs all video fields. `video_thumbnail_v2` is set to `0`, which clears the ACF image field.

**Why not deduplicate media uploads?** Media deduplication (searching by slug before uploading) was considered and prototyped but removed. Since Airtable thumbnail filenames are not guaranteed to be stable or unique, the slug-based dedup search cannot reliably find existing uploads. The current approach always uploads a fresh attachment on each thumbnail sync. Existing attachment posts accumulate in the media library but do not affect front-end display, which always reads the current `video_thumbnail_v2` ACF field value.

### Captions (Oral History Captions)

- **Airtable table:** Oral History Captions
- **WP CPT:** `captions`
- **Blueprint file:** `WT Sync - Captions.blueprint.json`
- **Flow:** Trigger → SetVariables → Resolve Languages → Resolve Videos → Resolve Creators → POST `/sync/captions`

The Captions scenario uses three **subscenarios** (`scenario-service:CallSubscenario`) to resolve linked Airtable record IDs to values the sync endpoint understands before POSTing. This is the correct architectural pattern for resolving linked records and should be adopted for other CPTs as Airtable computed/lookup columns are removed.

- `Resolve Languages` (SCN_3875258) — resolves the linked Language record
- `Resolve Videos` (SCN_3875631) — resolves the linked Video record
- `Resolve Creators` (SCN_3875755) — resolves the linked Creator record

`post_status` is hardcoded to `publish` in the scenario body — captions do not have a status field in Airtable.

### Lexicons

- **Airtable table:** Lexicons
- **WP CPT:** `lexicons`
- **Blueprint file:** `WT Sync - Lexicons.blueprint.json`
- **Flow:** Trigger → SetVariables → POST `/sync/lexicons`
- **Trigger field:** `last_modified` (a `LAST_MODIFIED_TIME()` formula field in Airtable — must exist in the table for the trigger to work)

---

## Plugin: wt-airtable-sync

**Location:** `wp-content/plugins/wt-airtable-sync/`

```
wt-airtable-sync/
├── wt-airtable-sync.php        Plugin bootstrap, hook registration
├── config/
│   └── field-maps.php          CPT field map definitions
└── includes/
    ├── class-sync-api.php      REST route registration and auth
    ├── class-sync-controller.php  Upsert pipeline
    ├── class-field-resolver.php   post_object title → WP post ID resolution
    ├── class-acf-fields.php    Programmatic ACF field registration
    └── class-logger.php        Structured logging wrapper
```

### Authentication

Requests are authenticated via the `X-WT-Sync-Key` request header. The value is compared in constant time against the `WT_SYNC_API_KEY` constant defined in `wp-config.php`.

```php
// wp-config.php
define( 'WT_SYNC_API_KEY', 'your-secret-key-here' );
```

If `WT_SYNC_API_KEY` is absent or empty, the endpoint returns **503** (server misconfiguration, not a client error). An admin notice is shown in the WordPress dashboard.

If the key is wrong or missing from the request, the endpoint returns **401**.

In Make.com, `sync_key` is stored in a **keychain** (connection type: API key; header name: `X-WT-Sync-Key`). It is referenced by ID in each HTTP module's `apiKeyKeychain` parameter. It is never stored in the SetVariables module.

### Endpoint

```
POST /wp-json/wikitongues/v1/sync/{post_type}
```

`post_type` must match a key in `config/field-maps.php`. Currently supported: `languages`, `videos`, `captions`, `lexicons`.

**Request headers:**

| Header | Required | Description |
|---|---|---|
| `X-WT-Sync-Key` | Yes | Shared secret, must match `WT_SYNC_API_KEY` |
| `X-WT-Dry-Run` | No | Set to `1` to preview without writing |
| `Content-Type` | Yes | `application/json` |

**Response (live write):**
```json
{ "status": "ok", "action": "created|updated", "post_id": 12345 }
```

**Response (dry run):**
```json
{
  "status": "dry_run",
  "action": "created|updated",
  "post_id": 12345,
  "post_title": "...",
  "post_status": "publish",
  "would_write": { "_airtable_record_id": "recXXX", "iso_code": "eng", ... }
}
```

### Upsert logic

On every POST, the controller attempts to find an existing WP post using a three-step priority lookup:

1. **`_airtable_record_id` postmeta** — stable, preferred. Matched against the `airtable_id` field in the payload.
2. **`iso_code` postmeta** — languages CPT only. Matched against the `iso_code` field in the payload.
3. **`post_title`** — last resort. Exact title match via `WP_Query` with the `title` parameter.

If a match is found the post is updated; if not, a new post is created. After every write, `_airtable_record_id` is stamped on the post so future syncs use the stable ID path.

**Fields absent from the payload are skipped** — no existing values are cleared unless the field is explicitly included in the request body with an empty/null value. This means partial updates are safe.

### Field maps

`config/field-maps.php` defines which payload keys map to which WordPress meta fields for each CPT. Each entry specifies:

```php
'payload_key' => [
    'meta_key'  => string,   // WP postmeta key
    'acf'       => bool,     // true → update_field(); false → update_post_meta()
    'acf_type'  => string,   // ACF field type (drives resolver selection)
    'post_type' => string|null, // For post_object fields: the target CPT to search
]
```

**Adding a new field:** add an entry to the relevant CPT array in `field-maps.php` and update the Make.com scenario body to include the new payload key. No other code changes required.

**post_object fields** receive post titles (or comma-separated titles) from Make.com. `Field_Resolver::resolve()` converts them to WP post IDs via `WP_Query` before writing. Unresolved titles are logged as errors and skipped.

**Current field maps by CPT:**

| CPT | Scalar fields | post_object fields |
|---|---|---|
| `languages` | standard_name, alternate_names, nations_of_origin, writing_systems, linguistic_genealogy, iso_code, glottocode, olac_url, wikipedia_url | speakers_recorded (→ videos), lexicon_source (→ lexicons), lexicon_target (→ lexicons), external_resources (→ resources) |
| `videos` | video_title, video_description, video_license, license_link, public_status, dropbox_link, wikimedia_commons_link, youtube_publish_date, youtube_id, youtube_link, video_thumbnail_v2, metadata | featured_languages (→ languages) |
| `captions` | creator, file_url | source_video (→ videos), source_language (→ languages) |
| `lexicons` | dropbox_link, external_link | source_languages (→ languages), target_languages (→ languages) |

### Dry-run mode

Send `X-WT-Dry-Run: 1` to execute all read-only steps (post lookup, post_object title resolution) without making any database writes. The response includes `would_write` — the exact meta values that would have been written.

Use dry-run to validate a new Make.com payload shape against staging or production before enabling live writes. The `dry_run` SetVariables field in every Make.com scenario controls this. Set it to `0` for live operation.

### Logging

The plugin writes structured log entries via `class-logger.php`. Log output goes to the standard PHP error log, visible in:
- **GreenGeeks:** cPanel → Logs → Error Log
- **Local:** `php_error.log` or the MAMP logs directory

Log format: `[wt-sync] {level}: {message}`

---

## WordPress Application Passwords

The Videos scenario uploads thumbnails directly to the WordPress media library via the WP REST API (`POST /wp/v2/media`). This requires a WordPress **Application Password** (separate from the `X-WT-Sync-Key` used by the sync endpoint).

Application Passwords are created at: WordPress Admin → Users → Edit User → Application Passwords.

In Make.com, the Application Password is stored as a **basicAuth keychain** (username = WordPress username, password = Application Password). It is referenced in the `basicAuthKeychain` parameter of the `http:MakeRequest` media upload module.

The sync endpoint (`X-WT-Sync-Key`) and the media upload Application Password are **independent credentials** — rotating one does not affect the other.

---

## Key Rotation

### Rotating `WT_SYNC_API_KEY`

1. Generate a new key (e.g. `openssl rand -hex 32`)
2. Update `wp-config.php` on production (and staging if applicable): `define( 'WT_SYNC_API_KEY', 'new-key' );`
3. In Make.com, update the keychain that holds `X-WT-Sync-Key` for each affected scenario instance (staging and production are separate keychains)
4. Verify by running a scenario manually in dry-run mode and confirming a 200 response

### Rotating the WordPress Application Password (media upload)

1. In WordPress Admin → Users → Edit User → Application Passwords, revoke the old password and generate a new one
2. In Make.com, update the basicAuth keychain used by the Videos scenario's media upload module (M27)

---

## WP-CLI Backfill

When new CPT sync scenarios go live, existing WordPress posts don't yet have `_airtable_record_id` stamped. Without it, the upsert falls back to title matching, which is fragile. The backfill stamps `_airtable_record_id` on existing posts using a CSV export from Airtable.

**Process:**

1. Export the Airtable table as CSV with two columns: `identifier` (post title) and `record_id` (Airtable record ID)
2. Run the WP-CLI importer:

```bash
wp --require=../temp/importer/acf-importer.php acf-import import-csv \
  --file=../temp/importer/data/airtable-ids.csv \
  --post-type=languages \
  --key=identifier \
  --fields=_airtable_record_id \
  --log-file=../temp/importer/logs/airtable-ids.txt \
  --delimiter=, \
  --field-types=text
```

Replace `--post-type` with the target CPT. `--field-types=text` is required for underscore-prefixed meta keys (writes via `update_field()`).

**Production backfill results (2026-03-01):**

| CPT | Stamped | Not found |
|---|---|---|
| languages | 8,088 | 2 (`wyim`, `wyug` — absent from WP) |
| videos | 1,853 | 3 (2 HTML-entity title encoding artifacts; 1 post-export new record) |
| captions | 257 | 60 (absent from WP) |
| lexicons | 20 | 130 (absent from WP — only 22 of 152 Airtable lexicon records had WP posts) |

**"Not found" does not mean data loss.** When Make.com next triggers on a not-found record, the upsert falls back to title match and then creates a new WP post if no match is found. The gap closes organically as records are modified in Airtable. To force-close it: bulk-touch the missing Airtable records (e.g. update a non-critical field) to fire the trigger across the full set.

---

## Record Gaps

Some Airtable records have no corresponding WordPress post. This is expected for CPTs where data was added to Airtable after the initial WP import, or where the old Make.com scenarios never synced them.

The new sync infrastructure handles gaps correctly: on first trigger, the upsert creates the WP post. Once created, `_airtable_record_id` is stamped and subsequent syncs use the stable ID path.

The lexicons CPT has the largest gap: as of 2026-03-01, 130 of 152 Airtable lexicon records had no WP post. These will be created progressively as records are modified or force-touched.

---

## Troubleshooting

**Scenario runs but post is not updated:**
- Check Make.com execution log for the HTTP response from the sync endpoint
- 401: `X-WT-Sync-Key` is wrong or the keychain is out of date
- 503: `WT_SYNC_API_KEY` is not defined in `wp-config.php`
- 400 with `wt_sync_unsupported_post_type`: the `post_type` in the URL does not match a key in `field-maps.php`
- 400 with `wt_sync_empty_payload`: the request body was empty or not valid JSON

**Post is created instead of updated (duplicates):**
- The post does not yet have `_airtable_record_id` stamped. Run the WP-CLI backfill, or wait for the sync to self-correct after the first run (which will stamp the ID).
- The `airtable_id` field in the Make.com payload is empty. Check the Airtable trigger module — the record ID field (`id`) must be mapped to `airtable_id` in the HTTP body.

**Videos scenario fails at media upload (M27) with 403:**
- The WordPress Application Password keychain in Make.com is stale. Re-enter the credentials.

**Videos scenario fails at media upload with "not allowed to upload this file type":**
- The `contentTypeValue` expression is returning an unrecognised MIME type. Check `1.'Raw Thumbnail'[].type` in the Make.com execution log.

**post_object field not resolving (Field_Resolver error in logs):**
- The title sent from Make.com does not exactly match any WP post title in the target CPT. This is usually a subscenario data mismatch in Captions, or a stale Airtable computed field value. Check the Airtable record and the WP post title directly.

**Trigger not firing on modified records:**
- The Airtable table must have a `LAST_MODIFIED_TIME()` formula field, and that field name must match the `triggerField` value in the Make.com trigger module. Lexicons uses `last_modified`; verify the field exists in Airtable.

---

## Retired Infrastructure

### integromat-connector (old sync plugin)

The `integromat-connector` WordPress plugin handled the old Make.com → WordPress write path. It exposed REST endpoints that Make.com called using the `wordpress:createMediaItem` and related modules. These paths were invalidated by the PHP 8.2 upgrade and have been replaced by `wt-airtable-sync`.

The old Make.com scenario instances (v1) were disabled on 2026-03-01. The `integromat-connector` plugin remains installed but is no longer called by any active Make.com scenario.

### post-object-helpers.php

`wp-content/themes/blankslate-child/includes/post-object-helpers.php` (and the duplicate at `includes/post-object-helpers.php`) was the handler for the old sync pattern. The old workflow wrote `_WT_TMP_{field_name}` staging keys to `wp_postmeta` with raw Airtable title strings, then called `handle_post_object()` to resolve them to WP post IDs.

Both this function and the `_WT_TMP_*` keys are now retired:
- All 3,376 `_WT_TMP_*` rows were deleted from `wp_postmeta` on 2026-03-01
- `post-object-helpers.php` is dead code — it should be removed during the code quality cleanup of `includes/`

### _WT_TMP_* postmeta keys

These were temporary staging keys written by the old Make.com scenarios. They have been fully cleaned up. No new keys of this pattern are written by any active code path.

---

## Deferred Work

- **`resources` CPT** — deferred from the initial sync rollout. Airtable has 204 resources records but WordPress has ~907 `resources` posts, suggesting significant data divergence. Requires reconciliation before sync can be enabled safely. See `plan.md` and `docs/make-audit-findings.md` § F3.
- **Airtable table bloat** — the Videos table has 188 fields, most computed or lookup. The correct long-term architecture is to resolve linked records in Make.com subscenarios (as Captions already does), then delete the Airtable computed columns. Do not add more Airtable lookup fields to support sync.
- **Phase 3 cleanup** — `post-object-helpers.php` removal is tracked under Code Quality in `plan.md`.
- **Deletion propagation** — when a record is deleted in Airtable, no event fires to WordPress. The recommended approach is a soft-delete convention (set `post_status` to `trash` in Airtable before deleting the record) for the sync to propagate. A hard-delete endpoint (`DELETE /wp-json/wikitongues/v1/sync/{post_type}?airtable_id={id}`) is not yet implemented.
