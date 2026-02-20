# Wikitongues – Completed Work

Completed items from [plan.md](plan.md), in reverse chronological order.
Each entry includes branch, PR, merge commit, and a summary of what was done.

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
