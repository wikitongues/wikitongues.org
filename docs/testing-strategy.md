# Wikitongues – Testing Strategy

Goal: professional-grade coverage — no visual regressions, no behavior breakage, no security gaps.
Coverage is built in layers, from fast/cheap to slow/comprehensive.

Layers 1–2 are complete and run on every PR. Layers 3–5 are planned for Phases 3–6.

---

## Layer 1 — Static Analysis ✅

**Tools:** PHPCS + WordPress Coding Standards, ESLint, PHPStan
**Catches:** coding standards violations, basic security anti-patterns (unescaped output, direct DB queries), JS style issues, type-safety violations
**Runs:** on every PR via GitHub Actions

**PHPCS** enforces the WordPress Coding Standards ruleset across the theme and `wt-gallery` plugin. One class per file enforced. Security sniffs catch unescaped output and direct `$wpdb` queries.

**PHPStan** at level 5 with `szepeviktor/phpstan-wordpress` stubs. A baseline of pre-existing violations lives in `phpstan-baseline.neon`; CI fails only on new violations introduced after the baseline. To regenerate: temporarily remove the baseline include from `phpstan.neon`, run `composer analyse --generate-baseline`, then re-add it.

---

## Layer 2 — Unit Tests ✅

**Tools:** PHPUnit 9.6 + WP_Mock 1.1
**Catches:** regressions in isolated business logic — URL encoding, meta value fallbacks, search routing regex, pagination math
**Runs:** on every PR via GitHub Actions
**Does not cover:** templates, DB queries, actual rendering, hook/filter wiring

### Covered functions

| File | Functions |
|---|---|
| `import-captions.php` | `safe_dropbox_url()`, `get_safe_value()` |
| `acf-helpers.php` | `wt_meta_value()` |
| `search-filter.php` | `searchfilter()` regex routing |
| `render_gallery_items.php` | `generate_gallery_pagination()` |
| `wt-gallery/helpers.php` | `getDomainFromUrl()` |
| `template-helpers.php` | `get_environment()`, `wt_prefix_the()` |
| `events-filter.php` | `format_event_date_with_proximity()` |
| `wt-gallery/includes/queries.php` | `build_gallery_query_args()` (10 tests) |

Any new function with non-trivial logic should ship with a unit test.

### Deferred unit test candidates

- **Territory name list formatter** — the Oxford-comma builder in `single-languages.php` has real edge-case risk (1 item / 2 items / 3+ items). If extracted into a named helper (e.g. `wt_format_list()`), it becomes a trivially testable pure function.
- **`GalleryQueryArgsTest` regression guard** — assert that `linguistic_genealogy` is absent from the `in_array` exact-match list, mirroring the `writing_systems` removal test deleted in PR #467.
- **Archive parameter resolution** (`archive-languages.php`, `archive-territories.php`) — deferred to Layer 3; mixes `$_GET`, `get_term_by()`, and `create_gallery_instance()` in a way that requires a running WP instance.
- **Template rendering** (`single-languages.php`) — deferred to Layer 4 (E2E); territory ID merging and gallery title logic are embedded in the template alongside `get_field()` calls.
- **`GalleryQueryArgsTest` — `include_children`** — assert that `tax_query` for the `region` taxonomy does not set `include_children => false`, documenting the intentional reliance on WP's default behaviour for the `archive-territories.php` continent filter path.

### Constraints and upgrade path

WP_Mock 1.x uses [Patchwork](https://github.com/antecedent/patchwork) to redefine global PHP functions at runtime, which is fundamentally at odds with how PHPUnit 10+ works internally. The entire WordPress unit testing ecosystem (WP_Mock, Brain Monkey) is locked to PHPUnit ^9.6, which is in maintenance mode (security fixes only).

The forward-looking exit is not to wait for WP_Mock to catch up — it's to reduce the surface area of WP function mocking. Functions that receive `is_admin()` or `get_query_var()` results as arguments rather than calling them directly need no mocking at all and can be tested with plain PHPUnit against any version. The refactor direction: **push WP API calls to the edges of functions**, keeping the logic core pure. This is both a testability improvement and an architectural improvement (separation of concerns).

As functions are refactored to be purer, WP_Mock can be removed from individual test classes incrementally. When WP_Mock is no longer needed by any test, we can upgrade to PHPUnit 10+ and drop the dependency entirely.

---

## Layer 3 — Integration Tests (Phase 5)

**Tools:** PHPUnit + `WP_UnitTestCase` (official WordPress test suite)
**Catches:** hook/filter wiring, CPT registration, REST endpoint responses, DB reads/writes, query correctness
**Covers templates indirectly:** tests the functions templates call, not the template files themselves
**Requires:** MySQL test database in CI (Docker service)

**Priority targets:**
- Custom REST endpoints (`rest-endpoints.php`)
- `get_custom_gallery_query()` query logic
- `searchfilter()` end-to-end with real WP_Query
- CPT and taxonomy registration
- `archive-territories.php` `?region=` param resolution (including continent slug with child term expansion)

---

## Layer 4 — End-to-End & Visual Regression (Phase 6)

**Tools:** Playwright
**Catches:** full user flows, JS behaviour, authenticated vs. unauthenticated states, visual layout regressions (screenshot diffs)
**This is the right layer for testing templates** — a real browser hits real page URLs; no WP internals to mock
**Requires:** running WP instance in CI (Docker Compose with WP + MySQL + seeded content)

**Priority flows:**
- Language search (ISO / glottocode / generic term)
- Language page render
- Video page render
- Gallery pagination
- Admin-restricted pages return 403

**Visual regression:** screenshot baseline per key page template; diff on every PR. Catches CSS/layout changes that behaviour assertions miss. Once established, nothing that changes template output should land without a deliberate baseline update.

---

## Layer 5 — Data Integrity (Phase 3)

**Tools:** WP-CLI custom command, server cron or GitHub Actions scheduled workflow
**Catches:** duplicate iso_codes/standard_names, missing required ACF fields, slug/iso_code mismatches, Airtable→WP record gaps
**Runs:** weekly scheduled job; logs results; reports violations (log file + optional GitHub issue or admin notice)
**Does not replace:** Airtable reconciliation (Phase 5) — complements it by catching problems that slip through to WordPress

### Priority checks

- No two published language posts share the same `iso_code` ACF value
- No two published language posts share the same `standard_name` / `post_title`
- No published language post has a blank `iso_code`
- `post_name` (URL slug) matches `iso_code` for all published language posts — mismatch causes silent routing failures like the wblu/blu bug
- **Airtable → WP record gap** — cross-check each CPT against the Airtable API to identify records with no corresponding WP post. Production backfill (2026-03-01) confirmed: 2 languages, ~3 videos (likely encoding artifacts), 60 captions, 130 lexicons absent from WP. Output: list of Airtable record IDs with no matching `_airtable_record_id` in WP.

### Implementation

- WP-CLI command (`wp wt integrity check`) registered in a new `includes/cli/` file in the theme
- Queries the DB directly via `$wpdb`; outputs a structured report (pass/fail per check, violation count, sample offending records)
- Server cron: `wp wt integrity check >> /path/to/integrity.log 2>&1`
- GitHub Actions `schedule` workflow (weekly) can SSH to staging and run the command, posting results as a job summary
- Violations do not block deploys — this is a monitoring/alerting layer, not a gate
