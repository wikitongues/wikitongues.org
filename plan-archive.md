# Wikitongues – Completed Work

Completed items from [plan.md](plan.md), in reverse chronological order.
Each entry includes branch, PR, merge commit, and a summary of what was done.

---

## 2026-02-21 (Tier 2 — Plugin hygiene + quick wins, partial)

### Audit `integromat-connector` REST API exposure
**Branch:** `chore/cc/delete-wt-form-and-plan-updates`
**PR:** (pending)

Read all plugin source files and queried the DB for opted-in fields.

**Findings:**
- v1.5.9 (Make Connector by Celonis s.r.o.) — third-party plugin; not tracked in git
- Token (`iwc_api_key`, 32-char) is active in DB; no expiry; no rotation performed
- Authentication: `HTTP_IWC_API_KEY` header → `wp_set_current_user($admin_id)` (administrator-level access)
- Guard scope: only protects core WP entities (posts/users/comments/tags/categories/media) on POST/PUT/DELETE; custom post type endpoints (languages, videos, fellows, territories) are not additionally gated by the plugin
- **No ACF fields or custom taxonomies are opted in** (`integromat_api_options_post` / `integromat_api_options_taxonomy` absent from DB)
- Implication: Make.com writes raw `wp_postmeta` keys directly, bypassing ACF hooks and validation

**Follow-on:** Audit Make.com scenarios (Tier 3) to inventory active workflows and determine which ACF fields need to be opted in for a production-quality integration.

---

### Delete wt-form plugin
**Branch:** `chore/cc/delete-wt-form-and-plan-updates`
**PR:** (pending)

`wt-form` was a prototype download gate for the Revitalization Toolkit. Both its Airtable and Mailchimp integration methods began with `return;` and never ran. The `[wikitongues_form]` shortcode was confirmed absent from all published content. Plugin folder deleted.

Also corrected the plan entry for `integromat-connector`: it is the official Make Connector plugin by Make.com (Celonis s.r.o.), not custom code. Active API token confirmed in DB. Plan updated from "Track in VCS" to "Audit REST API exposure".

---

