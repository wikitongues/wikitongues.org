# Wikitongues – Technical Debt & Improvement Plan

This file tracks known issues, deferred refactors, and planned improvements.
Items are grouped by area and roughly ordered by priority within each group.

---

## Code Quality

### Refactor dynamic IN clause in `wt-gallery` to eliminate raw SQL
**File:** `wp-content/plugins/wt-gallery/includes/queries.php` (~line 33–44)
**Context:** The `featured_languages` branch of `get_custom_gallery_query()` builds a
dynamic `IN ($placeholders)` clause using `array_fill('%s')` and `$wpdb->prepare()`.
This is the correct WordPress pattern, but PHPCS cannot statically verify that
`$placeholders` is safe, requiring a `phpcs:disable` suppression comment.
**Goal:** Refactor to avoid raw SQL entirely — query by post ID or slug instead of
`post_title`, which would allow `WP_Query` args (`post__in`) to replace the manual
`$wpdb` query. This also makes the query more robust (post titles are not unique).
**Prerequisite:** Understand how gallery shortcode callers pass `meta_value` for
`featured_languages` — the refactor must preserve that interface or update callers too.

---

## Plugins

### Track `wt-form` in version control when feature work resumes
**File:** `wp-content/plugins/wt-form/`
**Context:** Currently excluded from git. Plugin handles form submissions to Airtable
and Mailchimp. Not actively used; excluded from linting scope for now.
**Goal:** Add to `.gitignore` allowlist, include in PHPCS scan, and review for
security (form validation, API key handling).

### Track `integromat-connector` in version control when automation work resumes
**File:** `wp-content/plugins/integromat-connector/`
**Context:** Currently excluded from git. Custom Make.com connector with API token
auth. Not in active development; excluded from linting scope for now.
**Goal:** Add to `.gitignore` allowlist, include in PHPCS scan, review API token
handling and REST endpoint security.

---

## Infrastructure

### Migrate from Stylus to a maintained CSS preprocessor
**Context:** Stylus is largely unmaintained. Its dependency chain (`glob@7` →
`minimatch@3`) has known ReDoS vulnerabilities (dev-only, no production impact).
`npm audit` flags 3 high-severity findings with no clean in-place fix.
**Goal:** Migrate to PostCSS or Sass. Resolves audit findings and improves long-term
maintainability of the CSS build pipeline.

---

## Testing Strategy

Goal: professional-grade coverage — no visual regressions, no behavior breakage, no security gaps.
Coverage is built in layers, from fast/cheap to slow/comprehensive. Each phase depends on the one before it.

---

### Layer 1 — Static Analysis ✅ (Phase 2, complete)
**Tools:** PHPCS + WordPress Coding Standards, ESLint
**Catches:** coding standards violations, basic security anti-patterns (unescaped output, direct DB queries), JS style issues
**Runs:** on every PR via GitHub Actions

**Gap — PHPStan (Phase 4)**
Type-safety analysis: undefined variables, wrong argument types, unreachable code, method calls on null.
Complements PHPCS (style/security) with correctness guarantees.
Requires a PHPStan config + WordPress stubs (`szepeviktor/phpstan-wordpress`).

---

### Layer 2 — Unit Tests (Phase 3, in progress)
**Tools:** PHPUnit 10 + WP_Mock
**Catches:** regressions in isolated business logic — URL encoding, meta value fallbacks, search routing regex, pagination math
**Runs:** on every PR via GitHub Actions
**Does not cover:** templates, DB queries, actual rendering, hook/filter wiring

**Scope (initial):** functions in `includes/` that have testable logic without a database:
- `import-captions.php` → `safe_dropbox_url()`, `get_safe_value()` (pure PHP, no mocks)
- `acf-helpers.php` → `wt_meta_value()` (mock `esc_attr`)
- `search-filter.php` → `searchfilter()` regex routing (mock `is_admin`, `get_query_var`, query stub)
- `render_gallery_items.php` → `generate_gallery_pagination()` (mock `esc_attr`, stdClass query stub)

**Expand over time:** `getDomainFromUrl()` in `wt-gallery/helpers.php`, `get_environment()` and date logic in `template-helpers.php`, `format_event_date_with_proximity()` in `events-filter.php` once extracted. Any new function with non-trivial logic should ship with a unit test.

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

### Security (ongoing)
- PHPCS security sniffs already run (Layer 1)
- **Gap:** WPScan in CI — checks installed plugins/themes against known CVE database
- **Gap:** secrets scanning — ensure API keys, tokens never land in git history
- `wt-form` and `integromat-connector` plugins need security review when brought into version control (see Plugins section below)
