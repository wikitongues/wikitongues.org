# Wikitongues – Technical Debt & Improvement Plan

This file tracks known issues, deferred refactors, and planned improvements.
Items are grouped by area and roughly ordered by priority within each group.
Completed work is documented in [plan-archive.md](plan-archive.md).

---

## Table of Contents

- **[Backlog](#backlog)**
  - [x] Convert plan.md to checklist format
  - [ ] Dockerize project
  - [ ] Audit and clean up stale branches
  - [ ] Airtable reconciliation (520+ records missing fields)
  - [x] Fix w-prefixed language routing — wblu/blu ([archive](plan-archive.md))
  - [ ] Complete Donors post type
  - [ ] Link Fellows to Territories and vice versa
- [ ] Migrate `nations_of_origin` on language posts from text → territories relationship field — intentionally deferred; `Also spoken in` (the `territories` ACF relationship field) serves as the linked alternative in the sidebar. Migration requires changing the ACF field type, updating the make.com sync, and backfilling data.

- **[Code Quality](#code-quality)**
  - [x] Refactor raw SQL in `wt-gallery` ([archive](plan-archive.md))

- **[Plugins](#plugins)**
  - [ ] Track `wt-form` in version control
  - [ ] Track `integromat-connector` in version control

- **[Infrastructure](#infrastructure)**
  - [ ] Migrate from Stylus
  - [ ] Replace Font Awesome
  - [ ] Performance profiling and monitoring

- **[Testing Strategy](#testing-strategy)**
  - [x] Layer 1 — Static Analysis, Phase 2 ([archive](plan-archive.md))
    - [x] PHPStan, Phase 4 ([archive](plan-archive.md))
  - [x] Layer 2 — Unit Tests, Phase 3 ([archive](plan-archive.md))
  - [ ] Layer 3 — Integration Tests, Phase 5
  - [ ] Layer 4 — End-to-End & Visual Regression, Phase 6
  - [ ] Layer 5 — Data Integrity, Phase 7
  - **[Security](#security)**
    - [x] PHPCS security sniffs ([archive](plan-archive.md))
    - [ ] WPScan in CI
    - [ ] Secrets scanning
    - [ ] Security review of `wt-form` and `integromat-connector`

---

## Backlog

- [ ] **Dockerize project** for ease of contributor setup
- [ ] **Audit and clean up stale branches**
- [ ] **Airtable reconciliation** — 520+ language records missing essential fields. make.com syncs from Airtable without field guarantees; records arrive in WordPress incomplete. Rather than enforcing hard requirements at the WordPress layer, reconciliation should happen at the Airtable source: institute field requirements there and handle any divergence before sync.
- [ ] **Complete Donors post type** (in progress, stalled)
- [ ] **Link Fellows to Territories and vice versa**
  Fellows posts should display the territory they are associated with. Territory pages should display a gallery of Fellows from that territory.
  **Goal:** Add a territory relationship field to Fellows posts (or derive it from existing data); render the territory link on single-fellow pages; add a Fellows gallery block to `single-territories.php`.

---

## Code Quality

_All items complete. See [plan-archive.md](plan-archive.md)._

---

## Plugins

- [ ] **Track `wt-form` in version control** when feature work resumes
  **File:** `wp-content/plugins/wt-form/`
  Currently excluded from git. Plugin handles form submissions to Airtable and Mailchimp. Not actively used; excluded from linting scope for now.
  **Goal:** Add to `.gitignore` allowlist, include in PHPCS scan, review security (form validation, API key handling).

- [ ] **Track `integromat-connector` in version control** when automation work resumes
  **File:** `wp-content/plugins/integromat-connector/`
  Currently excluded from git. Custom Make.com connector with API token auth. Not in active development; excluded from linting scope for now.
  **Goal:** Add to `.gitignore` allowlist, include in PHPCS scan, review API token handling and REST endpoint security.

---

## Infrastructure

- [ ] **Migrate from Stylus to a maintained CSS preprocessor** (PostCSS or Sass)
  Stylus is largely unmaintained. Its dependency chain (`glob@7` → `minimatch@3`) has known ReDoS vulnerabilities (dev-only, no production impact). `npm audit` flags 3 high-severity findings with no clean in-place fix.
  **Goal:** Migrate to PostCSS or Sass. Resolves audit findings and improves long-term maintainability of the CSS build pipeline.

- [ ] **Replace Font Awesome**
  Font Awesome is loaded as an external dependency (CDN or npm package). It adds weight to every page load for a relatively small set of icons actually used. Replacing with inline SVGs or a purpose-built icon set (e.g. Heroicons, Phosphor) would reduce load time and remove the external CDN dependency.
  **Goal:** Audit which FA icons are in use, replace with lightweight inline SVGs or a self-hosted sprite, remove the FA dependency entirely.

- [ ] **Performance profiling and monitoring**
  No visibility into page load times or query performance in production. Known risk areas already identified: territory pages with large language counts (India: 403 languages, China: 249, Brazil: 200, USA: 197) and continent-level region pages aggregating many territories. `get_field()` returning full post objects on relationship fields at scale is the primary pattern to watch.
  **Goal:** Establish baseline load time measurements for key page templates (language, territory, region, search), set up ongoing monitoring (e.g. New Relic, Query Monitor in staging, or a lightweight GitHub Actions synthetic check), and alert on regressions.
  **Quick wins already done:** `get_field('languages', id, false)` on territory pages to avoid hydrating hundreds of post objects.

---

## Testing Strategy

Goal: professional-grade coverage — no visual regressions, no behavior breakage, no security gaps.
Coverage is built in layers, from fast/cheap to slow/comprehensive. Each phase depends on the one before it.

---

### Layer 1 — Static Analysis ✅ (Phase 2, complete)
**Tools:** PHPCS + WordPress Coding Standards, ESLint
**Catches:** coding standards violations, basic security anti-patterns (unescaped output, direct DB queries), JS style issues
**Runs:** on every PR via GitHub Actions

**PHPStan ✅ (Phase 4, complete)**
Type-safety analysis at level 5 with `szepeviktor/phpstan-wordpress` stubs. Baseline of
pre-existing violations in `phpstan-baseline.neon`; CI fails only on new violations.
Runs on every PR via GitHub Actions alongside PHPCS.

---

### Layer 2 — Unit Tests ✅ (Phase 3, complete)
**Tools:** PHPUnit 9.6 + WP_Mock 1.1
**Catches:** regressions in isolated business logic — URL encoding, meta value fallbacks, search routing regex, pagination math
**Runs:** on every PR via GitHub Actions
**Does not cover:** templates, DB queries, actual rendering, hook/filter wiring

**Covered functions:**
- `import-captions.php` → `safe_dropbox_url()`, `get_safe_value()`
- `acf-helpers.php` → `wt_meta_value()`
- `search-filter.php` → `searchfilter()` regex routing
- `render_gallery_items.php` → `generate_gallery_pagination()`
- `wt-gallery/helpers.php` → `getDomainFromUrl()`
- `template-helpers.php` → `get_environment()`
- `events-filter.php` → `format_event_date_with_proximity()`
- `wt-gallery/includes/queries.php` → `build_gallery_query_args()` (10 tests)

**Expand over time:** any new function with non-trivial logic should ship with a unit test.

**Known constraints and upgrade path:**
WP_Mock 1.x uses [Patchwork](https://github.com/antecedent/patchwork) to redefine global PHP functions at runtime, which is fundamentally at odds with how PHPUnit 10+ works internally. As a result, the entire WordPress unit testing ecosystem (WP_Mock, Brain Monkey) is locked to PHPUnit ^9.6, which is in maintenance mode (security fixes only). PHPUnit 9.6 will reach end-of-life; PHPUnit 10+ is the present and future of PHP testing.

The forward-looking exit from this constraint is not to wait for WP_Mock to catch up — it's to reduce the surface area of WP function mocking in the first place. Functions that receive `is_admin()` or `get_query_var()` results as arguments rather than calling them directly need no mocking at all and can be tested with plain PHPUnit against any version. The refactor direction is: **push WP API calls to the edges of functions**, keeping the logic core pure. This is both a testability improvement and a general architectural improvement (separation of concerns).

As functions are refactored to be purer, WP_Mock can be removed from individual test classes incrementally. When WP_Mock is no longer needed by any test, we can upgrade to PHPUnit 10+ and drop the dependency entirely.

---

### Layer 3 — Integration Tests (Phase 5, future)
**Tools:** PHPUnit + `WP_UnitTestCase` (official WordPress test suite)
**Catches:** hook/filter wiring, CPT registration, REST endpoint responses, DB reads/writes, query correctness
**Covers templates indirectly:** tests the functions templates call, not the template files themselves
**Requires:** MySQL test database in CI (Docker service)

**Priority targets:** custom REST endpoints (`rest-endpoints.php`), `get_custom_gallery_query()` query logic, `searchfilter()` end-to-end with real WP_Query, CPT/taxonomy registration.

---

### Layer 4 — End-to-End & Visual Regression (Phase 6, future)
**Tools:** Playwright
**Catches:** full user flows (search a language, view a video, submit a form), JS behavior, authenticated vs. unauthenticated states, visual layout regressions (screenshot diffs)
**This is the right layer for testing templates** — a real browser hits real page URLs; no WP internals to mock
**Requires:** running WP instance in CI (Docker Compose with WP + MySQL + seeded content)

**Priority flows:** language search (ISO / glottocode / generic term), language page render, video page render, gallery pagination, admin-restricted pages return 403.

**Visual regression:** screenshot baseline per key page template; diff on every PR. Catches CSS/layout changes that behavior assertions miss.

---

### Layer 5 — Data Integrity (Phase 7, future)
**Tools:** WP-CLI custom command, server cron or GitHub Actions scheduled workflow
**Catches:** duplicate iso_codes/standard_names (root cause of the wblu/blu routing bug), missing required ACF fields, slug/iso_code mismatches, records that would cause routing or display failures
**Runs:** weekly scheduled job; logs results; reports violations (log file + optional GitHub issue or admin notice)
**Does not replace:** Airtable reconciliation (see Backlog) — complements it by catching problems that slip through to WordPress

**Priority checks:**
- No two published language posts share the same `iso_code` ACF value
- No two published language posts share the same `standard_name` / `post_title`
- No published language post has a blank `iso_code`
- `post_name` (URL slug) matches `iso_code` for all published language posts — mismatch causes silent routing failures like the wblu/blu bug
- (Future) Cross-check against Airtable API: every WordPress language record has a corresponding Airtable record

**Implementation approach:**
- WP-CLI command (`wp wt integrity check`) registered in a new `includes/cli/` file in the theme
- Command queries the DB directly via `$wpdb`, outputs a structured report (pass/fail per check, count of violations, sample offending records)
- Server cron runs weekly: `wp wt integrity check >> /path/to/integrity.log 2>&1`
- GitHub Actions `schedule` workflow (weekly) can SSH to staging and run the command, posting results as a job summary
- Violations do not block deploys — this is a monitoring/alerting layer, not a gate

---

### Security

- [x] **PHPCS security sniffs** — runs on every PR via Layer 1
- [ ] **WPScan in CI** — check installed plugins/themes against known CVE database
- [ ] **Secrets scanning** — ensure API keys and tokens never land in git history
- [ ] **Security review of `wt-form` and `integromat-connector`** — when brought into version control (see Plugins)
