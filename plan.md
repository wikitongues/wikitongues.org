# Wikitongues – Technical Debt & Improvement Plan

This file tracks known issues, deferred refactors, and planned improvements.
Items are grouped by area and roughly ordered by priority within each group.
Completed work is documented in [plan-archive.md](plan-archive.md).

---

## Table of Contents

- **[Backlog](#backlog)**
  - [x] Convert plan.md to checklist format
  - [ ] Dockerize project
  - [x] Audit and clean up stale branches ([archive](plan-archive.md))
  - [ ] Airtable reconciliation (520+ records missing fields)
  - [x] Fix w-prefixed language routing — wblu/blu ([archive](plan-archive.md))
  - [ ] Complete Donors post type
  - [x] Link Fellows to Territories and vice versa ([archive](plan-archive.md))
  - [ ] Maps on territory templates
  - [ ] Gallery `link_out` param — filtered archive pages
  - [ ] Download gateway plugin
- [ ] Migrate `nations_of_origin` on language posts from text → territories relationship field — intentionally deferred; `Also spoken in` (the `territories` ACF relationship field) serves as the linked alternative in the sidebar. Migration requires changing the ACF field type, updating the make.com sync, and backfilling data.

- **[Code Quality](#code-quality)**
  - [x] Refactor raw SQL in `wt-gallery` ([archive](plan-archive.md))

- **[Plugins](#plugins)**
  - [ ] Delete `wt-form` plugin
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
        - [ ] WPScan in CI (deferred — API no longer free; use Patchstack or Wordfence)
    - [x] Secrets scanning ([archive](plan-archive.md))
    - [ ] Security review of `integromat-connector`

- **[Roadmap](#roadmap)**

---

## Roadmap

Logical implementation sequence across all plan items. Items within a tier can be parallelized; tiers should complete before the next begins. Detailed descriptions for each item are in the sections below.

**Key dependency chain:**
`Secrets scanning` → plugin VCS → plugin security reviews
`Stylus migration` + `Font Awesome replacement` + `Donors post type` → **Layer 4 visual baseline** (must all land before the E2E baseline is set)
`Docker` → `Layer 3` → gateway integration tests | `Layer 4` → maps, performance profiling
`Layer 5 Data Integrity` → `Airtable reconciliation` → `nations_of_origin migration`

---

### Tier 1 — Security foundation
_No prerequisites. Unblocks all credential-sensitive work._

- [x] Secrets scanning ([archive](plan-archive.md))
- [ ] WPScan in CI (deferred — API no longer free; use Patchstack or Wordfence)

---

### Tier 2 — Plugin hygiene + infrastructure cleanup + quick wins
_Parallel. Secrets scanning (Tier 1) required before `integromat-connector` enters VCS. `wt-form` deletion has no prerequisites (no live usage, no hardcoded credentials). Stylus, FA, and Donors have no hard deps but must land before the Layer 4 visual baseline (Tier 5)._

- [ ] Delete `wt-form` plugin _(no prerequisites — confirmed unused, credentials in ACF options not in files)_
- [ ] Track `integromat-connector` in version control
- [ ] Migrate from Stylus
- [ ] Replace Font Awesome
- [ ] Complete Donors post type
- [ ] Gallery `link_out` param

---

### Tier 3 — Docker + security reviews + data integrity
_Parallel. Docker unblocks testing Layers 3–4. Security reviews require plugins to be in VCS. Layer 5 runs against the live DB and needs no Docker, but should precede Airtable reconciliation._

- [ ] Dockerize project _(CSS/icon/Donors changes should be done first so Docker captures the final build)_
- [ ] Security review of `integromat-connector`
- [ ] Layer 5 — Data Integrity

---

### Tier 4 — Integration tests + Airtable + gateway core
_Parallel. Layer 3 requires Docker. Airtable reconciliation requires Layer 5 results. Gateway Phases 0–5 have no external hard dependencies but secrets scanning must be done before any credential-adjacent code (Dropbox)._

- [ ] Layer 3 — Integration Tests
- [ ] Airtable reconciliation
- [ ] Download gateway — Phases 0–5 _(scaffold, data model, primitives, endpoint, resource authoring, gate modes)_

---

### Tier 5 — Visual baseline + gateway completion + data migration
_Layer 4 requires Docker + Stylus + FA + Donors all done. Gateway Phases 6–10 require Tier 1 secrets scanning and benefit from Layer 3. nations_of_origin migration requires Airtable reconciliation._

- [ ] Layer 4 — End-to-End & Visual Regression _(locks the visual baseline; nothing that changes template output should land after this without a deliberate baseline update)_
- [ ] Download gateway — Phases 6–10 _(Dropbox, GA4, reporting, retention, rollout)_
- [ ] Migrate `nations_of_origin` _(intentionally deferred; see Backlog)_

---

### Tier 6 — Features requiring the visual baseline + performance monitoring
_Maps introduces visual changes to high-traffic territory/region templates; Layer 4 regression coverage should be active first. Performance profiling (Playwright-based) also requires Docker + Layer 4._

- [ ] Maps on territory templates
- [ ] Performance profiling and monitoring

---

## Backlog

- [ ] **Dockerize project** for ease of contributor setup
- [ ] **Airtable reconciliation** — 520+ language records missing essential fields. make.com syncs from Airtable without field guarantees; records arrive in WordPress incomplete. Rather than enforcing hard requirements at the WordPress layer, reconciliation should happen at the Airtable source: institute field requirements there and handle any divergence before sync.
- [ ] **Complete Donors post type** (in progress, stalled)
- [ ] **Gallery `link_out` param — filtered archive pages**
  Gallery sections (e.g. "Fellows from the United States", "Languages from the United States", "English videos") should be linkable to a dedicated full-page listing showing all matching items with full pagination. Auto-generated — no editor action required.

  **Two parts:**

  1. **`wt-gallery` plugin — `link_out` param**: when `link_out` is set (a URL string), render the `wt_sectionHeader` as `<a href="{link_out}">` instead of plain text. No other gallery behaviour changes.

  2. **Archive templates with filter params**: existing archive pages (`archive-fellows.php`, `archive-languages.php`, `archive-videos.php`) check for query-string filter params and apply them to the `WP_Query` or `WP_Query` args:
     - `?territory=<slug>` on the fellows/languages archives → meta query filtering by territory
     - `?language=<slug>` on the videos archive → tax query or meta query filtering by language
     Callers (territory pages, language pages) pass the constructed URL as `link_out` when calling `create_gallery_instance()`.

  **URL examples:** `/fellows/?territory=united-states`, `/languages/?territory=united-states`, `/videos/?language=eng`
  No custom rewrite rules required — query params on existing archive templates.

- [ ] **Maps on territory templates**
  Territory and region pages would benefit from an embedded map showing the geographic area. Applicable to both `single-territories.php` and `taxonomy-region.php`.
  **Goal:** Evaluate map options (Mapbox, Leaflet + OpenStreetMap, Google Maps Embed); implement on territory and region templates; ensure no API key is exposed client-side without restriction.

- [ ] **Download gateway plugin**
  Downloads currently go through unprotected direct file URLs or `force_download_file()` (proxy streaming, no logging, no auth). The goal is a standalone plugin that logs every download, optionally gates access with a name/email modal, supports Dropbox-hosted assets via temporary API links, forwards events to GA4, and auto-anonymizes collected data.

  **Full spec:** separate document provided to Claude Code. Summary below.

  **Architectural decisions (resolved):**
  - Signed expiring redirect URLs — not proxy streaming. Replaces the existing `force_download_file()` readfile approach.
  - CPT strategy: use existing `resources` and `document_files` CPTs rather than introducing `dg_resource`. Records not yet populated so migration risk is low.
  - Plugin namespace: `download-gateway` / prefix `dg_`.

  **Schema additions (3 tables + 1 gap fix):**
  - `wp_dg_people` — email_hash, email, name, consent fields, anonymization flags
  - `wp_dg_download_events` — resource, storage, UTM params, visitor_id, person_id, ip_hash, event_type
  - `wp_dg_webhook_delivery` — retry queue and dead-letter
  - `wp_dg_tokens` (**not in original spec — required**) — one-time download tokens with expiry; needed by Phase 3 gate check and Phase 5 gate submission

  **Phases:**
  - **Phase 0** — Plugin scaffold: activation/deactivation/uninstall hooks, feature flag constant, settings page placeholder, logging conventions
  - **Phase 1** — Data model: create tables with indexes on activation; idempotent migrations
  - **Phase 2a** — Core primitives (unblocks Phase 3): PolicyResolver with precedence (per-resource → taxonomy → global), SettingsRepository, EventBus, DownloadEventRepository
  - **Phase 2b** — Form/gate primitives (unblocks Phase 5): FormSchemaRegistry, Validator, SubmissionService, PeopleRepository, RateLimiter + honeypot, modal UI kit
  - **Phase 2c** — Deferrable primitives: WebhookDispatcher (retry + dead-letter), RetentionJob skeleton + cron registration
  - **Phase 3** — Download endpoint: `/dg/download/{token-or-post-id}`, `dg_vid` visitor cookie, click event logging, UTM/referrer capture, IP hashing, no-cache headers
  - **Phase 4** — Resource authoring: attach gateway fields to existing `resources`/`document_files` posts via ACF (file_url, storage_type, dropbox_path/share_id, version); metabox showing gateway URL; `[dg_download]` shortcode
  - **Phase 5** — Gate modes: soft gate (skippable) and hard gate (email required); `POST /wp-json/dg/v1/gate`; person upsert; one-time token returned; nonce + rate limit + honeypot
  - **Phase 6** — Storage adapters + Dropbox: local/media/external adapters issue expiring tokenized redirects; Dropbox adapter calls `files/get_temporary_link`, caches result briefly, stores credentials in options with `autoload=no`
  - **Phase 7** — GA4 forwarding: EventBus subscriber; client-side where possible; first-party log unaffected if GA4 blocked. Events: `resource_download_click`, `resource_download_gate_submit`, `resource_download_redirect`
  - **Phase 8** — Admin reporting: date-filtered download table, top resources, CSV export with capability check
  - **Phase 9** — Retention automation: daily cron nulls email/name after `retention_months`, marks `is_anonymized`; manual run-now button
  - **Phase 10** — Rollout: convert resources hub first, then top downloads; deprecate and remove `document-download-handler.php` `force_download_file()` once coverage is complete

  **Cut lines (if scope must shrink):**
  Must-have: Phase 0–3, Phase 5 (basic hard gate), Phase 9 (retention)
  Cut first: taxonomy-level policy defaults, admin charts (keep CSV only), webhook retries (keep best-effort single attempt), inline gate option (keep modal only)

  **Implementation notes:**
  - WP Cron fires on page visits only — production retention job should be backed by server cron (`wp cron event run --due-now`)
  - Cache plugins (WP Rocket etc.) must explicitly exclude `/dg/download/` — HTTP headers alone are not sufficient
  - `dg_vid` cookie: define whether it is set unconditionally or only after consent (GDPR/ePrivacy implications)
  - Dropbox credentials: store in `wp_options` with `autoload=no`; ensure excluded from any REST API exposure
  - ACF fields on plugin-registered CPT: use `register_meta` or own ACF JSON within the plugin — do not depend on theme's `acf-json/` directory
  - EventBus: evaluate whether `do_action('dg/download/click', $event)` is sufficient before introducing a custom bus class

  **Testing targets (unit):** PolicyResolver precedence, Validator, token expiry, people upsert
  **Testing targets (integration):** endpoint logs and redirects, gate submission yields one-time token, Dropbox temporary link generation

---

## Code Quality

_All items complete. See [plan-archive.md](plan-archive.md)._

---

## Plugins

- [ ] **Delete `wt-form` plugin**
  **File:** `wp-content/plugins/wt-form/`
  A prototype download gate for the Revitalization Toolkit — collects name and email, stores to a `form_submission` CPT, with stubs for Airtable and Mailchimp integration (both methods begin with `return;` and have never run). The `[wikitongues_form]` shortcode is not used on any published page. Fully superseded by download gateway Phase 5. Credentials are pulled from ACF options (not hardcoded), so no secrets risk.
  **Goal:** Deactivate and delete the plugin folder. Can be done independently of secrets scanning. Do before the download gateway Phase 5 lands to avoid confusion between the two form systems.

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
- `template-helpers.php` → `get_environment()`, `wt_prefix_the()`
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
- [ ] **WPScan in CI** — WPScan API is no longer free; deferred. Recommended replacement: install Patchstack or Wordfence on the production site for plugin/theme vulnerability monitoring without a paid API dependency.
- [x] **Secrets scanning** — TruffleHog on every PR (pinned SHA v3.93.4); GitHub native secret scanning + push protection enabled on repo ([archive](plan-archive.md))
- [ ] **Security review of `integromat-connector`** — when brought into version control (see Plugins)
