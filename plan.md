# Wikitongues – Technical Debt & Improvement Plan

This file tracks known issues, deferred refactors, and planned improvements.
Items are grouped by area and roughly ordered by priority within each group.
Completed work is documented in [plan-archive.md](plan-archive.md).

---

## Table of Contents

- **[Backlog](#backlog)**
  - [x] Convert plan.md to checklist format
  - [x] Audit and clean up stale branches ([archive](plan-archive.md))
  - [x] Fix w-prefixed language routing — wblu/blu ([archive](plan-archive.md))
  - [x] Link Fellows to Territories and vice versa ([archive](plan-archive.md))
  - [x] Gallery `link_out` param — filtered archive pages
  - [x] Convert `writing_systems` to `writing-system` taxonomy ([archive](plan-archive.md))
  - [x] Convert `linguistic_genealogy` to `linguistic-genealogy` taxonomy ([archive](plan-archive.md))
  - [ ] Migrate `nations_of_origin` on language posts from text → territories relationship field — intentionally deferred; `Also spoken in` (the `territories` ACF relationship field) serves as the linked alternative in the sidebar. Migration requires changing the ACF field type, updating the make.com sync, and backfilling data.
  - [ ] Complete Donors post type
  - [ ] Dockerize project
  - [ ] Airtable reconciliation (520+ records missing fields)
  - [ ] Maps on territory templates
  - [ ] Download gateway plugin
  - [ ] Territories archive
  - [ ] Enhanced search results page
  - [ ] Donation optimization (donor cards in galleries)
  - [ ] Forms (report a problem, replace Airtable embeds)
  - [ ] Better aliveness (dynamic homepage)
  - [ ] Gamification (stamp rally / onboarding)

- **[Code Quality](#code-quality)**
  - [x] Refactor raw SQL in `wt-gallery` ([archive](plan-archive.md))
  - [ ] Resolve `class-wt-rest-posts-controller.php` duplication (root `includes/` vs theme `includes/`)
  - [ ] Move root-level `includes/` into `wp-content/mu-plugins/` or the theme
  - [ ] Reorganize theme `includes/` flat folder (24 files) into subdirectories by concern (e.g. `api/`, `admin/`, `taxonomies/`, `template/`, `integrations/`)
  - [ ] Autoloader for CPTs/includes
  - [ ] Archive template refactor

- **[Plugins](#plugins)**
  - [x] Delete `wt-form` plugin ([archive](plan-archive.md))
  - [x] Audit `integromat-connector` REST API exposure
  - [ ] Audit Make.com scenarios

- **[Infrastructure](#infrastructure)**
  - [ ] Migrate from Stylus
  - [x] Replace Font Awesome ([archive](plan-archive.md))
  - [ ] Performance profiling and monitoring
  - [ ] Evaluate Bedrock for composer-managed WordPress installs _(Tier 2 — resolve before code quality cleanups)_

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
    - [x] Audit `integromat-connector` REST API exposure
    - [ ] Audit Make.com scenarios

- **[Roadmap](#roadmap)**

---

## Roadmap

Logical implementation sequence across all plan items. Items within a tier can be parallelized; tiers should complete before the next begins. Detailed descriptions for each item are in the sections below.

**Key dependency chains:**
`Secrets scanning` → integromat-connector audit ✅ → Make.com scenario audit → production ACF integration
`Make.com scenario audit` → `Airtable reconciliation` _(soft: audit findings narrow reconciliation scope)_
`Evaluate Bedrock` → code quality cleanups _(if not adopting Bedrock, file layout proceeds as-is; if adopting, A/B/C become moot)_
`Duplication fix` → `Root includes move` → `Reorganize includes` → `Docker` _(Docker must capture final file layout)_
`Stylus migration` + ~~`Font Awesome replacement`~~ ✅ + `Donors post type` → `Docker` → **Layer 4 visual baseline**
`Layer 5 Data Integrity` → `Airtable reconciliation` → `nations_of_origin migration`
`Docker` → `Layer 3` → gateway integration tests | `Layer 4` → maps, performance profiling
`Donors CPT` → `Donation optimization`
`Enhanced search results page` → Layer 4 visual baseline (Tier 6)
`Archive template refactor` + `Autoloader` → `Docker` (Tier 4)
`Forms` (report/Airtable replace) → no hard deps; `Forms` (gate) → Download gateway Phase 5
`Better aliveness` → before Layer 4 visual baseline (Tier 6)
`Gamification` → Membership infrastructure (not yet scoped) → Tier 8+

---

### Tier 1 — Security foundation
_No prerequisites. Unblocks all credential-sensitive work. Complete._

- [x] Secrets scanning ([archive](plan-archive.md))
- [ ] WPScan in CI (deferred — API no longer free; use Patchstack or Wordfence)

---

### Tier 2 — Strategic decision + visual infrastructure + plugin hygiene
_Parallel. Evaluate Bedrock first within this tier — the decision gates whether code quality cleanups (Tier 3) proceed in their current form or become moot. Stylus, FA, and Donors must land before Docker (Tier 4), which must land before the Layer 4 visual baseline. Make.com audit moved here (no hard deps) so findings are available before Airtable reconciliation in Tier 5._

- [x] Delete `wt-form` plugin ([archive](plan-archive.md))
- [x] Audit `integromat-connector` REST API exposure _(findings: no ACF fields opted in; token active; Guard only covers WP core entities — see Plugins section)_
- [x] Gallery `link_out` param ([archive](plan-archive.md))
- [x] Gallery `link_out` param — filtered archive pages (`archive-fellows.php`, `archive-languages.php`, `archive-videos.php`; `?territory=` / `?language=` filter params; "see all" button on section header)
- [x] Convert `writing_systems` to `writing-system` taxonomy ([archive](plan-archive.md))
- [x] Convert `linguistic_genealogy` to `linguistic-genealogy` taxonomy ([archive](plan-archive.md))
- [ ] Evaluate Bedrock _(strategic decision only — no code; resolve first within this tier)_
- [ ] Audit Make.com scenarios _(moved up from Tier 3; no hard deps; findings inform Airtable reconciliation scope)_
- [x] Replace Font Awesome ([archive](plan-archive.md))
- [ ] Complete Donors post type
- [ ] Migrate from Stylus
- [ ] Territories archive _(no hard deps; simple gallery-based archive, follows existing pattern)_

---

### Tier 3 — Code quality cleanup + data integrity baseline
_Parallel tracks. Bedrock evaluation (Tier 2) must be resolved before A/B/C so the file layout decision is final. A/B/C must complete before Docker (Tier 4) so the image captures the final structure. Layer 5 has no Docker dependency and runs against the live DB; completing it in this tier means results are ready for Airtable reconciliation in Tier 5._

- [ ] Resolve `class-wt-rest-posts-controller.php` duplication _(root copies are orphaned — safe delete; theme copy is canonical)_
- [ ] Move root-level `includes/` into `wp-content/mu-plugins/` or the theme
- [ ] Reorganize theme `includes/` into subdirectories by concern _(after duplication and root move are resolved)_
- [ ] Autoloader for CPTs/includes _(do as part of Reorganize includes)_
- [ ] Archive template refactor _(before Docker so image captures refactored layout)_
- [ ] Enhanced search results page _(no hard deps; parallel track)_
- [ ] Layer 5 — Data Integrity _(parallel track; no Docker required)_

---

### Tier 4 — Docker + gateway core
_Code quality refactors (Tier 3) must be done so Docker captures the final file layout. Stylus, FA, and Donors (Tier 2) must be done so Docker captures the final CSS/icon/Donors state. Gateway Phases 0–5 can run in parallel with Docker setup — no mutual dependency._

- [ ] Dockerize project
- [ ] Download gateway — Phases 0–5 _(scaffold, data model, primitives, endpoint, resource authoring, gate modes)_
- [ ] Donation optimization — phase 1: donor cards in galleries _(after Donors CPT + Docker)_
- [ ] Forms — report a problem + replace Airtable embeds _(parallel to gateway Phases 0–5; no Docker dep for basic implementation)_
- [ ] Better aliveness — dynamic homepage _(before Layer 4 visual baseline)_

---

### Tier 5 — Integration tests + Airtable reconciliation + gateway completion
_Layer 3 requires Docker. Airtable reconciliation requires Layer 5 results (Tier 3) and benefits from Make.com audit findings (Tier 2). Gateway Phases 6–10 require Phases 0–5._

- [ ] Layer 3 — Integration Tests
- [ ] Airtable reconciliation
- [ ] Download gateway — Phases 6–10 _(Dropbox, GA4, reporting, retention, rollout)_

---

### Tier 6 — Visual baseline + data migration
_Layer 4 requires Docker + Stylus + FA + Donors (all done by Tier 4). nations_of_origin migration requires Airtable reconciliation (Tier 5)._

- [ ] Layer 4 — End-to-End & Visual Regression _(locks the visual baseline; nothing that changes template output should land after this without a deliberate baseline update)_
- [ ] Migrate `nations_of_origin` _(intentionally deferred; see Backlog)_

---

### Tier 7 — Features and monitoring requiring the visual baseline
_Maps introduces visual changes to high-traffic territory/region templates; Layer 4 regression coverage must be active first. Performance profiling (Playwright-based) requires Docker + Layer 4._

- [ ] Maps on territory templates
- [ ] Performance profiling and monitoring

---

### Tier 8 — Membership-dependent features
_Blocked on membership infrastructure (user accounts), which is not currently in scope. Write a spec before implementation._

- [ ] Gamification _(stamp rally + onboarding; blocked on membership infrastructure — write spec first)_

---

## Backlog

- [ ] **Dockerize project** for ease of contributor setup
- [ ] **Airtable reconciliation** — 520+ language records missing essential fields. make.com syncs from Airtable without field guarantees; records arrive in WordPress incomplete. Rather than enforcing hard requirements at the WordPress layer, reconciliation should happen at the Airtable source: institute field requirements there and handle any divergence before sync.
- [ ] **Complete Donors post type** (in progress, stalled)
- [x] **Gallery `link_out` param — filtered archive pages**
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

- [ ] **Territories archive**
  `/territories/` has no dedicated archive page — falls through to a default WP archive or 404. A territories archive should list all territories in a browsable gallery, using `create_gallery_instance()` following the existing archive pattern. No hard prerequisites beyond the CPT existing (which it does).

- [ ] **Enhanced search results page**
  The current search results page is basic. Replace with a gallery-powered page surfacing results across languages, territories, linguistic genealogy, writing system, videos, and fellows. Evaluate `create_gallery_instance()` in multi-type mode or a dedicated query-and-render pattern. Adds meaningful discovery value.

- [ ] **Donation optimization**
  After the Donors CPT lands: (1) integrate donor cards into gallery instances on relevant pages (campaign pages, homepage); (2) `membership` — future phase where recurring donors receive profile features. Scope phase 1 only for now; membership is deferred until a separate spec is written.

- [ ] **Forms**
  Three sub-items:
  - **Report a problem** — lightweight form for users to flag content errors (broken language page, wrong ISO code, etc.)
  - **Replace Airtable embed submission forms** — Airtable iframe embeds are brittle and off-brand; replace with native WP forms (Gravity Forms or custom REST endpoints)
  - **Download gateway gate form** — already scoped in gateway Phase 5; not duplicated here

- [ ] **Better aliveness**
  The homepage feels static. Surface the most recently added/updated languages, latest videos, and rotate banners to reflect current campaigns. Identify which content signals are most meaningful (publication date? editor-curated featured flag?) and build the query logic. Assess JS vs. server-side rendering needs. Must land before Layer 4 visual baseline so the dynamic content is captured in screenshot comparisons.

- [ ] **Gamification**
  Stamp rally: users earn "stamps" for core actions (watch a video, add a language, share a page). Onboarding flow: guide new users/members through first actions. Matches the Wikitongues travel/documentation brand. **Hard dependency:** membership infrastructure (user accounts — not currently in scope). Write a separate spec before implementation. Deferred to Tier 8+.

---

## Code Quality

_Previously completed items in [plan-archive.md](plan-archive.md)._

- [ ] **Resolve `class-wt-rest-posts-controller.php` duplication**
  The file exists in both `includes/` (root) and `wp-content/themes/blankslate-child/includes/`. One is the source of truth; the other should be removed or replaced with a `require`.

- [ ] **Move root-level `includes/` into `wp-content/mu-plugins/` or the theme**
  The root `includes/` directory is non-standard — WordPress has no awareness of it and files must be manually required somewhere (likely `functions.php`). Move to a must-use plugin (`wp-content/mu-plugins/`) if the code is site-wide, or into the theme's `includes/` if it is theme-specific. Resolve the duplication item above first.

- [ ] **Reorganize theme `includes/` into subdirectories by concern**
  Currently 24 flat files. Suggested grouping:
  - `api/` — REST endpoints, controller
  - `admin/` — admin helpers, batch operations
  - `taxonomies/` — CPT and taxonomy registration
  - `template/` — template helpers, router
  - `integrations/` — import-captions, events filter, license handling

- [ ] **Autoloader for CPTs/includes**
  `includes/custom-post-types.php` manually `require_once`s 15 files. Every new CPT requires editing this orchestrator. Replace with a directory-scanning autoloader that automatically requires every `.php` file in `includes/custom-post-types/` — no manual step when adding new CPTs. Do as part of (or immediately after) the Reorganize includes item.

- [ ] **Archive template refactor**
  `archive-languages.php`, `archive-fellows.php`, `archive-videos.php` share a structural pattern (build args → `create_gallery_instance()` → handle filter params) with boilerplate repeated across files. Evaluate a shared archive helper or declarative config approach to reduce per-template repetition while keeping template-specific filter logic clear.
  Note: `archive-donors.php` intentionally does NOT use `create_gallery_instance()` and is out of scope for this refactor.

---

## Plugins

- [x] **Delete `wt-form` plugin** — done ([archive](plan-archive.md))

- [x] **Audit `integromat-connector` REST API exposure**
  **File:** `wp-content/plugins/integromat-connector/` (v1.5.9, Make Connector by Celonis s.r.o.)
  Not custom code — managed via WP admin plugin updates; not tracked in git.

  **Findings:**
  - **Token:** Active (`iwc_api_key` confirmed in DB, 32-char alphanumeric). No expiry; no rotation has been performed. Token stored in `wp_site_options`.
  - **Authentication model:** `HTTP_IWC_API_KEY` header → `wp_set_current_user($admin_id)` (administrator). Guard only protects WP core entity endpoints (posts/users/comments/tags/categories/media) on POST/PUT/DELETE. Custom post type endpoints (languages, videos, fellows, territories) are not additionally gated by the plugin, though WP's own auth still applies.
  - **Custom fields exposed:** **None.** `integromat_api_options_post` and `integromat_api_options_taxonomy` do not exist in the DB — no ACF fields or custom taxonomies have been opted in.
  - **Implication:** Make.com is currently writing to raw `wp_postmeta` keys directly rather than through the ACF REST API. This works but bypasses ACF hooks, validation, and field formatting.
  - **Production-quality path:** Opt in the relevant ACF field keys in the integromat-connector admin settings (Settings → Make Connector → Posts tab), then update Make.com scenarios to read/write those fields as REST API fields rather than raw meta. Requires the Make.com scenario audit first to know which fields are in scope.

- [ ] **Audit Make.com scenarios**
  **Prerequisite:** integromat-connector audit (done above).
  Make.com is confirmed live and writing to WordPress, but the active scenario inventory is unknown. This audit should be done in the Make.com dashboard.

  **Goal:**
  1. List all active scenarios and their triggers (Airtable webhook? Scheduled? Manual?)
  2. For each scenario: which WordPress post types does it write to, and which fields (raw meta keys)?
  3. Identify which of those fields are ACF-managed vs. plain `wp_postmeta`
  4. Determine whether any scenario reads data back from WordPress (and which fields)
  5. Document the data flow for the primary Airtable → WordPress language sync

  **Production-quality follow-on (after audit):**
  - Opt in the ACF field keys used by Make.com in the integromat-connector admin settings
  - Update the Make.com scenarios to write via the REST field names rather than raw meta keys
  - Rotate the `iwc_api_key` token; document rotation procedure
  - Confirm custom post type write endpoints require authentication (currently Guard-scope gap for CPTs)

---

## Infrastructure

- [ ] **Migrate from Stylus to a maintained CSS preprocessor** (PostCSS or Sass)
  Stylus is largely unmaintained. Its dependency chain (`glob@7` → `minimatch@3`) has known ReDoS vulnerabilities (dev-only, no production impact). `npm audit` flags 3 high-severity findings with no clean in-place fix.
  **Goal:** Migrate to PostCSS or Sass. Resolves audit findings and improves long-term maintainability of the CSS build pipeline.

- [x] **Replace Font Awesome** — done ([archive](plan-archive.md))

- [ ] **Performance profiling and monitoring**
  No visibility into page load times or query performance in production. Known risk areas already identified: territory pages with large language counts (India: 403 languages, China: 249, Brazil: 200, USA: 197) and continent-level region pages aggregating many territories. `get_field()` returning full post objects on relationship fields at scale is the primary pattern to watch.
  **Goal:** Establish baseline load time measurements for key page templates (language, territory, region, search), set up ongoing monitoring (e.g. New Relic, Query Monitor in staging, or a lightweight GitHub Actions synthetic check), and alert on regressions.
  **Quick wins already done:** `get_field('languages', id, false)` on territory pages to avoid hydrating hundreds of post objects.

- [ ] **Evaluate Bedrock for composer-managed WordPress** _(Tier 2 — resolve before code quality cleanups)_
  Bedrock restructures a WordPress install so WP core and plugins are managed as Composer dependencies and excluded from git, with custom code (themes/plugins) as the only tracked artifacts. If adopted, the Code Quality cleanups (duplication fix, root includes move, reorganize) become moot in their current form since the file layout changes radically.
  **Goal:** Assess fit for this project — cost of migration vs. long-term benefit. If the decision is "no," proceed with Code Quality cleanups in their current form. If "yes," scope the migration as a separate project.

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

**Deferred unit test candidates:**
- **Territory name list formatter** — the Oxford-comma builder in `single-languages.php` (maps `$territories` → "Dominica, Saint Kitts and Nevis, and United Kingdom") has real edge-case risk (1 item / 2 items / 3+ items). If extracted into a named helper (e.g. `wt_format_list()`), it becomes a trivially testable pure function.
- **`GalleryQueryArgsTest` regression guard** — an assertion that `linguistic_genealogy` is absent from the `in_array` exact-match list, mirroring the `writing_systems` removal test that was deleted with PR #467.
- **Archive parameter resolution (`archive-languages.php`)** — deferred to Layer 3; mixes `$_GET`, `get_term_by()`, and `create_gallery_instance()` in a way that requires a running WP instance to test meaningfully.
- **Template rendering (`single-languages.php`)** — deferred to Layer 4 (E2E); territory ID merging and gallery title logic are embedded in the template alongside `get_field()` calls.

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
- [x] **Security review of `integromat-connector`** — audited (see Plugins section); not tracked in VCS as it is a third-party plugin
- [ ] **Audit Make.com scenarios** — see Plugins section for scope and production-quality follow-on