### Gallery `link_out` param
**Branch:** `feature/cc/gallery-link-out`
**PR:** [#462](https://github.com/wikitongues/wikitongues.org/pull/462)

Added `link_out` param to `custom_gallery` shortcode and `create_gallery_instance()`. When set, the `wt_sectionHeader` renders as `<a href="{link_out}">` instead of `<strong>`. URL passes through `esc_url()` at both the passthrough and render points. No behaviour change when param is empty.

Part 2 (archive templates with `?territory=` / `?language=` filter params) is a follow-on — `archive-fellows.php`, `archive-languages.php`, and `archive-videos.php` do not yet exist.

---

## 2026-02-21 (Tier 1 — Security foundation, partial)

### Secrets scanning
**Branch:** `feature/cc/tier-1-security`
**PR:** (pending)

_Note: WPScan was planned for this tier but removed — the WPScan API is no longer free. Plugin/theme vulnerability monitoring deferred to a server-side tool (Patchstack or Wordfence)._

Two-layer secrets scanning: GitHub-native for zero-maintenance push protection, TruffleHog as the PR gate.

Changes:
- **GitHub repository settings:** Native secret scanning and push protection enabled via API. Vulnerability alerts also enabled. Runs on every push; blocks pushes containing known credential patterns without any workflow required.
- **`.github/workflows/security.yml`:** TruffleHog action (pinned to SHA `7c0734f` = v3.93.4) runs on every PR to main. `--only-verified` eliminates false positives. Scans PR diff only (base → head SHA).

Notes: Update the SHA comment in the workflow when upgrading. One-time full history audit: `trufflehog git file://. --only-verified` run locally.

---

## 2026-02-21

### Branch audit and cleanup
**Type:** Maintenance (no PR)
**Date:** 2026-02-21

Audited all branches not owned by `cc/` automation. Three `_`-prefixed branches remained after all cc/ branches were auto-deleted on PR merge:

- **`_feature/language-search-improvements`** — Orphaned search overlay, never wired up; superseded by `search-filter.php`. Deleted.
- **`_fix/fix-thumbnails`** — Three files at wrong root-level `modules/` path, superseded by organised subdirectory versions (`modules/search/`, `modules/videos/`). Deleted.
- **`_hotfix/rclone-cicd-action`** — Kept intentionally for potential future rsync/rclone CI work.

---

## 2026-02-20

### Fellows ↔ Territories — bidirectional linking
**Branches:** `feature/cc/fellows-territories-linking`, `fix/fellow-territory-style`, `fix/cc/fellows-territory-comma-separated`
**PRs:** [#450](https://github.com/wikitongues/wikitongues.org/pull/450), [#451](https://github.com/wikitongues/wikitongues.org/pull/451), [#452](https://github.com/wikitongues/wikitongues.org/pull/452), [#455](https://github.com/wikitongues/wikitongues.org/pull/455)
**Merged & deployed to production:** 2026-02-20

Added a `fellow_territory` ACF field to Fellows posts and wired up bidirectional display on fellow, territory, region, and continent pages.

Changes:
- **`acf-json/group_624f529b40c49.json`:** New `post_object` field `fellow_territory` — type `territories`, `multiple: 1`, `allow_null: 1`, `return_format: object`. Editors can associate one or more territories with a fellow.
- **`single-fellows.php`:** `$fellow_territory = get_field('fellow_territory')` passed into scope for the meta module.
- **`modules/fellows/meta--fellows-single.php`:** Territory link(s) rendered before `banner_copy` as a single `<p class="fellow-territory">` with comma-separated `<a>` anchors when multiple territories are set. Uses `wt_prefix_the()` for correct display ("the Bahamas", "the Americas").
- **`single-territories.php`:** Restructured to sidebar + `<main>` layout (matching single-language page). Fellows gallery (3 cols, 6 posts, paginated) appears first, then languages gallery (3 cols, 6 posts, paginated). Fellows pre-queried with `LIKE '"id"'` meta_query to match ACF-serialised multiple post_object values.
- **`taxonomy-region.php`:** Same layout restructure. Fellows aggregated across all territory IDs in the region via OR LIKE meta_query; languages aggregated via existing `selected_posts`. Applied to both region and continent pages (continent pages already expand child term IDs).
- **`tests/unit/PrefixTheTest.php`:** 8 unit tests for `wt_prefix_the()` — covers all five prefixed names (Americas, Caribbean, Sahel, Gambia, Bahamas), ordinary names, empty string, and case-sensitivity. Total test count: 56 tests, 89 assertions.

Notes: ACF serialises `post_object` arrays as `a:2:{i:0;s:3:"42";i:1;s:3:"99";}` — integer IDs stored as quoted strings. Meta queries must use `LIKE '"id"'` (not `= id` with `NUMERIC`) to match these values.

---

### Territories CPT — "the" prefix generalisation
**Branch:** `fix/cc/territories-prefix-the`
**PR:** [#446](https://github.com/wikitongues/wikitongues.org/pull/446)
**Merged & deployed to production:** 2026-02-20

Centralised the "the Americas / the Bahamas / etc." prefix logic that was previously duplicated (with inconsistent name lists and capitalisation) across four territory template modules.

Changes:
- **`template-helpers.php`:** New `wt_prefix_the( string $name ): string` helper — covers Americas, Caribbean, Sahel, Gambia, Bahamas; returns lowercase `'the ' . $name`
- **`single-territories.php`:** `$territory = wt_prefix_the( get_the_title() )` — fixes h1 and gallery title (e.g. "Bahamas" → "the Bahamas")
- **`taxonomy-region.php`:** `$territory = wt_prefix_the( $current_region->name )` — fixes region page h1
- **`territories-active-region.php`, `territories-child-regions.php`, `territories-parent-regions.php`, `territories-sibling-regions.php`:** All inline `in_array` prefix checks replaced with `wt_prefix_the()` calls
- **`meta--languages-single.styl`:** `text-transform: capitalize` on territory h1 and sidebar list links so lowercase "the" displays correctly
- **`phpstan-baseline.neon`:** Regenerated (446 errors) after removing dead commented-out code that had been counted in prior baseline

---

### Territories CPT — initial feature
**Branch:** `feature/nations-post-type`
**PR:** [#445](https://github.com/wikitongues/wikitongues.org/pull/445)
**Merged & deployed to production:** 2026-02-20

Introduced the `territories` custom post type and `region` hierarchical taxonomy, with a two-level continent → sub-region hierarchy.

Changes:
- **`includes/custom-post-types/territories.php`:** CPT registration; custom permalink `/territories/{region}/{territory}/`; rewrite rules; removed duplicate `add_filter` call; strict comparisons throughout
- **`taxonomy-region.php`:** Region archive template; continent pages expand `tax_query` to include all child sub-region term IDs and pass `selected_posts` to gallery (single `term` slug insufficient for multi-region continent queries)
- **`single-territories.php`:** Territory single template; `get_field('languages', id, false)` returns raw IDs rather than hydrated `WP_Post` objects — critical for large territories (India: 403 languages)
- **`modules/territories/`:** Four sidebar modules — `territories-active-region`, `territories-child-regions`, `territories-parent-regions`, `territories-sibling-regions`
- **`temp/territories/import-territories.php`:** WP-CLI `wp eval-file` import script; builds continent → sub-region hierarchy; idempotent (`wp_update_term` fixes missing parent on re-run); uses `__DIR__` path (WP-CLI rejects `--file` and `--tsv` as unknown params)
- **`phpstan-baseline.neon`:** Regenerated to include territory template files (449 errors at time of merge)

Notes: Territories data must be (re-)imported after each deploy with `wp eval-file temp/territories/import-territories.php`. Flush rewrite rules after first activation with `wp rewrite flush`.

---

## 2026-02-19

### Low-hanging unit tests
**Branch:** `feature/cc/unit-tests-low-hanging`
**PR:** [#441](https://github.com/wikitongues/wikitongues.org/pull/441)
**Merged:** `aff4d8d` — 2026-02-19

Added unit tests for three pure-logic functions that needed no DB:
- `getDomainFromUrl()` in `wt-gallery/helpers.php` — 5 tests (www stripping, subdomain preservation, path/query ignored)
- `get_environment()` in `template-helpers.php` — 4 tests (sets/restores `$_SERVER['HTTP_HOST']`)
- `format_event_date_with_proximity()` in `events-filter.php` — 6 tests (far future/past, ±3 days, today, output format)

Notes: `setUp()`/`tearDown()` must be `public` in `WP_Mock\Tools\TestCase` subclasses. Added `wp_date()` bootstrap shim to handle `events-filter.php`'s file-scope `get_current_datetime()` call before WP_Mock is active. Fixed PHPCS `date()` → `gmdate()` violations throughout.

---

### Raw SQL refactor — `wt-gallery` featured languages
**Branch:** `fix/cc/raw-sql-featured-languages`
**PR:** [#438](https://github.com/wikitongues/wikitongues.org/pull/438)
**Merged:** `3996593` — 2026-02-19

Removed the `$wpdb->prepare()` raw SQL block from `get_custom_gallery_query()` in `queries.php`. The `featured_languages` branch was building a dynamic `IN ($placeholders)` clause by looking up language post IDs from `post_title` — an unsafe and brittle approach requiring a `phpcs:disable` suppression.

Changes:
- **`queries.php`:** Removed 35-line raw SQL block; `featured_languages` branch now treats `meta_value` as comma-separated post IDs directly (9 lines). Extracted `build_gallery_query_args($atts)` as a pure testable function; `get_custom_gallery_query()` reduced to one line. Removed a dead first `new WP_Query()` call whose result was discarded. Fixed stale `@param $args` docblock.
- **`single-languages__videos.php`:** `'meta_value' => get_the_title()` → `get_the_ID()`
- **`single-videos.php`:** Changed from passing ISO codes (broken — they were matched against `post_title`) to passing language post IDs directly. Fixed a silent bug where video galleries on language pages always returned empty results.
- **`tests/unit/GalleryQueryArgsTest.php`:** 10 new unit tests covering all `meta_key` branches of `build_gallery_query_args()`.

---

### PHPStan static analysis (Phase 4)
**Branch:** `feature/cc/phase-4-phpstan`
**PR:** [#435](https://github.com/wikitongues/wikitongues.org/pull/435)
**Merged:** `29151f8` — 2026-02-19

Added PHPStan level 5 type-safety analysis with WordPress stubs.

Changes:
- **`composer.json`:** Added `phpstan/phpstan ^1.0` and `szepeviktor/phpstan-wordpress ^1.0` to `require-dev`; added `"analyse"` script with `--memory-limit=-1` (child processes crash at PHP's default 128M)
- **`phpstan.neon`:** New config — level 5, paths cover theme + wt-gallery plugin + typeahead.php + tests; includes WordPress extension and baseline
- **`phpstan-baseline.neon`:** Generated baseline of 424 pre-existing violations; CI fails only on new errors introduced after baseline
- **`.github/workflows/lint.yml`:** Added `phpstan` job after `lint-js`
- **`.rsync-filter`:** Excluded `phpstan.neon` and `phpstan-baseline.neon` from deploy sync

Notes: baseline regeneration required temporarily removing the baseline include from `phpstan.neon`, running `composer analyse --generate-baseline`, then re-adding it. Required after any refactor that changes violation count.

---

### Unit testing infrastructure (Phase 3)
**Branch:** `feature/cc/phase-3-unit-tests`
**PR:** [#432](https://github.com/wikitongues/wikitongues.org/pull/432)
**Merged:** `93c0fff` — 2026-02-19

Established PHPUnit + WP_Mock testing infrastructure and wrote the first batch of unit tests.

Setup:
- PHPUnit 9.6 + WP_Mock 1.1 (`require-dev`); `composer test` script; `phpunit.xml.dist`
- `tests/bootstrap.php` — WP_Mock init, requires for all testable includes, support class includes
- `tests/` and `phpunit.xml.dist` excluded from rsync (`.rsync-filter`); `tests/` added to PHPCS scope
- `.github/workflows/test.yml` — PHPUnit job on every PR

Tests written (initial batch):
- `safe_dropbox_url()`, `get_safe_value()` — `import-captions.php`
- `wt_meta_value()` — `acf-helpers.php`
- `searchfilter()` regex routing — `search-filter.php`
- `generate_gallery_pagination()` — `render_gallery_items.php`

Notes: WP_Mock 1.x is locked to PHPUnit ^9.6 due to Patchwork incompatibility with PHPUnit 10+. Upgrade path: push WP API calls to function edges, reducing mocking surface, then drop WP_Mock incrementally. See plan.md Layer 2 for full constraint details.

---

### Static analysis — PHPCS + ESLint (Phase 2)
**Branch:** `feature/cc/phase-2-code-quality`
**PR:** [#428](https://github.com/wikitongues/wikitongues.org/pull/428)
**Merged:** `6887458` — 2026-02-19

Added PHPCS with WordPress Coding Standards and ESLint to CI.

- **`composer.json`:** Added `squizlabs/php_codesniffer`, `wp-coding-standards/wpcs`, `phpcsstandards/phpcsutils` to `require-dev`; added `"lint"` script
- **`.phpcs.xml`:** Config covering theme and wt-gallery plugin with WPCS ruleset
- **`.github/workflows/lint.yml`:** `lint-php` and `lint-js` jobs on every PR

---

### CI and deployment pipeline (Phase 1)
**Branch:** `feature/cc/phase-1-operational`
**PR:** [#423](https://github.com/wikitongues/wikitongues.org/pull/423)
**Merged:** `9361c5f` — 2026-02-19

Established the foundational CI/CD pipeline: GitHub Actions workflows, rsync-based deployment to staging and production, `.rsync-filter` exclusion rules, and baseline project hygiene (`.gitignore`, `composer.json` platform pinning to PHP 8.2 for CI compatibility).

---

### CI fix — SSH agent version
**Branch:** `fix/cc/ssh-agent-version`
**PR:** [#425](https://github.com/wikitongues/wikitongues.org/pull/425)
**Merged:** `b9be0b4` — 2026-02-19

Minor fix to SSH agent action version in deployment workflow.

---

## 2026-02 (data fix, no PR)

### w-prefixed language ID routing — wblu/blu collision
**Type:** Production data fix
**Date:** 2026-02-20

**Root cause:** 19 language posts had `post_title` values that collided with existing ISO codes. For example, the Bildts language (`post_name = wblu`) had `post_title = 'blu'` and ACF fields set to `blu` values, causing `/languages/wblu` to render content for Hmong Njua instead of Bildts. WordPress was routing to the correct post by slug, but all displayed content belonged to the wrong language.

**Fix:** Direct data correction on production — titles, iso_code, standard_name, and related ACF fields updated for all 19 affected posts. Database pulled from production to staging and local.

**Prevention:** Layer 5 — Data Integrity (see plan.md) will catch duplicate iso_codes and standard_names on a weekly schedule before they cause routing failures.
