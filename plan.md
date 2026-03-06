# Wikitongues – Technical Debt & Improvement Plan

This file tracks known issues, deferred refactors, and planned improvements.
Completed work: [plan-archive.md](docs/plan-archive.md) | Testing strategy: [docs/testing-strategy.md](docs/testing-strategy.md)

---

## Table of Contents

- [Roadmap](#roadmap)

---

## Roadmap

Phases are ordered by dependency. Items within a phase can be parallelized; phases should complete before the next begins.

**Key dependency chains:**

- `Secrets scanning` → integromat-connector audit ✅ → Make.com scenario audit ✅ → `wt-airtable-sync` ✅ → retire integromat-connector write paths ✅
- `Make.com scenario audit` ✅ → `Airtable reconciliation` _(soft: audit findings narrow reconciliation scope)_
- ~~`Evaluate Bedrock`~~ ✅ → code quality cleanups proceed in current form _(decision: No — see [archive](docs/plan-archive.md))_
- `Duplication fix` → `Root includes move` → `Reorganize includes` → `Docker` _(Docker must capture final file layout)_
- ~~`Font Awesome`~~ ✅ → `Docker` → **Layer 4 visual baseline** → `Stylus migration` _(deferred)_
- `Donors CPT` _(Phase 6)_ → `Donation optimization`
- `Archive template refactor` + `Autoloader` → `Docker` (Phase 4)
- `PHPStan baseline reduction` → zero suppressions before `Layer 3` (Phase 5)
- `Docker` → `Layer 3` → gateway integration tests
- `Docker` → `Layer 4` → maps, performance profiling
- `Layer 5 Data Integrity` → `Airtable reconciliation` → `nations_of_origin migration`
- `Enhanced search results page` → `Layer 4 visual baseline` (Phase 6)
- `Better aliveness` → before `Layer 4 visual baseline` (Phase 6)
- `Forms` (report/Airtable replace) — no hard deps; `Forms` (gate) → Download gateway sub-phase 5
- `Gamification` → Membership infrastructure _(not in scope)_ → Phase 8+

---

### Phase 1 — Security foundation ✅

_No prerequisites. Unblocked all credential-sensitive work. Complete._

- [x] Secrets scanning — TruffleHog on every PR + GitHub native push protection ([archive](docs/plan-archive.md))
- [x] PHPCS security sniffs — runs on every PR via static analysis ([archive](docs/plan-archive.md))
- [x] Audit `integromat-connector` REST API exposure — no ACF fields opted in; token active; Guard only covers WP core entities ([archive](docs/plan-archive.md))
- [x] Audit Make.com scenarios — findings in `docs/make-audit-findings.md` ([archive](docs/plan-archive.md))
- [ ] WPScan in CI _(deferred — API no longer free; use Patchstack or Wordfence on production instead)_

---

### Phase 2 — Visual infrastructure + plugin hygiene ✅

_Complete._

- [x] Delete `wt-form` plugin ([archive](docs/plan-archive.md))
- [x] Gallery `link_out` param + filtered archive pages ([archive](docs/plan-archive.md))
- [x] Convert `writing_systems` to `writing-system` taxonomy ([archive](docs/plan-archive.md))
- [x] Convert `linguistic_genealogy` to `linguistic-genealogy` taxonomy ([archive](docs/plan-archive.md))
- [x] Replace Font Awesome with inline SVGs ([archive](docs/plan-archive.md))
- [x] Territories archive ([archive](docs/plan-archive.md))
- [x] Evaluate Bedrock _(decision: No — GreenGeeks hosting blocks webroot relocation; see [archive](docs/plan-archive.md))_
- [x] `wt-airtable-sync` plugin — Phases 0–3 complete; production cutover 2026-03-01 ([docs](docs/airtable-sync.md), [archive](docs/plan-archive.md))

---

### Phase 3 — Code quality + data integrity baseline

_Code quality chain (1→5) must complete before Docker (Phase 4) so the image captures the final structure. Parallel tracks (6–10) have no ordering constraint relative to each other or the chain._

#### 1. ~~Resolve `class-wt-rest-posts-controller.php` duplication~~ ✅

Root copy was orphaned — deleted (PR #501). Theme copy is canonical. Remaining cleanup is item 3 below.

#### 2. ~~Staging environment data sync~~ ✅

Weekly automated sync via `backup-prod-db.yml` → `sync-prod-to-staging.yml`. Runbook: [`docs/staging-sync.md`](docs/staging-sync.md) (PR #518).

#### 3. ~~Remove dead code + clear root `includes/`~~ ✅

Deleted `post-object-helpers.php` (both copies), `class-wt-rest-posts-controller.php`, removed `rest_controller_class` from 7 CPTs, cleared root `includes/` directory (PR #520).

#### 4. ~~Reorganize theme `includes/` into subdirectories + autoloader~~ ✅

See [archive](docs/plan-archive.md) (PR #529).

#### 5. ~~CPT/taxonomy file consistency refactor~~ ✅

See [archive](docs/plan-archive.md) (PR #529).

#### 6. Archive template refactor _(before Docker)_

`archive-languages.php`, `archive-fellows.php`, `archive-videos.php` share a structural pattern with boilerplate repeated across files. Evaluate a shared archive helper or declarative config approach. `archive-donors.php` intentionally does NOT use `create_gallery_instance()` — out of scope.

#### 7. `gallery-territories.php` + `archive-territories.php` fixes _(combines former F + G)_

**`gallery-territories.php`:**
- **Double query** — drop `no_found_rows=true` from the preview query and read `found_posts` directly; halves query count per card (55 cards × 1 saved query = 55 fewer SQL calls on the Asia archive page)
- **XSS** — `get_the_title()` in the `alt` attribute not escaped; should be `esc_attr( get_the_title() )`
- **Blank label** — `get_field('standard_name', ...)` returns null when unset; no fallback to `post_title`
- **Filter bypass** — `$language_post->post_title` used for video lookup instead of `get_the_title()`

**`archive-territories.php`:**
- `?region=<continent-slug>` filter relies on WP defaulting `tax_query` to `include_children => true`. Add explicit `include_children => true` or a comment — if `build_gallery_query_args()` ever adds an explicit `false`, continent archive pages silently break.


#### 8. Fellows ACF field audit _(parallel)_

Audit which ACF fields on the `fellows` CPT are actually read by templates (`single-fellows.php`, `modules/fellows/meta--fellows-single.php`, `archive-fellows.php`, `template-revitalization-fellows.php`) and which are unused. Remove or deprecate unused fields.

#### 9. Root-level file hygiene _(parallel)_

`plan-archive.md` moved to `docs/`; `.DS_Store` gitignored; `docs/local_docs/` structure established (PRs #498–500). Remaining:

- Full audit of locally present but untracked stale files (testing scripts, migration files, ad hoc exports) — remove or document any that remain
- `npm audit` — `inflight@1.0.6` and `glob@7.2.3` flagged as deprecated/vulnerable in deploy logs; both are transitive dev dependencies of the Stylus toolchain. Address by updating or replacing the Stylus build dependency (overlaps with Phase 7 Stylus migration)

#### 10. Layer 5 — Data Integrity _(parallel; no Docker required)_

Weekly WP-CLI command (`wp wt integrity check`) against the live DB. See [docs/testing-strategy.md](docs/testing-strategy.md) for full spec, priority checks, and implementation approach.

#### 11. Enhanced search results page _(parallel; no deps)_

Replace the basic search results page with a gallery-powered page surfacing results across languages, territories, linguistic genealogy, writing system, videos, and fellows. Evaluate `create_gallery_instance()` in multi-type mode or a dedicated query-and-render pattern.

---

### Phase 3b — PHPStan baseline reduction _(concurrent with Phase 3 and beyond)_

The baseline introduced with PHPStan (PR #435) suppressed 400+ pre-existing violations. CI only catches new regressions — existing debt is frozen unless actively reduced. This phase is not a single pass; it runs alongside other work: when a file is already being touched for a refactor, fix its suppressed errors in the same PR. Regenerate the baseline after each batch.

**Strategy:** fix by file cluster, not by error type. Each batch should be scoped to files already being modified so the diff stays coherent and reviewable.

**Batches (in order of when the files are likely to be touched):**

- **Batch 1 — `taxonomies/`** — CPT/taxonomy consistency refactor (Phase 3 item 5) touches these files; fix their baseline suppressions in the same PR
- **Batch 2 — `template/` and `integrations/`** — fix during or after archive template refactor (Phase 3 item 6)
- **Batch 3 — templates (`single-*.php`, `archive-*.php`, `taxonomy-*.php`)** — fix during Layer 4 prep; these files accumulate the most `get_field not found` suppressions
- **Batch 4 — modules (`modules/`)** — fix during Layer 4 visual baseline work
- **Batch 5 — residual** — whatever remains after Batches 1–4; target zero baseline before Phase 5 integration tests so PHPStan runs clean with no suppressions

**Goal:** zero entries in `phpstan-baseline.neon` before Phase 5. New code after that point must pass PHPStan without suppression.

---

### Phase 4 — Docker + gateway core

_Phase 3 code quality chain (A–E) must complete before Docker so the image captures the final file layout. Stylus migration is deferred to Phase 7 — Docker does not need to capture the final CSS preprocessor state. Gateway sub-phases 0–5 can run in parallel with Docker setup._

#### Dockerize project

Containerize the WordPress install for contributor onboarding and CI-based integration/E2E tests. Must capture the post-Phase 3 file layout.

#### Download gateway plugin — sub-phases 0–5

Downloads currently go through unprotected direct file URLs or `force_download_file()` (proxy streaming, no logging, no auth). Goal: standalone plugin that logs every download, optionally gates access with a name/email modal, supports Dropbox-hosted assets via temporary API links, forwards events to GA4, and auto-anonymizes collected data.

**Architectural decisions (resolved):**
- Signed expiring redirect URLs — not proxy streaming; replaces `force_download_file()`
- CPT strategy: use existing `resources` and `document_files` CPTs — records not yet populated, migration risk is low
- Plugin namespace: `download-gateway` / prefix `dg_`

**Schema additions:**
- `wp_dg_people` — email_hash, email, name, consent fields, anonymization flags
- `wp_dg_download_events` — resource, storage, UTM params, visitor_id, person_id, ip_hash, event_type
- `wp_dg_webhook_delivery` — retry queue and dead-letter
- `wp_dg_tokens` _(not in original spec — required)_ — one-time download tokens with expiry; needed by sub-phases 3 and 5

**Sub-phases 0–5:**
- **0** — Plugin scaffold: activation/deactivation/uninstall hooks, feature flag constant, settings page placeholder, logging conventions
- **1** — Data model: create tables with indexes on activation; idempotent migrations
- **2a** — Core primitives _(unblocks 3)_: PolicyResolver with precedence (per-resource → taxonomy → global), SettingsRepository, EventBus, DownloadEventRepository
- **2b** — Form/gate primitives _(unblocks 5)_: FormSchemaRegistry, Validator, SubmissionService, PeopleRepository, RateLimiter + honeypot, modal UI kit
- **2c** — Deferrable primitives: WebhookDispatcher (retry + dead-letter), RetentionJob skeleton + cron registration
- **3** — Download endpoint: `/dg/download/{token-or-post-id}`, `dg_vid` visitor cookie, click event logging, UTM/referrer capture, IP hashing, no-cache headers
- **4** — Resource authoring: ACF fields on existing CPTs (file_url, storage_type, dropbox_path, version); metabox showing gateway URL; `[dg_download]` shortcode
- **5** — Gate modes: soft gate (skippable) and hard gate (email required); `POST /wp-json/dg/v1/gate`; person upsert; one-time token; nonce + rate limit + honeypot

**Implementation notes:**
- WP Cron fires on page visits only — production retention job should be backed by server cron (`wp cron event run --due-now`)
- Cache plugins must explicitly exclude `/dg/download/` — HTTP headers alone are not sufficient
- `dg_vid` cookie: define whether set unconditionally or only after consent (GDPR/ePrivacy implications)
- Dropbox credentials: store in `wp_options` with `autoload=no`; exclude from any REST API exposure
- ACF fields: use `register_meta` or own ACF JSON within the plugin — do not depend on theme's `acf-json/`
- EventBus: evaluate `do_action('dg/download/click', $event)` before introducing a custom bus class

**Cut lines (if scope must shrink):** Must-have: sub-phases 0–3, 5 (basic hard gate), 9 (retention). Cut first: taxonomy-level policy defaults, admin charts (keep CSV only), webhook retries (keep best-effort), inline gate (keep modal only).

**Testing targets (unit):** PolicyResolver precedence, Validator, token expiry, people upsert
**Testing targets (integration):** endpoint logs and redirects, gate submission yields one-time token, Dropbox temporary link generation

#### Forms _(parallel to gateway sub-phases 0–5)_

- **Report a problem** — lightweight form for users to flag content errors (broken language page, wrong ISO code, etc.)
- **Replace Airtable embed submission forms** — Airtable iframe embeds are brittle and off-brand; replace with native WP forms or custom REST endpoints
- _Download gateway gate form_ — already scoped in gateway sub-phase 5; not duplicated here

#### Better aliveness — dynamic homepage _(before Phase 6 visual baseline)_

The homepage feels static. Surface recently added/updated languages, latest videos, rotate banners for current campaigns. Identify content signals (publication date, editor-curated featured flag). Assess JS vs. server-side rendering. Must land before Layer 4 so dynamic content is captured in baseline screenshots.

---

### Phase 5 — Integration tests + Airtable reconciliation + gateway completion

_Layer 3 requires Docker. Airtable reconciliation requires Layer 5 results (Phase 3). Gateway sub-phases 6–10 require sub-phases 0–5._

#### Layer 3 — Integration Tests

PHPUnit + `WP_UnitTestCase`. Catches hook/filter wiring, CPT registration, REST endpoint responses, DB reads/writes, query correctness. Requires MySQL test database in CI (Docker service). See [docs/testing-strategy.md](docs/testing-strategy.md) for priority targets.

#### Airtable reconciliation

Three known divergence directions:

1. **WP records missing fields** — 520+ language records arrived incomplete. Reconciliation should happen at the Airtable source: institute field requirements and handle divergence before sync.
2. **Airtable records missing from WP** — `_airtable_record_id` backfill (2026-03-01) confirmed gaps: 2 languages (`wyim`, `wyug`), ~3 videos (encoding artifacts), 60 captions, 130 lexicons. All absent records are created automatically on next Airtable modification. To force-close: bulk-touch missing records in Airtable.
3. **Airtable table bloat** — Videos table has 188 fields, most computed or lookup. Correct architecture: resolve linked records in Make.com subscenarios (as Captions already does), then delete Airtable computed columns. Do NOT add more lookup fields — migrate existing ones to subscenarios instead.

#### Download gateway — sub-phases 6–10

- **6** — Storage adapters + Dropbox: local/media/external adapters; Dropbox adapter calls `files/get_temporary_link`, caches briefly, stores credentials in `wp_options` with `autoload=no`
- **7** — GA4 forwarding: EventBus subscriber; client-side where possible; events: `resource_download_click`, `resource_download_gate_submit`, `resource_download_redirect`
- **8** — Admin reporting: date-filtered download table, top resources, CSV export with capability check
- **9** — Retention automation: daily cron nulls email/name after `retention_months`, marks `is_anonymized`; manual run-now button
- **10** — Rollout: convert resources hub first, then top downloads; deprecate `document-download-handler.php` `force_download_file()` once coverage is complete

---

### Phase 6 — Visual baseline + data migration

_Donors must land before the Layer 4 baseline is locked so Donors UI is captured in screenshots. Stylus not required here — the baseline is captured before the preprocessor swap so regressions from that swap are caught in Phase 7. `nations_of_origin` migration requires Airtable reconciliation (Phase 5)._

#### Complete Donors post type

Net new development — requires product definition and data input before implementation can begin. Build before Layer 4 baseline so Donors UI is included in screenshot comparisons.

#### Donation optimization — donor cards in galleries

After Donors CPT lands: integrate donor cards into gallery instances on relevant pages (campaign pages, homepage). Phase 2 (membership/recurring donors with profile features) is deferred pending a separate spec.

#### Layer 4 — End-to-End & Visual Regression _(locks the visual baseline)_

Playwright. Full user flows, JS behaviour, authenticated vs. unauthenticated states, visual layout regressions (screenshot diffs). Nothing that changes template output should land after this without a deliberate baseline update. See [docs/testing-strategy.md](docs/testing-strategy.md) for priority flows.

#### Migrate `nations_of_origin`

`Also spoken in` (the `territories` ACF relationship field) already serves as the linked alternative in the sidebar. Migration requires changing the ACF field type, updating the Make.com sync, and backfilling data. Intentionally deferred until Airtable reconciliation (Phase 5) provides a clean data baseline.

---

### Phase 7 — Features and monitoring requiring the visual baseline

_All items here introduce visual changes or depend on Layer 4. Maps and Stylus must be validated against the established baseline. Performance profiling (Playwright-based) requires Docker + Layer 4._

#### Migrate from Stylus

Stylus is largely unmaintained. `npm audit` flags 3 high-severity findings (dev-only, no production impact). Choose one option before starting; they are mutually exclusive.

**Option A — Dart Sass** _(recommended)_
Sass/SCSS syntax maps almost 1:1 to Stylus. Dart Sass ships as a standalone CLI — same watch/build pattern as today. Resolves all audit findings. No template changes required.
- Rename 42 `.styl` → `.scss`, adjust import syntax, update `package.json` scripts
- Replace `$blue(tint)` color function with Sass `color.adjust()` or `color.mix()`
- Drop `stylus`; add `sass`

**Option B — PostCSS + Vite** _(larger investment; modern foundation)_
Vite as build tool for both CSS and JS. PostCSS plugins provide Stylus-equivalent transforms; Stylus variables become CSS custom properties. JS gets bundled and tree-shaken — resolves the jQuery/bundling gap. Right foundation if Tailwind is ever introduced.
- Add `vite`, `postcss`, `postcss-nesting`, `postcss-preset-env` to `devDependencies`
- Convert Stylus variables to CSS custom properties; replace 4 individually-enqueued JS files with a Vite entry point
- Larger scope — do not start while Phase 6 items are in flight

_Option A can be adopted first; Option B can follow incrementally since Vite supports Sass natively._

#### Maps on territory templates

Territory and region pages would benefit from an embedded map. Applicable to `single-territories.php` and `taxonomy-region.php`. Evaluate Mapbox, Leaflet + OpenStreetMap, Google Maps Embed — ensure no API key is exposed client-side without restriction.

#### Performance profiling and monitoring

No visibility into page load times or query performance in production. Known risk areas: territory pages with large language counts (India: 403, China: 249, Brazil: 200, USA: 197); continent-level region pages aggregating many territories; `get_field()` returning full post objects on relationship fields at scale.

**Goal:** Baseline measurements for key templates (language, territory, region, search); ongoing monitoring (New Relic, Query Monitor in staging, or GitHub Actions synthetic check); alert on regressions.

**Quick win already done:** `get_field('languages', id, false)` on territory pages avoids hydrating hundreds of post objects.

---

### Backlog — known issues, no active fix timeline

- **Fellows meta query scales poorly on continent pages** — `taxonomy-region.php` builds an OR `meta_query` with one LIKE clause per territory (Asia: 215 territories). Not currently failing (`memory_limit = -1` on local and production) but would exhaust a 128 MB limit. [Issue #533](https://github.com/wikitongues/wikitongues.org/issues/533)

---

### Phase 8 — Membership-dependent features

_Blocked on membership infrastructure (user accounts), which is not currently in scope. Write a spec before implementation._

#### Gamification

Stamp rally: users earn stamps for core actions (watch a video, add a language, share a page). Onboarding flow guides new users through first actions. Matches the Wikitongues travel/documentation brand. Hard dependency: membership infrastructure. Write a separate spec before implementation.
