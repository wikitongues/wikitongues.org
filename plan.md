# Wikitongues – Technical Debt & Improvement Plan

This file tracks known issues, deferred refactors, and planned improvements.
Completed work: [plan-archive.md](docs/plan-archive.md) | Testing strategy: [docs/testing-strategy.md](docs/testing-strategy.md)

**Companion documents (authoritative for sequencing and analytics):**
- Product roadmap: `wikitongues-product-roadmap.md` — impact-first sequencing overrides the dependency-ordered phasing below where they conflict
- Analytics strategy: `wikitongues-analytics-strategy.md` — defines GA4/GTM instrumentation, key metrics, and reporting cadence

---

## Table of Contents

- [Roadmap](#roadmap)
- [~~Phase 1~~](#phase-1--security-foundation-)
- [~~Phase 2~~](#phase-2--visual-infrastructure--plugin-hygiene-)
- [Phase 3](#phase-3--code-quality--data-integrity-baseline)
- [Phase 3b](#phase-3b--phpstan-baseline-reduction-)
- [Phase 4](#phase-4--docker--gateway-core)
- [Phase 5](#phase-5--integration-tests--airtable-reconciliation--gateway-completion)
- [Phase 6](#phase-6--visual-baseline--data-migration)
- [Phase 7](#phase-7--features-and-monitoring-requiring-the-visual-baseline)
- [Phase 8](#phase-8--membership-dependent-features)
- [Backlog](#backlog--known-issues-no-active-fix-timeline)

---

## Roadmap

Phases are ordered by dependency. Items within a phase can be parallelized. **Note:** The product roadmap (`wikitongues-product-roadmap.md`) sequences work by impact rather than dependency. Where the two conflict, the roadmap's sequencing wins — several items below (download gateway sub-phases 0–5, FundraiseUp ACF, Donors CPT) ship ahead of their plan.md phase gates. This document remains the authoritative source for technical specs and dependency chains; the roadmap is authoritative for what ships when.

**Key dependency chains:**

- `Secrets scanning` → integromat-connector audit ✅ → Make.com scenario audit ✅ → `wt-airtable-sync` ✅ → retire integromat-connector write paths ✅
- `Make.com scenario audit` ✅ → `Airtable reconciliation` _(soft: audit findings narrow reconciliation scope)_
- ~~`Evaluate Bedrock`~~ ✅ → code quality cleanups proceed in current form _(decision: No — see [archive](docs/plan-archive.md))_
- `Duplication fix` → `Root includes move` → `Reorganize includes` → `Docker` _(Docker must capture final file layout)_
- ~~`Font Awesome`~~ ✅ → `Docker` → **Layer 4 visual baseline** → `Stylus migration` _(deferred)_
- `Donors CPT` _(Phase 6)_ → `Donation optimization` _(roadmap: Track 1B, ships in Phase 1 ahead of Layer 4)_
- `Archive template refactor` + `Autoloader` → `Docker` (Phase 4)
- `PHPStan baseline reduction` → zero suppressions before `Layer 3` (Phase 5)
- `Docker` → `Layer 3` → gateway integration tests
- `Docker` → `Layer 4` → maps, performance profiling
- `Layer 5 Data Integrity` → `Airtable reconciliation` → `nations_of_origin migration`
- `Enhanced search results page` → `Layer 4 visual baseline` (Phase 6) _(roadmap: Track 2C, Phase 2 Engagement Features)_
- `Better aliveness` → before `Layer 4 visual baseline` (Phase 6) _(roadmap: Track 2A, Phase 2 Engagement Features)_
- `Forms` (report/Airtable replace) — no hard deps; `Forms` (gate) → Download gateway sub-phase 5
- `Download gateway` → `Visitor engagement profile` → `Retention campaign personalization`
- `Visitor engagement profile` + `Membership` _(board decision)_ → `Language passport` → `Gamification` → Phase 8+

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

#### ~~6. Archive template refactor~~ ✅

See [archive](docs/plan-archive.md) (PR #536).

#### ~~7. `gallery-territories.php` + `archive-territories.php` fixes~~ ✅

See [archive](docs/plan-archive.md) (PR #538).


#### ~~8. Fellows ACF field audit~~ ✅

See [archive](docs/plan-archive.md) (PR #541).

#### ~~9. Root-level file hygiene~~ ✅

See [archive](docs/plan-archive.md) (PRs #498–500, #542).

#### 10. Layer 5 — Data Integrity _(parallel; no Docker required)_

Weekly WP-CLI command (`wp wt integrity check`) against the live DB. See [docs/testing-strategy.md](docs/testing-strategy.md) for full spec, priority checks, and implementation approach.

#### 11. Enhanced search results page _(parallel; no deps)_

Replace the basic search results page with a gallery-powered page surfacing results across languages, territories, linguistic genealogy, writing system, videos, and fellows. Evaluate `create_gallery_instance()` in multi-type mode or a dedicated query-and-render pattern.

#### 12. Airtable sync — Slack change notifications _(parallel; no deps)_

Two-part feature: plugin returns a field-level diff in the sync response; Make.com posts a Slack message when the diff is non-empty or a new record is created.

**Part 1 — Plugin: changed-fields diff in sync response**

Add a diff step to `Sync_Controller::sync()` in `wt-airtable-sync`:

1. Before writing, read the current stored value of each mapped field via `get_field()` / `get_post_meta()`.
2. After resolving incoming values (same logic as `write_meta()`), compare old vs new.
3. Exclude fields where old and new are identical — no write-without-change noise.
4. Return a `changed` key in the live-write response alongside the existing fields:

```json
{
  "status": "ok",
  "action": "updated",
  "post_id": 12345,
  "post_title": "Polynesian",
  "changed": {
    "public_status": { "old": "draft", "new": "publish" },
    "featured_languages": { "old": [42], "new": [42, 87] }
  }
}
```

For `created` records, `changed` lists all written values with `"old": null`. `video_thumbnail_v2` (attachment ID) is excluded from the diff — its changes are implicit when the thumbnail module runs.

**Part 2 — Make.com: Slack module with human-readable diff**

Add a Slack module at the end of each scenario, after the sync POST:

- **Gate:** only fire when `action == "created"` OR `changed` is non-empty. Skip entirely on unchanged updates.
- **post_object resolution:** `changed` values for relationship fields are WP post IDs. Use a "Resolve WP Post" subscenario (see below) to convert them to titles before formatting the message.
- **Message format:** `[Videos] Updated "Polynesian" — public_status: draft → publish | featured_languages: added "English"`
- **Channel:** configurable in each scenario's SetVariables module (same pattern as `wp_base_url`).

**"Resolve WP Post" subscenario pattern**

Mirrors the Captions subscenarios that resolve Airtable linked record IDs. Instead of calling Airtable, call the standard WP REST API:

```
GET {{wp_base_url}}/wp-json/wp/v2/{post_type}/{id}?_fields=id,title
```

No custom endpoint required — the built-in WP REST API already exposes this. One subscenario per target CPT (languages, videos, etc.) or a single generic one parameterised by `post_type` + `id`. The auth keychain used for media uploads (basicAuth) is reused — no new credentials needed.

**Scope boundaries:**
- Only scalar text fields and resolved post_object titles in the notification — no binary/attachment diffs.
- `video_thumbnail_v2` excluded from diff display.
- Staging and production scenario instances each notify their own channel (same per-environment convention as `wp_base_url`).

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

_**Roadmap note:** Gateway sub-phases 0–5 are roadmap Track 1A and ship immediately as the primary email capture engine. Docker setup follows on its own timeline. See `wikitongues-product-roadmap.md` Phase 1._

#### Dockerize project

Containerize the WordPress install for contributor onboarding and CI-based integration/E2E tests. Must capture the post-Phase 3 file layout.

#### Download gateway plugin — sub-phases 0–5

Downloads currently go through unprotected direct file URLs or `force_download_file()` (proxy streaming, no logging, no auth). Goal: standalone plugin that logs every download, optionally gates access with a name/email modal, supports Dropbox-hosted assets via temporary API links, forwards events to GA4, and auto-anonymizes collected data.

**Architectural decisions (resolved):**
- Signed expiring redirect URLs — not proxy streaming; replaces `force_download_file()`
- CPT strategy: `documents` + `document_files` (existing, in active use); `resources` CPT not used
- Downloadable unit is the leaf node (`document_files` post, `videos` post, etc.) — selection UI stays in theme templates; gateway is post-type-agnostic
- Plugin namespace: `download-gateway` / prefix `gateway_`
- `FileResolverRegistry` maps post types to `FileResolver` implementations; `DocumentFileResolver` handles `document_files` via ACF `file` field; future types (videos, captions) register the same interface

**Schema additions:**
- `wp_gateway_people` — email_hash, email, name, consent fields, anonymization flags
- `wp_gateway_download_events` — resource, storage, UTM params, visitor_id, person_id, ip_hash, event_type
- `wp_gateway_webhook_delivery` — retry queue and dead-letter
- `wp_gateway_tokens` — one-time download tokens with expiry; needed by sub-phases 3 and 5

**Sub-phases 0–5:**
- [x] **0** — Plugin scaffold: activation/deactivation/uninstall hooks, `GATEWAY_ENABLED` feature flag, settings page placeholder, Logger (PR #560)
- [x] **1** — Data model: 4 tables created via `dbDelta()` on activation; idempotent (PR #560)
- [x] **2a** — Core primitives: `PolicyResolver` (per-resource → taxonomy → global), `SettingsRepository`, `EventBus` (namespaced WP hooks), `DownloadEventRepository` (PR #560)
- [x] **2b** — Collapsed into sub-phase 5: PeopleRepository, GateController, rate limiter (transients), honeypot, modal UI
- **2c** — Deferrable primitives: WebhookDispatcher (retry + dead-letter), RetentionJob skeleton + cron registration
- [x] **3** — Download endpoint: `GET /wp-json/gateway/v1/download/{token-or-post-id}`, `gateway_vid` visitor cookie, click + redirect event logging, IP hashing, no-cache headers. Tested on localhost — 302 redirect confirmed (PR #560)
- [x] **4** — Resource authoring: ACF gate policy override field (per-resource, via `acf_add_local_field_group()`), metabox showing gateway URL + shortcode snippet, `[gateway_download]` shortcode. All three validated on localhost.
- [x] **5** — Gate modes: soft (skippable modal) and hard (email required); `POST /wp-json/gateway/v1/gate`; PeopleRepository upsert; one-time token; nonce + rate limit + honeypot. All policy permutations validated on localhost.

**Implementation notes:**
- WP Cron fires on page visits only — production retention job should be backed by server cron (`wp cron event run --due-now`)
- Cache plugins must explicitly exclude `/gateway/download/` — HTTP headers alone are not sufficient
- `gateway_vid` cookie is set unconditionally on first download; GDPR/ePrivacy implications TBD before gate launch
- Dropbox credentials: store in `wp_options` with `autoload=no`; exclude from any REST API exposure
- ACF fields: own ACF JSON within the plugin — do not depend on theme's `acf-json/`
- EventBus wraps WP `do_action`/`add_action` with `gateway/` namespace prefix
- Admin UI for download data: `wp_gateway_download_events` → sub-phase 8 (reporting, CSV export); `wp_gateway_people` → sub-phase 9 (retention management, anonymization audit, manual run-now)

**Cut lines (if scope must shrink):** Must-have: sub-phases 0–3 ✅, 5 (basic hard gate), 9 (retention). Cut first: taxonomy-level policy defaults, admin charts (keep CSV only), webhook retries (keep best-effort), inline gate (keep modal only).

**Testing targets (unit):** ✅ IpHasher (12), TokenRepository (12), FileResolverRegistry + DocumentFileResolver (11), VisitorId (8), DownloadController::resolve() (10) — 53 tests total
**Testing targets (integration):** endpoint logs and redirects ✅ (manual), gate submission yields one-time token, Dropbox temporary link generation

#### Forms _(parallel to gateway sub-phases 0–5)_

- **Report a problem** — lightweight form for users to flag content errors (broken language page, wrong ISO code, etc.)
- **Replace Airtable embed submission forms** — Airtable iframe embeds are brittle and off-brand; replace with native WP forms or custom REST endpoints
- _Download gateway gate form_ — already scoped in gateway sub-phase 5; not duplicated here

#### Better aliveness — dynamic homepage _(before Phase 6 visual baseline)_

_(Roadmap: Track 2A, Phase 2 Engagement Features)_

The homepage feels static. Surface recently added/updated languages, latest videos, rotate banners for current campaigns. Identify content signals (publication date, editor-curated featured flag). Assess JS vs. server-side rendering. Must land before Layer 4 so dynamic content is captured in baseline screenshots.

#### Retention & discovery email campaign _(parallel; no code deps)_

_(Roadmap: Track 1D, Phase 1 Fix the Funnel)_

Nurture sequence turning language exploration into recurring donations. Core thesis: discovery and travel — users see a set of languages, receive an email campaign featuring associated languages, with the goal of driving monthly donations. Full spec in `wikitongues-product-roadmap.md` Track 1D.

**Codebase touchpoints:**
- UTM parameter conventions for all email links (must be consistent with GA4 channel grouping)
- Email provider integration (API or webhook for subscriber management)
- Newsletter subscribe event (`newsletter_subscribe`) already instrumented in GTM
- Download gateway (sub-phases 0–5) provides the primary email capture mechanism

#### Visitor engagement profile _(parallel; depends on download gateway for email capture)_

Data infrastructure for tracking content engagement per email-known visitor. This is the foundation that the retention campaign personalizes from, and that the user-facing passport (Phase 8) eventually surfaces.

**Visitor identity progression:**

1. **Anonymous** — GA4 tracks aggregate behavior via `content_type` dimension. No PII. Current state.
2. **Email-known** — Download gateway or newsletter captures email. Engagement can be tied to an individual via hashed email. No account, no password. Enables personalized retention emails.
3. **Member** — Full account with password. User can see their own passport, earn stamps. **Blocked on board-level strategic decision** about what membership means for Wikitongues — scope, benefits, feel, impact. This is not a development task until the board decides.

**What to build now (layers 1–2 only):**

- `wp_gateway_people` table (already spec'd in download gateway sub-phase 1) stores the email-known visitor
- Engagement log table: `visitor_id` (FK to `wp_gateway_people`), `content_type`, `content_slug`, `event_type` (view, download, donate_click), `timestamp`
- Write hook: on `page_view` events where a `gateway_vid` cookie maps to a known person, log the content interaction
- Read API: given an email hash, return content types and slugs engaged with — consumed by retention campaign for personalization

**What NOT to build now:**

- User-facing passport UI (requires membership — Phase 8)
- Gamification / stamps (requires membership — Phase 8)
- Account creation, login, password management (requires board decision)

**Dependency note:** The engagement log extends the download gateway's `wp_gateway_people` table. It can ship as part of gateway sub-phases 6–10 or as a standalone addition after sub-phase 5.

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

_(Roadmap: Track 1B, ships in Phase 1 ahead of Layer 4 gate)_

Net new development — requires product definition and data input before implementation can begin. Build before Layer 4 baseline so Donors UI is included in screenshot comparisons.

#### Donation optimization — donor cards in galleries

_(Roadmap: Track 1B Phase 2, ships after Donors CPT)_

After Donors CPT lands: integrate donor cards into gallery instances on relevant pages (campaign pages, homepage). Phase 2 (membership/recurring donors with profile features) is deferred pending a separate spec.

#### FundraiseUp campaign management via ACF _(before Layer 4 — banner changes must be captured in baseline)_

_(Roadmap: Track 1C, ships in Phase 1 as part of giving page redesign)_

Move all FundraiseUp configuration out of hardcoded PHP into a new ACF options page ("Fundraising"), and add an admin-driven campaign banner slot.

**Three deliverables:**

**1. ACF options page + field group**

New "Fundraising" options page under General. Two sections:

- _Default campaign_ — `fundraiseup_org_id` (text), `default_element_id` (text), `default_campaign_id` (text). Replaces the hardcoded org ID in `page--head.php` and element/campaign IDs in `single-fellows.php` and `meta--languages-single.php`.
- _Active campaign_ — `active_campaign_status` (select: disabled / active / scheduled), `active_campaign_label` (text, admin-only), `active_campaign_id` (text), `active_campaign_element_id` (text), `active_campaign_start` (date_picker), `active_campaign_end` (date_picker), and an `active_campaign_banner` group (see deliverable 3).

**2. `wt_active_campaign()` helper**

New function in `template-helpers.php`. Returns the active point-in-time campaign data if status is `active`, or if status is `scheduled` and today falls within start/end dates; otherwise returns the default campaign. All templates consume this single function — no IDs hardcoded anywhere.

**3. Campaign banner module + header integration**

`active_campaign_banner` group fields: `show_banner` (true_false), `heading` (text), `body` (textarea), `cta_label` (text), `variant` (select: standard / urgent), `display_scope` (checkbox: all / home / archive / singles).

New `banner--campaign.php` module. `header.php` gains two ordered banner slots: campaign banner (if active + `show_banner` + scope matches), then the existing alert banner. The two slots serve distinct purposes — campaign banner is fundraising-specific; alert banner remains for general announcements.

**What this enables:** campaign launches and banner copy changes require no deploy. EOY campaigns, point-in-time drives, and future raises are managed entirely from admin.

**Dependency note:** no Docker dependency; can start independently. Must land before Layer 4 so banner UI is captured in visual baseline.

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

### Phase 8 — Membership-dependent features

_**Blocked on board-level strategic decision.** Membership — what it means for Wikitongues, its scope, benefits, feel, and impact — is a strategic question that rises above website development objectives. This phase does not begin until the board decides what membership looks like. Technical implementation follows that decision, not the other way around._

_The visitor engagement profile (Phase 4) accumulates data in the background without requiring membership. When Phase 8 begins, that data is ready to surface._

#### Language passport

User-facing view of their engagement profile — languages explored, territories visited, videos watched, downloads. Requires authenticated access (account with password or token-based). The data layer already exists from the visitor engagement profile; this phase adds the UI and the account system.

#### Gamification

Stamp rally: users earn stamps for core actions (watch a video, add a language, share a page). Onboarding flow guides new users through first actions. Matches the Wikitongues travel/documentation brand. Hard dependency: membership infrastructure + language passport. Write a separate spec before implementation.

---

### Backlog — known issues, no active fix timeline

- **Fellows meta query scales poorly on continent pages** — `taxonomy-region.php` builds an OR `meta_query` with one LIKE clause per territory (Asia: 215 territories). Not currently failing (`memory_limit = -1` on local and production) but would exhaust a 128 MB limit. [Issue #533](https://github.com/wikitongues/wikitongues.org/issues/533)