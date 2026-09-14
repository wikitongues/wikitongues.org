# Wikitongues – Completed Work

Completed work from [plan.md](../plan.md). The **index** lists each finished initiative and the doc that now describes it. The **log** below it records each change, newest first, with its branch and PR.

When something in the plan is done: remove it from `plan.md`, add a log entry here, add or update its index row, and make sure the system it built is described in a doc under `docs/`, creating one if needed.

---

## Index

| Initiative | Shipped | PRs | Described in |
|---|---|---|---|
| Plan and docs restructure | 2026-09-13 | [#643] | [plan.md](../plan.md), this file |
| Deploy and backup hardening | 2026-09-12 | [#633]–[#636], [#639], [#640] | [deployment.md](deployment.md), [staging-sync.md](staging-sync.md) |
| Financials (Form 990s) | 2026-09-11 | [#630] | [content-model.md](content-model.md#financials-form-990s) |
| People content model | 2026-09-08 – 11 | [#616], [#622], [#623], [#626] | [content-model.md](content-model.md#people) |
| Editorial blocks on video and fellow-category pages | 2026-09-09 | [#620], [#621] | [content-model.md](content-model.md#editorial-content) |
| Gallery fixes | 2026-09-08 – 11 | [#612], [#613], [#615], [#623] | [gallery.md](gallery.md) |
| GitHub issue triage | 2026-09-08 | [#611] | [plan.md](../plan.md) |
| Download gateway | 2026-03-14 – 09-11 | [#560]–[#598], [#619], [#623] | [download-gateway.md](download-gateway.md) |
| Donation plumbing (FundraiseUp audit, donate CTAs) | 2026-03-10 – 14 | [#544], [#548], [#553], [#557] | This log |
| Code-quality chain (plan Phase 3, items 1–9) | 2026-03-05 – 06 | [#501], [#520], [#529], [#536], [#538], [#541], [#542] | [content-model.md](content-model.md), [gallery.md](gallery.md) |
| Staging data sync | 2026-03-05 | [#509], [#518] | [staging-sync.md](staging-sync.md) |
| Airtable → WordPress sync | 2026-02-22 – 03-01 | [#482], [#486], [#489] | [airtable-sync.md](airtable-sync.md) |
| Territories | 2026-02-20 – 28 | [#445], [#446], [#450]–[#455], [#491] | [content-model.md](content-model.md#territories-and-regions) |
| Writing-system and genealogy taxonomies | 2026-02-22 | [#467], [#471] | [content-model.md](content-model.md#taxonomies) |
| Font Awesome replaced with inline SVGs | 2026-02-22 | [#477] | This log |
| Security foundation (secrets scanning, plugin and Make audits) | 2026-02-21 – 22 | [#459], [#463] | This log; the Make audit is in `docs/local_docs/` (not committed) |
| Bedrock evaluated and declined | 2026-02-28 | — | This log |
| Tests and static analysis (PHPCS, PHPUnit, PHPStan) | 2026-02-19 | [#428], [#432], [#435], [#438], [#441] | [testing-strategy.md](testing-strategy.md) |
| CI and deployment pipeline | 2026-02-19 – 03-05 | [#423], [#425], [#503], [#511] | [deployment.md](deployment.md) |

---

## 2026-09-13 (Plan and docs)

### Website plan restructured; docs for completed systems
**Branch:** `docs/cc/plan-archive-refresh` · **PR:** [#643]

- `plan.md` was rewritten as an ordered queue plus workstream specs. It now owns the order of website work; the product roadmap remains the organization-level strategy. Completed phases were removed, and the gates on Docker, the visual-regression baseline and a zero PHPStan baseline were dropped: engineering foundations no longer block features.
- This archive gained the index above and the March–September 2026 entries below.
- New docs: [content-model.md](content-model.md), [deployment.md](deployment.md) and [gallery.md](gallery.md). [download-gateway.md](download-gateway.md) was rewritten against the code.
- Stale facts corrected in `readme.md`, [testing-strategy.md](testing-strategy.md), [airtable-sync.md](airtable-sync.md) and `CLAUDE.md`.

---

## 2026-09-12 (Deploy and backup hardening)

### Backups outside the web root, deploys that delete, a pinned host key
**PRs:** [#633], [#634], [#635], [#636], [#639], [#640]

- **Backups.** Backup Prod DB and Sync to Staging now write and read `~/backups/prod_dump.sql`, created with mode 700 under a `077` umask, outside the web root. The old dump folder was deleted ([#633], [#636]).
- **Deletes.** Deploys remove files deleted from the repo, within the theme and the four custom plugins, with safeguards: a partial-checkout check, `--max-delete=100`, and a protected `error_log`. The first run removed about 80 leftovers per server, including retired templates and includes from before the folder reorganization. The Donate page was moved off a deleted template first ([#634]).
- **Host key.** All four server workflows trust a pinned host key from the `SSH_HOST_KEY` secret instead of `ssh-keyscan` ([#635]).
- **Logs.** Debug logging that wrote on every REST request was removed; the PHP error logs had grown to 1–2 GB ([#639]).
- **Tooling.** `CLAUDE.md`, `.claude/` and `.vscode/` no longer deploy ([#640]).

Described in [deployment.md](deployment.md) and [staging-sync.md](staging-sync.md).

---

## 2026-09-11 (Financials; People follow-ups)

### Form 990 financials page replaces monthly reports
**Branch:** `feature/cc/form-990-financials` · **PR:** [#630]

- New `form_990` post type. `/financials` lists every filing, newest tax year first, and the footer links to it beside the Candid seal.
- The `reports` post type (monthly updates, last posted March 2024) is retired; `/reports` and `/reports/*` redirect with a 301. Its posts and files stay in the database for now.
- Filings link straight to the media library, not through the download gateway, by decision.
- 8 new tests (223 total).

Described in [content-model.md](content-model.md#financials-form-990s).

### People migration and styling follow-ups
**PRs:** [#623], [#624], [#626], [#628]

- The `wp wt migrate-people` dry run now resolves people the way `--execute` does; only the preview had been wrong ([#626]).
- An editorial block's secondary download button carried two `class` attributes, so it skipped the gateway; fixed. The gallery card look also became opt-in (`gallery-item--card`) after the People change flattened fellow cards ([#623]).
- Lighter dark blocks and card hover states ([#624]).
- Documented the rule to refresh from production before any migration or bulk write ([#628]).

---

## 2026-09-09 (People; editorial blocks)

### `team` renamed to `people`, typed by taxonomy
**Branch:** `feature/cc/people-cpt` · **PR:** [#622]

- The post type now covers everyone connected to the organization, typed by a `people-type` taxonomy (Board Member, Staff, Volunteer, Advisor), so one person can hold several types.
- The Board, Advisors and Staff pages share `template-people.php`, composed from editorial gallery rows filtered by type. New people pages need no deploy.
- Locked down ahead of donor records: no REST route (the old `/wp-json/wp/v2/team` listed all 52 people), no search, no archive.
- Also fixed: curated gallery selections lost their order, and the staff page's careers galleries matched nothing.
- Migration: `wp wt migrate-people`. The old list fields were kept, so the change is reversible.

Described in [content-model.md](content-model.md#people).

### Editorial blocks on video and fellow-category pages
**PRs:** [#620], [#621]

- Fellow-category pages render the term's own banner, then the gallery, then an editorial block; existing banners were migrated with `wp wt migrate-fellow-category-banner` ([#620]).
- Every video page shows one shared editorial block, configured under Videos → Video Settings ([#621]).

### Oral-history CTAs routed through the document page
**PR:** [#619]

- Two video CTAs linked the oral-history PDF directly, bypassing the gateway; they now link its document page. Three links to the same PDF in admin content were left to fix by hand.

---

## 2026-09-08 (Gallery fixes; triage)

### Gallery fixes
**PRs:** [#612], [#613], [#615]

- Random-order galleries re-shuffled on every pagination request; the shuffle is now seeded once per render (#380, [#615]).
- Removed the `custom_gallery_id` field that editors were asked to fill but no code read (#421, [#612]).
- Fixed an undefined `$post_ids` warning in gallery builders ([#613]).

Described in [gallery.md](gallery.md).

### Shared "Global: Social Links" group
**PR:** [#616]

- The duplicated social fields on Team and Fellows became one ACF group attached to both, which gave team members a YouTube field (#59). Field keys and meta keys were reused, so no data moved.

### GitHub issue triage
**PR:** [#611]

- The 15 open issues were checked against the code and sequenced in the plan. Four were resolved or closed the same day: #59, #73, #380 and #421 (#73 no longer reproduced).

### Project tooling and docs
**PRs:** [#605], [#609], [#610], [#614]

- Claude Code project config committed: `CLAUDE.md` and the `/deploy` and `/test` commands ([#605]).
- Documented the change of the Airtable People *Last seen* field to a lookup, and its fallout ([#609]). Logged the duplicate Download records for cleanup ([#610]). Clarified that pre-PR checks run all three gates ([#614]).

---

## 2026-09-07 (Documents through the gateway)

### Documents routed through the download gateway
**PRs:** [#595], [#598], [#600]

- Every document download surface (versions table, banner, card blocks) goes through the gateway. The legacy `force_download_file()` handler and `/force-download/` route were removed ([#598]).
- The webhook cron event now re-registers itself if lost. It had gone missing, and 208 webhooks sat undelivered for about five months ([#595]).

Part of the [download gateway](#2026-03-27-download-gateway).

### Translate-a-document invitation
**PR:** [#606]

- Document pages invite readers to help translate, with a pre-filled email to hello@wikitongues.org below the versions table.

### Card grids render three columns
**PR:** [#602]

- `.thirds` card grids became CSS grids (3, 2 or 1 columns by width); the flex layout had wrapped the third card.

---

## 2026-03-27 (Download gateway)

### Download gateway: sub-phases 0–10
**PRs:** [#560], [#561], [#563]–[#568], [#570], [#572]–[#574], [#577]–[#579], [#582], [#585], [#588], [#589], [#592], [#595], [#598]
**Built:** 2026-03-14 to 2026-09-07 · **In production:** 2026-03-27

A plugin that sits between every download link and its file. It logs each download, can ask for a name and email, asks follow-up questions, and forwards contacts and downloads to Airtable through Make.

- **Mar 14–16:** scaffold, schema, the download endpoint with single-use tokens, soft and hard gates, the retention job, per-type policies and `disabled`, and the follow-up form infrastructure.
- **Mar 21–27:** the Dropbox adapter and the video and caption resolvers; the signed person cookie and fixes from a security review; named form sets; the webhook queue; the modal redesign; GA4 events. First production release on Mar 27.
- **Apr 12–19:** admin reporting dropped, since Airtable views cover it; payloads carry `airtable_record_id` and `download_event_id`.
- **Sep 7–11:** self-healing cron schedule; documents routed through the gateway; CTA fixes.
- **Not built:** the anonymization webhook (9b), now in the plan.

**Decisions:**
- Tokens and redirects, not streaming.
- Forms in code, not ACF.
- No donation ask in the modal; the post-download email carries it.
- No reporting screen.
- Form 990s bypass the gateway.

Described in [download-gateway.md](download-gateway.md).

---

## 2026-03-15

### Captions listing on video pages
**PR:** [#562]

- The captions query compared the serialized `source_video` value with `=`, so it found nothing; it now matches `LIKE '"{id}"'`. The section is hidden when no caption has a file.

---

## 2026-03-14 (Donate CTAs)

### Donation CTA tracking and fixes
**PRs:** [#553], [#554], [#557]

- Donation elements carry data attributes for analytics ([#553]).
- The fellow-page donate CTA no longer reloads the page or fires the wrong analytics event ([#557]).
- Fellows gallery cards tweaked ([#554]).

---

## 2026-03-10 (FundraiseUp audit)

### FundraiseUp audit
**Branch:** `chore/cc/fundraiseup-audit` · **PRs:** [#544], [#545]

- The disabled 2024 year-end campaign ID was replaced with the General Fund in the fellow and giving-campaign templates.
- The language-page donate CTA was a `<button href>`, which doesn't navigate; it is now an `<a>`.
- `/2024-fundraiser` redirects to `/donate` (301).
- The `cli` includes folder is now loaded.
- The FundraiseUp-in-ACF item was added to the plan.
- Outside the repo, the same week: FundraiseUp campaigns and elements were consolidated into one General Fund, FundraiseUp was connected to GA4 through GTM, and every donate placement was instrumented.

### Legacy analytics and CSS leftovers
**PRs:** [#548], [#550]

- Removed a newsletter-form handler that called a function that no longer existed ([#548]), and a stale `blankslate.css` import that 404ed on staging ([#550]).

---

## 2026-03-06 (Navigation)

### Sub-menu width
**PR:** [#537]

- Long navigation items no longer clip in sub-menus.

---

## 2026-03-06 (Phase 3 item 6)

### Phase 3 item 6 — Archive template refactor + `wt_gallery_params()` helper
**Branch:** `refactor/cc/archive-template-helper`
**PR:** [#536](https://github.com/wikitongues/wikitongues.org/pull/536)

- Added `wt_gallery_params( array $overrides ): array` to `template-helpers.php` — merges caller-provided keys with a full set of defaults so callers only specify what differs
- Renamed `wt_archive_params()` → `wt_gallery_params()` and added `custom_class` to defaults
- Expanded usage from 4 archive files to all 26 `create_gallery_instance()` call sites across 18 files — eliminates 17–19 key boilerplate arrays throughout the theme
- Added full `@param`/`@return` PHPDoc array shape annotations — PHPStan enforces valid keys and types; Intelephense provides hover docs for all 18 params
- Fixed `posts_per_page: '50'` (string) → `50` (int) in `taxonomy-fellow-category.php`
- Fixed `show_total` missing/incorrect in `template-about-staff.php`
- Added `docs/gallery-inventory.csv` + `docs/gallery-inventory-main.csv` inventorying every gallery's effective settings before and after
- Added `.vscode/extensions.json` recommending PHP Intelephense for contributors

---

## 2026-03-06 (Phase 3 item 9)

### Phase 3 item 9 — Root-level file hygiene
**PRs:** [#498](https://github.com/wikitongues/wikitongues.org/pull/498)–[#500](https://github.com/wikitongues/wikitongues.org/pull/500), [#542](https://github.com/wikitongues/wikitongues.org/pull/542)

- `plan-archive.md` moved to `docs/`; `.DS_Store` gitignored; `docs/local_docs/` structure established (PRs #498–500)
- Untracked stale files audit: working tree clean, no orphaned scripts or exports found
- `npm audit fix` resolved `ajv` (moderate ReDoS) and `minimatch` (high ReDoS) — both transitive dev deps of the Stylus toolchain; Stylus build verified clean after fix (PR #542)

---

## 2026-03-06 (Phase 3 item 8)

### Phase 3 item 8 — Fellows ACF field audit
**Branch:** `chore/cc/fellows-acf-audit`
**PR:** [#541](https://github.com/wikitongues/wikitongues.org/pull/541)

Audited all 23 ACF fields on the `fellows` CPT against all templates that read them. Findings:

- **Removed `fellow_headshot`** (image) — duplicated by `fellow_banner.banner_image`; `carousel--testimonial.php` updated to read from `fellow_banner` instead, with `esc_attr()` and `esc_url()` fixes
- **Removed `fellow_bio`** (textarea) — content migrated to editorial content blocks (`text_layout`); removed the `get_field('fellow_bio')` block from `single-fellows.php`
- **Removed `fellow_collaborators`** (post_object) — no PHP reads anywhere; unused
- **Removed `custom_links`** (repeater) — no PHP reads anywhere; unused
- **Social links group reverted** — attempted to group the 8 social link fields; reverted after confirming ACF group fields cause existing postmeta data to become inaccessible without a DB migration
- All 19 remaining fields confirmed active and in use

---

## 2026-03-06 (Phase 3 item 7)

### Phase 3 item 7 — `gallery-territories.php` + `archive-territories.php` fixes
**Branch:** `fix/cc/territories-gallery-fixes`
**PR:** [#538](https://github.com/wikitongues/wikitongues.org/pull/538)

All four `gallery-territories.php` bugs were already fixed in the plugin prior to this PR:
- Double query (no_found_rows) — already removed; `found_posts` read from single query
- XSS in alt attribute — already uses `esc_attr( get_the_title() )`
- Blank label — already uses `get_field('standard_name', ...) ?: get_the_title()`
- Filter bypass — already uses `get_the_title()` for video lookup

Remaining fix applied in this PR:
- Added explicit `'include_children' => true` to the `tax_query` built in `build_gallery_query_args()` (`wt-gallery/includes/queries.php`) — WP already defaults to `true` but making it explicit prevents a silent regression if the query builder is ever changed

---

## 2026-03-05 (Phase 3 items 4–5)

### Phase 3 item 4 — Reorganize theme `includes/` into subdirectories + autoloader
**Branch:** `chore/cc/reorganize-includes`
**PR:** [#529](https://github.com/wikitongues/wikitongues.org/pull/529)

Reorganized the flat `includes/` directory (24 files) into subdirectories by concern and replaced the manual `require_once` list in `functions.php` with a directory-scanning autoloader:

- `admin/` — `admin-helpers.php`, `batch-operations.php`
- `api/` — `rest-endpoints.php`
- `integrations/` — `acf-helpers.php`, `document-download-handler.php`, `events-filter.php`
- `taxonomies/` — all CPT/taxonomy registration files (moved from `custom-post-types/`)
- `template/` — `enqueue-scripts.php`, `menus.php`, `router.php`, `search-filter.php`, `template-helpers.php`

Also deleted one-time migration tools now superseded by Make.com: `import-captions.php` (+ its 44 unit tests) and `wp-cli-import.php`. Removed stale PHPStan baseline entries for both deleted files.

### Phase 3 item 5 — CPT/taxonomy file consistency refactor
**Branch:** `chore/cc/reorganize-includes`
**PR:** [#529](https://github.com/wikitongues/wikitongues.org/pull/529)

After moving all CPT files to `includes/taxonomies/`, cross-file audit and fixes:

- Created missing CPT files: `blogs.php`, `careers.php`, `events.php` (previously inlined in `functions.php`)
- Refactored `careers.php` from a class to plain functions; removed dead `register_acf_fields()` method
- Fixed textdomains: `blogs.php` wrapped bare strings in `__()`, `events.php`/`faq.php` changed `'textdomain'` to CPT slug
- Fixed wrong comment in `events.php` ("Register Custom Post Type for FAQs")
- Added `taxonomies` key to `reports.php` to match other CPT registrations
- Fixed XSS in `faq.php`: column handler uses `wp_kses_post()`, shortcode uses `esc_html( get_the_title() )`
- Fixed null-deref in `documents.php`: `->post_title` access guarded with ternary on `get_field()` return
- Added `show_in_rest` and `is_main_query()` guard to `fellows.php`

---

## 2026-03-05 (Phase 3 items 2–3 + Infrastructure + Features)

### Phase 3 item 3 — Remove dead post-object-helpers / REST controller chain
**Branch:** `chore/cc/remove-dead-post-object-helpers`
**PR:** [#520](https://github.com/wikitongues/wikitongues.org/pull/520)

Deleted the dead code chain left behind from the old Make.com v1 (Integromat) write path, retired 2026-03-01:

- Deleted `wp-content/themes/blankslate-child/includes/class-wt-rest-posts-controller.php`
- Deleted `wp-content/themes/blankslate-child/includes/post-object-helpers.php`
- Deleted `includes/post-object-helpers.php` (orphaned root copy)
- Removed `'rest_controller_class' => 'WT_REST_Posts_Controller'` from all 7 CPT registrations (languages, videos, captions, territories, lexicons, partners, resources). WordPress now uses the default `WP_REST_Posts_Controller`.
- Removed `require_once 'includes/class-wt-rest-posts-controller.php'` from `functions.php`
- Deleted the now-empty root `includes/` directory and removed its entry from `phpcs.xml`
- Regenerated `phpstan-baseline.neon` to remove 2 stale entries

**Why it was dead:** `handle_post_object()` was only triggered by `_WT_TMP_*` fields in REST request bodies. All 3,376 such rows were deleted from `wp_postmeta` on 2026-03-01 when Make.com v1 scenarios were disabled.

---

### Phase 3 item 2 — Staging environment data sync
**Branch:** `chore/cc/staging-sync-runbook`
**PR:** [#518](https://github.com/wikitongues/wikitongues.org/pull/518)

Completed the staging sync setup with a runbook at [`docs/staging-sync.md`](staging-sync.md).

The automated pipeline was already in place via two workflows:
- `backup-prod-db.yml` — runs every Monday 03:00 UTC; dumps production DB to `~/public_html/tmp/prod_dump.sql` (moved to `~/backups/` in [#633]); automatically triggers the sync via repository dispatch
- `sync-prod-to-staging.yml` — imports dump, rsyncs uploads, runs URL search-replace (http + https), verifies `siteurl`/`home` point to staging

The runbook documents manual trigger options, what each step does, post-sync caveats (ACF options, Make.com staging isolation, wp-config safety), and troubleshooting for common failure modes.

---

## 2026-03-05 (Tier 3 — Phase 3 item 1 + Infrastructure + Features)

### Phase 3 item 1 — Delete orphaned root `class-wt-rest-posts-controller.php`
**Branch:** `chore/cc/phase-3-item-a`
**PR:** [#501](https://github.com/wikitongues/wikitongues.org/pull/501)

Deleted the orphaned copy of `class-wt-rest-posts-controller.php` from the root `includes/` directory. The theme copy at `wp-content/themes/blankslate-child/includes/class-wt-rest-posts-controller.php` is canonical. The root copy had diverged and was not being loaded by anything. Remaining cleanup (stripping the now-dead `create_item()`/`update_item()` overrides from the theme copy, removing `WT_REST_Posts_Controller` from CPT registrations, and deleting the empty root `includes/` directory) is tracked as Phase 3 item 3.

---

### Deploy workflow fixes
**Branch:** `chore/cc/deploy-workflow-fixes`, `chore/cc/mirror-staging-deploy-workflow`
**PRs:** [#503](https://github.com/wikitongues/wikitongues.org/pull/503), [#511](https://github.com/wikitongues/wikitongues.org/pull/511)

Four fixes applied to `deploy-production.yml`, then mirrored to `deploy-staging.yaml`:

- **`npm install` → `npm ci`** — strict lockfile install, consistent with `lint.yml`
- **Remove `StrictHostKeyChecking=no`** — `ssh-keyscan` already populates `known_hosts`; bypassing the check made that step pointless
- **Remove redundant `Clean up known hosts` step** — Actions runners are ephemeral; step was a no-op
- **Post-deploy health check** — `curl` the homepage after rsync; fails the job if non-200, surfacing broken deploys immediately
- **Slack notifications** — `:rocket:` on success, `:rotating_light:` on failure with a link to the run log

---

### Staging sync workflow — verification steps
**Branch:** `chore/cc/staging-sync-verification`
**PR:** [#509](https://github.com/wikitongues/wikitongues.org/pull/509)

Added a post-import verification step to `sync-prod-to-staging.yml` that runs `wp post list` counts for all four synced CPTs after the DB import and logs them to confirm parity with production. Runbook documentation (`docs/staging-sync.md`) is still outstanding.

---

### Archive stats section
**Branch:** `feature/cc/archive-stats-section`
**PR:** [#504](https://github.com/wikitongues/wikitongues.org/pull/504)

Added a three-stat strip to `/archive` between the search bar and the language index.

**Stats shown:**
- Languages with at least one material (video, lexicon, or resource)
- Total materials (videos + lexicons + resources)
- Nations represented (cross-referenced against published territory posts)

Each stat shows a count, a label, and a percentage subtitle relative to total records.

**Implementation:**
- `archive-languages__stats.php` — new module; queries computed via `$wpdb` direct queries for accuracy
- Results cached in a 6-hour transient (`wt_archive_stats`); invalidated on `save_post` for languages, videos, lexicons, resources, and territories
- `archive.styl` — new `.archive-stats` block styles; responsive stack on mobile
- `template-archive-home.php` — module included between search bar and language index

**Also bundled in this PR:**
- Fellows meta redesign (`meta--fellows-single.php`, `meta--fellows-single.styl`, `single-fellows.php`, `gallery-fellows.php`) — visual refresh of the fellows single page meta block
- Sticky nav fix — use placeholder `offsetTop` when nav is fixed to prevent layout jump
- ACF count meta fixes — switched stale transient-backed counts (`_language_video_count`, `_language_lexicon_count`, `_language_fellows_count`) to live `$wpdb` queries; fixed hook to fire on all post types; fixed lexicons field name

---

### Airtable record links + admin menu reorganization
**Branch:** `feature/cc/airtable-record-links`
**PRs:** [#505](https://github.com/wikitongues/wikitongues.org/pull/505), [#506](https://github.com/wikitongues/wikitongues.org/pull/506)

#### Airtable record links

Added a "View in Airtable" button to the sidebar of every Languages, Videos, Captions, and Lexicons post edit screen. The button only renders when the post has an `_airtable_record_id` value and the options page is configured.

**Configuration:** A new "Airtable Link" ACF options page (UI-defined, synced via `acf-json/`) stores:
- `airtable_table_configurations` (group)
  - `base_id`
  - Per-CPT groups (`languages`, `videos`, `captions`, `lexicons`), each with `table_id` and `view_id`

URL format: `airtable.com/{baseId}/{tableId}/{viewId}/{recordId}` (view segment omitted if not configured).

`class-acf-fields.php` was refactored to remove programmatic options page registration; the field group is now fully UI-defined and loaded from `acf-json/`.

**Files:**
- `wp-content/plugins/wt-airtable-sync/includes/class-acf-fields.php` — stripped to read-only record ID field + `render_record_link()` + `get_record_url()`
- `wp-content/themes/blankslate-child/acf-json/group_69a8bc1fac0cc.json` — new field group
- `wp-content/themes/blankslate-child/acf-json/ui_options_page_69a8bc0d92ef1.json` — new options page

#### Admin menu reorganization

Rewrote `admin-helpers.php` to reorganize the WordPress admin sidebar with semantic section headers and visual nesting.

**Changes:**
- Five non-navigable section headers registered via `add_menu_page()` with `__return_null`: **Archive**, **People**, **Publishing**, **Documents**, **Admin** (positions 91–95)
- `wt-menu-section` CSS class applied to section headers; styled as uppercase category labels (non-clickable via `pointer-events: none` + JS `href` removal)
- Full explicit menu order covering all CPTs
- Menu icons hidden; indentation added for visual nesting under section headers
- Duplicate CPT list-view submenu items and all "Add New" submenu entries removed

---

## 2026-03-01 (Tier 2 — Plugin: wt-airtable-sync)

### `wt-airtable-sync` plugin — Phases 0–3
**Branches:** `feature/cc/wt-airtable-sync-phase-0-1`, `feature/cc/wt-airtable-sync-phase-2`, `feature/cc/wt-airtable-sync-dry-run`
**PRs:** [#482](https://github.com/wikitongues/wikitongues.org/pull/482), [#486](https://github.com/wikitongues/wikitongues.org/pull/486), [#489](https://github.com/wikitongues/wikitongues.org/pull/489)
**Production cutover:** 2026-03-01
**Full documentation:** [`docs/airtable-sync.md`](airtable-sync.md)

Replaced the integromat-connector write paths with a standalone WordPress plugin that owns all field mapping, transformation, and ACF writes in code. Make.com is now a dumb HTTP transport: Airtable record change → POST raw Airtable payload to `/wp-json/wikitongues/v1/sync/{post_type}`.

#### Architecture

- **Plugin:** `wp-content/plugins/wt-airtable-sync/`
- **Auth:** `X-WT-Sync-Key` header matched against `WT_SYNC_API_KEY` constant in `wp-config.php`
- **Upsert key:** `_airtable_record_id` postmeta; languages fall back to `iso_code` then `post_title`
- **Field mapping:** `config/field-maps.php` — one entry per CPT; all ACF writes via `update_field()`
- **Dry-run mode:** `?dry_run=1` param processes all logic, writes nothing, returns full diff; requires `WT_SYNC_API_KEY` auth
- **Logging:** structured PHP error log entries per sync run

#### CPTs synced

| CPT | Status |
|---|---|
| `languages` | ✅ Live |
| `videos` | ✅ Live |
| `captions` | ✅ Live |
| `lexicons` | ✅ Live |
| `resources` | ⏸ Deferred — 907 WP posts vs 204 Airtable records; reconciliation required first |

#### Make.com blueprint architecture

Four production scenarios (one per CPT), each with a staging counterpart (separate `wp_base_url`, separate keychain):

- **`util:SetVariables`** at the top of every scenario — centralises `dry_run`, `wp_base_url`; `sync_key` in Make.com keychain
- **Languages / Captions / Lexicons:** linear — Airtable trigger → SetVariables → POST `/sync/{post_type}`
- **Videos:** `builtin:BasicRouter` — Route 1 (thumbnail URL present): download thumbnail → upload to WP media library → POST with `video_thumbnail_v2: <media_id>`; Route 2 (no thumbnail): POST with `video_thumbnail_v2: 0`
  - Media upload: `http:MakeRequest` POST to `/wp/v2/media`, Application Password basicAuth, `contentType: custom` for raw binary body
  - `Content-Disposition: attachment; filename=thumbnail_{{lower(Identifier)}}.{{last(split(type; "/"))}}` — MIME-derived extension, lowercased identifier for predictable WP slug
  - Always uploads fresh; no dedup search (dedup by slug was abandoned after observing that a changed thumbnail failed to update — slug matched old file)
- All four scenarios run on **15-minute schedule** in production
- **Slack notifications** on every run: `util:SetVariables` after the sync POST pre-computes `sync_status`, `sync_color`, `sync_env` as Make.com expressions (avoids quoting issues inside JSON Blocks); Block Kit header + fields section shows CPT, record count, environment, duration, and dry-run flag

#### Key decisions

- **No dedup search for media uploads.** The original design searched WP media library by slug before uploading; abandoned because (a) `rest_upload_sideload_error` — WordPress rejects uploads without a file extension in `Content-Disposition`; (b) even after fixing the extension, a changed Airtable thumbnail failed to update because the slug matched the old file. Decision: fresh upload on every run is acceptable; media library duplication is not the principal problem.
- **`contentType: custom` for media upload.** `contentType: json` converts the binary buffer to a string, corrupting image data. Raw binary body requires `contentType: custom`.
- **Staging and production as separate scenarios.** Simpler than a single scenario with environment switching; separate keychains prevent accidental cross-environment writes.

#### WP-CLI backfill — `_airtable_record_id` (production, 2026-03-01)

Stamped existing WP posts with their Airtable record IDs via CSV export + WP-CLI import script (`temp/importer/acf-importer.php`):

| CPT | Stamped | Not found | Notes |
|---|---|---|---|
| Languages | 8,088 | 2 | `wyim`, `wyug` — absent from WP |
| Videos | 1,853 | 3 | 2 are HTML-entity title encoding artifacts (`&#039;` vs `'`); 1 is a post-export new record — all resolve on next Airtable modification |
| Captions | 257 | 60 | Absent from WP — created automatically on next Airtable modification |
| Lexicons | 20 | 130 | Only 22 of 152 Airtable lexicon records have WP posts; remainder created on next modification |

#### Phase 3 — cleanup and cutover (2026-03-01)

- **`_WT_TMP_*` postmeta deleted:** 3,376 rows removed from `wp_postmeta`. Safety check confirmed all posts with `_WT_TMP_featured_languages` also had a resolved real `featured_languages` value before deletion.
- **Old Make.com v1 scenarios disabled:** integromat-connector write paths retired.
- **`post-object-helpers.php`** (`wp-content/themes/blankslate-child/includes/` and root `includes/`) is now dead code — `handle_post_object()` is no longer called by any active code path. Removal deferred to code quality cleanup (Tier 3).

---

## 2026-02-28 (Tier 2 — Territories archive)

### Territories archive
**Branch:** `feature/cc/territories-archive`
**PR:** [#491](https://github.com/wikitongues/wikitongues.org/pull/491)

Implemented the territories archive page and wired it into existing region pages as a gallery link-out.

**Changes:**
- **`archive-territories.php`:** Replaced the `<h1>Nations Archive</h1>` placeholder with a proper gallery-based archive following the same pattern as `archive-languages`, `archive-fellows`, and `archive-videos`. Supports optional `?region=<slug>` filter param — maps directly to the `region` taxonomy so no extra WP_Query is needed. Title adapts: "Territories" (unfiltered) or "Territories of {Region}" (filtered). Relies on WP's default `include_children => true` for hierarchical taxonomies, so continent-level slugs (e.g. `?region=asia`) include sub-region territories.
- **`taxonomy-region.php`:** Added `link_out` to the territories gallery params pointing to `get_post_type_archive_link('territories')` with `?region=<slug>`, matching the existing pattern used by the fellows gallery on the same template.
- **`gallery-territories.php`:** Refactored the territory card template to fix an OOM issue on large-region archive pages. The original loaded all languages per territory (`posts_per_page=-1`) and called `get_field('speakers_recorded', ...)` on every language inside a `usort()` callback — O(n log n) ACF calls per card. For Asia (55 territories, some with 400+ languages) this exhausted the 128 MB memory limit. Replaced with two targeted queries: a count query (`posts_per_page=1`, `fields=ids`, reads `found_posts`) and a preview query (`posts_per_page=4`). The usort and shuffle were both no-ops (line 33 overwrote the sorted result with the original array) and were removed. Also added `esc_html()` to previously unescaped output.
- **`archive.styl`:** Added `&-territories` to the archive selector group alongside `&-languages`, `&-videos`, `&-fellows`.
- **`phpstan-baseline.neon`:** Reduced `get_field not found` suppression count for `gallery-territories.php` from 4 to 1, reflecting the removed usort/foreach calls.

**Known issues logged in plan.md:**
- `gallery-territories.php`: double query (count + preview can be merged into one), `$language_name` empty fallback missing, raw `post_title` used for video lookup instead of `get_the_title()`
- `archive-territories.php`: `include_children` reliance is implicit — should be made explicit in query builder
- `taxonomy-region.php`: fellows OR LIKE meta query is still OOM-prone on continent pages (separate backlog item)

---

## 2026-02-28 (Tier 2 — Infrastructure decision)

### Evaluate Bedrock for composer-managed WordPress
**Type:** Strategic decision — no code changes

Decision: **No.**

**Blocking factors:**
- GreenGeeks shared hosting — cPanel's `public_html/` webroot can't be cleanly redirected to Bedrock's `web/` subdirectory without fragile symlink hacks; a clean adoption would require migrating to a VPS or managed host.
- 14 of 17 plugins are untracked third-party/premium installs (ACF Pro and others) — getting these into Composer requires Satispress or per-vendor repos with license keys, adding significant ongoing maintenance overhead for marginal gain.

**Separable benefit retained:** The `.env`-based config (removing hardcoded secrets from `wp-config.php`) can be done standalone by adding `vlucas/phpdotenv` as a production Composer dependency. Worth doing independently.

**Result:** Code quality cleanups (Tier 3) proceed in current form — duplication fix, root includes move, reorganize, autoloader all remain in scope as-is.

---

## 2026-02-22 (Tier 2 — Gallery features)

### Gallery `link_out` param — filtered archive pages
**Branch:** `feature/cc/gallery-link-out` (follow-on to PR [#462](https://github.com/wikitongues/wikitongues.org/pull/462))

Part 2 of the `link_out` feature: archive templates with query-string filter params and `link_out` wiring on territory/language/region pages.

Changes:
- **`archive-fellows.php`:** `?territory=<slug>` resolves via `get_page_by_path()` to a territory post; builds a meta query for `fellow_territory` to filter fellows by territory. `?region=<slug>` resolves via `get_term_by()` to a region term; expands to child terms for continent-level pages; aggregates fellows via OR LIKE meta query across territory IDs.
- **`archive-languages.php`:** `?territory=<slug>` filters languages via `selected_posts` from `get_field('languages', territory_id, false)`. `?genealogy=<slug>` filters via `taxonomy/term` on `linguistic-genealogy`. `?writing_system=<slug>` filters via `taxonomy/term` on `writing-system`.
- **`archive-videos.php`:** `?language=<slug>` resolves via `get_page_by_path()` to a language post; filters videos via `meta_key=featured_languages` / `meta_value=post_id`.
- **`single-territories.php`**, **`taxonomy-region.php`**, **`single-languages.php`**: `link_out` URLs passed to `create_gallery_instance()` on relevant gallery sections, constructing filtered archive URLs via `add_query_arg()` and `get_post_type_archive_link()`.

---

## 2026-02-22 (Tier 2 — Plugin hygiene / security)

### Audit Make.com scenarios
**Branch:** n/a (documentation only)
**Findings:** `docs/local_docs/make-audit-findings.md` (local only; not committed)

Conducted a full audit of all 14 Make.com scenarios by parsing exported JSON blueprints and cross-referencing against the WP codebase, live DB, and Airtable CSV exports.

**Scenario inventory:**
- 5 scenarios write to WordPress (daily scheduled Airtable poll → WP REST): `Import Languages`, `Import Captions`, `Import External Resources`, `Import Lexicons`, `Import Oral Histories`
- 3 scenarios are Airtable-read-only subscenarios (`Submodule-Resolve Creators/Languages/Videos`)
- 3 are non-WP operations (2× Dropbox folder/Paper doc creation, 1× GitHub Actions `repository_dispatch` for LOC Archival)
- 1 is empty (no routes), 1 is an inactive prototype

**Airtable → WP CPT mapping confirmed:**

| Airtable table | WP CPT |
|---|---|
| Languages | `languages` |
| Oral Histories | `videos` |
| Oral History Captions | `captions` |
| External Resources | `resources` |
| Lexicons | `lexicons` |

**Custom resolution architecture (`_WT_TMP_*` pattern):**
Make.com cannot send WP post IDs for ACF `post_object` fields (it only knows Airtable identifiers). The existing integration works around this with a two-step pattern: Make.com writes `_WT_TMP_{field_name}` with the Airtable title string; `class-wt-rest-posts-controller.php` intercepts the REST request and `handle_post_object()` (`post-object-helpers.php`) resolves each title to a WP post ID via `get_page_by_title()`, then writes the real ACF field via `update_field()`. The staging key is never deleted, leaving ~2,900 dead rows in `wp_postmeta`.

**Critical findings:**
- **No `_airtable_record_id`** on any post (0 rows) — every upsert is by `post_title` search; a title change in Airtable silently creates a duplicate WP post
- **`_WT_TMP_*` staging keys persist** — resolver runs but never calls `delete_post_meta`; accumulates dead rows on every sync
- **`get_page_by_title()` deprecated** since WP 6.2; currently still works
- **`writing_systems` and `linguistic_genealogy`** are still ACF text fields (not yet connected to their respective taxonomies); Make.com writes the text values; the taxonomies are separate
- **`resources` CPT mismatch**: 907 WP posts vs 204 in Airtable export view — reconciliation required before syncing resources with `wt-airtable-sync`
- **`video_thumbnail`** (legacy raw postmeta): confirmed dead in templates; `video_thumbnail_v2` (ACF image field) is the live field
- `post_type` Airtable field values exactly match registered WP CPT slugs — no slug mismatch

**Complete field map** (all 5 CPTs, all meta keys, ACF field types, transform strategy) documented in `docs/local_docs/make-audit-findings.md` § 9. This is the direct input to `config/field-maps.php` in `wt-airtable-sync`.

**Feeds into:** `wt-airtable-sync` plugin (next item in backlog).

---

## 2026-02-22 (Tier 2 — Infrastructure cleanup)

### Replace Font Awesome with inline SVGs
**Branch:** `feature/cc/replace-font-awesome`
**PR:** [#477](https://github.com/wikitongues/wikitongues.org/pull/477)

Removed the Font Awesome Kit CDN script and replaced all 12 icons with self-hosted inline SVGs. Simultaneously extracted the fourfold-duplicated `$social_links` array into a `wt_social_links()` helper.

Changes:
- **`modules/page--head.php`:** Removed FA Kit `<script>` tag — eliminates the external CDN dependency and one blocking network round-trip per page load
- **`includes/template-helpers.php`:** Added `wt_icon( string $name ): string` — returns a self-contained inline SVG for each of the 12 icons previously sourced from FA (`arrow-right-long`, `bars`, `envelope`, `instagram`, `link`, `linkedin`, `square-email`, `square-facebook`, `tiktok`, `x-twitter`, `xmark`, `youtube`). SVGs use `fill="currentColor"` and `width/height="1em"` to inherit text colour and font size from context. Added `wt_social_links(): array` — single source of truth for the 8-platform social link array; previously defined identically in four places across three files.
- **`header.php`:** `fa-bars` / `fa-xmark` → `wt_icon()`
- **`modules/banners/banner--alert.php`:** `fa-arrow-right-long` → `wt_icon()`
- **`modules/team/team-member--partner.php`:** `fa-link` / `fa-envelope` → `wt_icon()`
- **`modules/team/team-member--wide.php`, `team-member--grid.php`, `modules/fellows/meta--fellows-single.php`:** `<i class="...">` → `wt_icon( $data['icon'] )`
- **`single-fellows.php`, `template-about-board.php`, `template-about-staff.php`:** `$social_links = wt_social_links()` replaces four inline array definitions; email icon normalised to `square-email` across board/staff (was `envelope`, inconsistent with brand-style social icons elsewhere)
- **`phpstan-baseline.neon`:** Regenerated (423 errors) after `get_field()` calls moved from template files into `wt_social_links()`

---

## 2026-02-22 (Tier 2 — Data model improvements)

### Convert `writing_systems` to `writing-system` taxonomy
**Branch:** `feature/cc/writing-system-taxonomy`
**PR:** [#467](https://github.com/wikitongues/wikitongues.org/pull/467)

Converted the `writing_systems` ACF text field (comma-separated values, e.g. "Latin, Arabic") to a proper WP taxonomy `writing-system`. The previous exact-match meta query (`writing_systems = 'Latin, Arabic'`) could never correctly filter a language that uses multiple writing systems.

Changes:
- **`languages.php`:** Registered `writing-system` taxonomy (`publicly_queryable: false`, `show_in_rest: true`)
- **`acf-json/group_614a2f1facd00.json`:** Added `writing_system_taxonomy` ACF field (taxonomy type, multi-select, `save_terms/load_terms: 1`); renamed legacy text field to "Writing Systems (legacy)"; moved to bottom of field group
- **`archive-languages.php`:** `?writing_system=<slug>` resolved via `get_term_by('slug', ..., 'writing-system')`; gallery params switch to `taxonomy`/`term`; title: "Languages written in {term}" (edge case: "Unwritten languages" when term name is "Unwritten")
- **`meta--languages-single.php`:** Uses `get_the_terms(get_the_ID(), 'writing-system')`; terms rendered as comma-separated archive filter links
- **`meta--videos-single.php`:** Same taxonomy lookup inside the `featured_languages` loop
- **`queries.php`:** Removed `writing_systems` from exact-match `in_array` list
- **`search-filter.php`:** Removed `writing_systems` meta clause
- **`GalleryQueryArgsTest.php`:** Removed `test_writing_systems_uses_equals_compare`; test count: 58 → 57
- **`phpstan-baseline.neon`:** Adjusted occurrence counts after removing `get_field()` calls
- **`temp/migrate-writing-systems.php`** (gitignored): batch-paginated migration (50 posts/batch); splits comma-separated values; run with `wp eval-file ./temp/migrate-writing-systems.php --allow-root`

Notes: Migration must be run on each environment after deploy. ACF field group sync in WP Admin required (JSON `modified` timestamp bumped above DB value to trigger sync UI).

---

### Convert `linguistic_genealogy` to `linguistic-genealogy` taxonomy
**Branch:** `feature/cc/linguistic-genealogy-taxonomy`
**PR:** [#471](https://github.com/wikitongues/wikitongues.org/pull/471)

Same taxonomy migration pattern applied to `linguistic_genealogy`. Migration script adds a pre-pass to delete all existing `linguistic-genealogy` terms before re-migrating — required because genealogy values can also be comma-separated (e.g. "Indo-European, Slavic") and any prior run without splitting would have created merged terms.

Changes:
- **`languages.php`:** Registered `linguistic-genealogy` taxonomy (`publicly_queryable: false`, `show_in_rest: true`)
- **`acf-json/group_614a2f1facd00.json`:** Added `linguistic_genealogy_taxonomy` field (taxonomy type, single-select); renamed legacy field to "Linguistic Genealogy (legacy)"; moved to bottom
- **`archive-languages.php`:** `?genealogy=<slug>` resolved via `get_term_by('slug', ..., 'linguistic-genealogy')`; title: `{term->name} linguistic family`
- **`meta--languages-single.php`:** `get_the_terms(get_the_ID(), 'linguistic-genealogy')`; rendered as comma-separated `?genealogy=<slug>` archive links
- **`meta--videos-single.php`:** Same lookup inside `featured_languages` loop
- **`queries.php`:** Removed `linguistic_genealogy` from exact-match list; only `nations_of_origin` remains
- **`search-filter.php`:** Removed `linguistic_genealogy` meta clause
- **`GalleryQueryArgsTest.php`:** Removed `test_linguistic_genealogy_uses_equals_compare`; test count: 57 → 56
- **`phpstan-baseline.neon`:** Adjusted occurrence counts after removing `get_field()` calls
- **`temp/migrate-linguistic-genealogy.php`** (gitignored): Step 1 deletes all existing terms; Step 2 batch-migrates splitting on commas; run with `wp eval-file ./temp/migrate-linguistic-genealogy.php --allow-root`

Notes: Migration must be run on each environment after deploy. ACF field group sync required. `show_in_rest: true` means Make.com can write terms via the native WP REST API after the Make.com scenario audit — no custom endpoint needed.

---

### Single language page — multi-territory language gallery
**Branch:** `fix/cc/multi-territory-language-gallery`
**PR:** [#474](https://github.com/wikitongues/wikitongues.org/pull/474)

Fixed a regression in `single-languages.php` where the "Other languages from..." gallery only drew language IDs from the first territory (`$territories[0]`). For languages with multiple territories (e.g. English spanning US, UK, Australia), the gallery now collects and deduplicates language IDs from every associated territory.

Changes:
- **`single-languages.php`:** Replaced `$territories[0]`-only lookup with a loop over all territories, merging IDs and deduplicating with `array_unique()`. Gallery title built as an Oxford-comma list of territory names via `wt_prefix_the()` — one territory: "Other languages from the United States"; two: "Other languages from Dominica and Saint Kitts and Nevis"; three or more: "Other languages from Dominica, Saint Kitts and Nevis, and United Kingdom". `link_out` (see-all button) is set only for the single-territory case — a multi-territory gallery has no coherent single archive filter URL.

---

## 2026-02-21 (Tier 2 — Plugin hygiene + quick wins, partial)

### Audit `integromat-connector` REST API exposure
**Branch:** `chore/cc/delete-wt-form-and-plan-updates`
**PR:** [#463](https://github.com/wikitongues/wikitongues.org/pull/463)

Read all plugin source files and queried the DB for opted-in fields.

**Findings:**
- Make Connector by Celonis s.r.o. — third-party plugin; not tracked in git
- **No ACF fields or custom taxonomies are opted in** (`integromat_api_options_post` / `integromat_api_options_taxonomy` absent from DB)
- Implication: Make.com writes raw `wp_postmeta` keys directly, bypassing ACF hooks and validation

**Follow-on:** Audit Make.com scenarios (Tier 3) to inventory active workflows and determine which ACF fields need to be opted in for a production-quality integration.

---

### Delete wt-form plugin
**Branch:** `chore/cc/delete-wt-form-and-plan-updates`
**PR:** [#463](https://github.com/wikitongues/wikitongues.org/pull/463)

`wt-form` was a prototype download gate for the Revitalization Toolkit. Both its Airtable and Mailchimp integration methods began with `return;` and never ran. The `[wikitongues_form]` shortcode was confirmed absent from all published content. Plugin folder deleted.

Also corrected the plan entry for `integromat-connector`: it is the official Make Connector plugin by Make.com (Celonis s.r.o.), not custom code. Plan updated from "Track in VCS" to "Audit REST API exposure".

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
**PR:** [#459](https://github.com/wikitongues/wikitongues.org/pull/459)

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

Notes: WP_Mock 1.x is locked to PHPUnit ^9.6 due to Patchwork incompatibility with PHPUnit 10+. Upgrade path: push WP API calls to function edges, reducing mocking surface, then drop WP_Mock incrementally. See [testing-strategy.md](testing-strategy.md) for full constraint details.

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

**Prevention:** Layer 5 — Data Integrity (see [plan.md](../plan.md) → Data quality) will catch duplicate iso_codes and standard_names on a weekly schedule before they cause routing failures.

<!-- Pull request links used by the index and the newer entries above. -->
[#423]: https://github.com/wikitongues/wikitongues.org/pull/423
[#425]: https://github.com/wikitongues/wikitongues.org/pull/425
[#428]: https://github.com/wikitongues/wikitongues.org/pull/428
[#432]: https://github.com/wikitongues/wikitongues.org/pull/432
[#435]: https://github.com/wikitongues/wikitongues.org/pull/435
[#438]: https://github.com/wikitongues/wikitongues.org/pull/438
[#441]: https://github.com/wikitongues/wikitongues.org/pull/441
[#445]: https://github.com/wikitongues/wikitongues.org/pull/445
[#446]: https://github.com/wikitongues/wikitongues.org/pull/446
[#450]: https://github.com/wikitongues/wikitongues.org/pull/450
[#455]: https://github.com/wikitongues/wikitongues.org/pull/455
[#459]: https://github.com/wikitongues/wikitongues.org/pull/459
[#463]: https://github.com/wikitongues/wikitongues.org/pull/463
[#467]: https://github.com/wikitongues/wikitongues.org/pull/467
[#471]: https://github.com/wikitongues/wikitongues.org/pull/471
[#477]: https://github.com/wikitongues/wikitongues.org/pull/477
[#482]: https://github.com/wikitongues/wikitongues.org/pull/482
[#486]: https://github.com/wikitongues/wikitongues.org/pull/486
[#489]: https://github.com/wikitongues/wikitongues.org/pull/489
[#491]: https://github.com/wikitongues/wikitongues.org/pull/491
[#501]: https://github.com/wikitongues/wikitongues.org/pull/501
[#503]: https://github.com/wikitongues/wikitongues.org/pull/503
[#509]: https://github.com/wikitongues/wikitongues.org/pull/509
[#511]: https://github.com/wikitongues/wikitongues.org/pull/511
[#518]: https://github.com/wikitongues/wikitongues.org/pull/518
[#520]: https://github.com/wikitongues/wikitongues.org/pull/520
[#529]: https://github.com/wikitongues/wikitongues.org/pull/529
[#536]: https://github.com/wikitongues/wikitongues.org/pull/536
[#537]: https://github.com/wikitongues/wikitongues.org/pull/537
[#538]: https://github.com/wikitongues/wikitongues.org/pull/538
[#541]: https://github.com/wikitongues/wikitongues.org/pull/541
[#542]: https://github.com/wikitongues/wikitongues.org/pull/542
[#544]: https://github.com/wikitongues/wikitongues.org/pull/544
[#545]: https://github.com/wikitongues/wikitongues.org/pull/545
[#548]: https://github.com/wikitongues/wikitongues.org/pull/548
[#550]: https://github.com/wikitongues/wikitongues.org/pull/550
[#553]: https://github.com/wikitongues/wikitongues.org/pull/553
[#554]: https://github.com/wikitongues/wikitongues.org/pull/554
[#557]: https://github.com/wikitongues/wikitongues.org/pull/557
[#560]: https://github.com/wikitongues/wikitongues.org/pull/560
[#561]: https://github.com/wikitongues/wikitongues.org/pull/561
[#562]: https://github.com/wikitongues/wikitongues.org/pull/562
[#563]: https://github.com/wikitongues/wikitongues.org/pull/563
[#568]: https://github.com/wikitongues/wikitongues.org/pull/568
[#570]: https://github.com/wikitongues/wikitongues.org/pull/570
[#572]: https://github.com/wikitongues/wikitongues.org/pull/572
[#574]: https://github.com/wikitongues/wikitongues.org/pull/574
[#577]: https://github.com/wikitongues/wikitongues.org/pull/577
[#579]: https://github.com/wikitongues/wikitongues.org/pull/579
[#582]: https://github.com/wikitongues/wikitongues.org/pull/582
[#585]: https://github.com/wikitongues/wikitongues.org/pull/585
[#588]: https://github.com/wikitongues/wikitongues.org/pull/588
[#589]: https://github.com/wikitongues/wikitongues.org/pull/589
[#592]: https://github.com/wikitongues/wikitongues.org/pull/592
[#595]: https://github.com/wikitongues/wikitongues.org/pull/595
[#598]: https://github.com/wikitongues/wikitongues.org/pull/598
[#600]: https://github.com/wikitongues/wikitongues.org/pull/600
[#602]: https://github.com/wikitongues/wikitongues.org/pull/602
[#605]: https://github.com/wikitongues/wikitongues.org/pull/605
[#606]: https://github.com/wikitongues/wikitongues.org/pull/606
[#609]: https://github.com/wikitongues/wikitongues.org/pull/609
[#610]: https://github.com/wikitongues/wikitongues.org/pull/610
[#611]: https://github.com/wikitongues/wikitongues.org/pull/611
[#612]: https://github.com/wikitongues/wikitongues.org/pull/612
[#613]: https://github.com/wikitongues/wikitongues.org/pull/613
[#614]: https://github.com/wikitongues/wikitongues.org/pull/614
[#615]: https://github.com/wikitongues/wikitongues.org/pull/615
[#616]: https://github.com/wikitongues/wikitongues.org/pull/616
[#619]: https://github.com/wikitongues/wikitongues.org/pull/619
[#620]: https://github.com/wikitongues/wikitongues.org/pull/620
[#621]: https://github.com/wikitongues/wikitongues.org/pull/621
[#622]: https://github.com/wikitongues/wikitongues.org/pull/622
[#623]: https://github.com/wikitongues/wikitongues.org/pull/623
[#624]: https://github.com/wikitongues/wikitongues.org/pull/624
[#626]: https://github.com/wikitongues/wikitongues.org/pull/626
[#628]: https://github.com/wikitongues/wikitongues.org/pull/628
[#630]: https://github.com/wikitongues/wikitongues.org/pull/630
[#633]: https://github.com/wikitongues/wikitongues.org/pull/633
[#634]: https://github.com/wikitongues/wikitongues.org/pull/634
[#635]: https://github.com/wikitongues/wikitongues.org/pull/635
[#636]: https://github.com/wikitongues/wikitongues.org/pull/636
[#639]: https://github.com/wikitongues/wikitongues.org/pull/639
[#640]: https://github.com/wikitongues/wikitongues.org/pull/640
[#643]: https://github.com/wikitongues/wikitongues.org/pull/643
