# Wikitongues – Website Plan

The live plan for wikitongues.org: what comes next, in order, and the spec for each piece of work. Finished work moves to [docs/plan-archive.md](docs/plan-archive.md); how the finished systems work is documented in `docs/`.

**Docs:** [content model](docs/content-model.md) · [download gateway](docs/download-gateway.md) · [Airtable sync](docs/airtable-sync.md) · [galleries](docs/gallery.md) · [deployment](docs/deployment.md) · [staging sync](docs/staging-sync.md) · [testing](docs/testing-strategy.md) · [completed work](docs/plan-archive.md)

## Contents

- [Why this work](#why-this-work)
- [How this plan works](#how-this-plan-works)
- [Queue](#queue)
- [Workstreams](#workstreams)
  - [1. Fundraising & donors](#1-fundraising--donors)
  - [2. Pages & storytelling](#2-pages--storytelling)
  - [3. Discovery](#3-discovery)
  - [4. Email capture & engagement](#4-email-capture--engagement)
  - [5. Content model](#5-content-model)
  - [6. Data quality & Airtable](#6-data-quality--airtable)
  - [7. Engineering foundations](#7-engineering-foundations)
  - [8. Membership (blocked on the board)](#8-membership-blocked-on-the-board)
- [GitHub issues](#github-issues)
- [Ideas](#ideas)
- [Decided against](#decided-against)

---

## Why this work

Wikitongues documents the world's languages. The website is where that work meets the public: an archive of 8,000+ language profiles, 1,800+ oral histories, territories and fellows, and the place every donation starts.

The product roadmap's working thesis is that **engagement drives donations**. People who explore the archive, finding a language, watching an oral history or downloading a resource, become more likely to give, and to give monthly.

- **Metrics:**
  - North star: monthly recurring donor conversion.
  - Near-term proof: one-time gift conversion.
  - Leading indicator: email capture rate.
- **Order of work:** first make giving work and visible, with FundraiseUp configuration, the giving page, donor recognition, and pages that tell the story. Then build the surfaces that test the thesis: homepage, maps, search. Then let the data choose what's next.
- **Instrument on day one.** A feature isn't done until its GA4 events fire in DebugView.
- **Sized for a small team.** Ship small and iterate, with minimal copy where copy is the bottleneck. Engineering foundations never block features.

**Outside this repo, but it limits what this work can return:** the Mailchimp setup and the post-download nurture emails (roadmap steps 1–5) haven't started. The download gateway has been collecting emails since March 2026, and no email sequence follows up on them yet.

## How this plan works

- **Queue.** *Now* items are committed and run in order; *Next* items wait behind them. Anything in the workstreams that isn't queued is unscheduled.
- **Specs** live in the workstream sections, and queue entries link to them.
- **The product roadmap**, kept outside the repo, holds organization-level strategy: Mailchimp, editorial, the contributor program, and board decisions. This file owns the order of website work.
- **When something ships:** remove it here, add a log entry and index row to [plan-archive.md](docs/plan-archive.md), and make sure the system it built is described in `docs/`.
- **Security specifics**, such as open findings and credentials, stay out of this public file and are tracked privately.

Last reviewed 2026-09-13.

---

## Queue

### Now

| # | Item | Why now | Spec |
|---|---|---|---|
| 1 | FundraiseUp configuration in ACF, and a campaign banner | The year-end campaign has to launch and change without deploys; it needs to be on production by mid-November | [1.1](#11-fundraiseup-configuration-and-campaign-banner) |
| 2 | Pages and navigation, with minimal copy | Tell the story, and give contributors a way in (`/contribute`) | [2.1](#21-pages-and-navigation) |
| 3 | Giving page redesign | The page donors land on; bias it toward monthly | [1.2](#12-giving-page-redesign) |
| 4 | Donor recognition | Social proof for the giving page, built on People | [1.3](#13-donor-recognition) |
| 5 | Dynamic homepage | The first real test of the engagement thesis | [3.1](#31-dynamic-homepage) |
| 6 | Maps on territory and region pages | Discovery by place | [3.2](#32-maps-on-territory-and-region-pages) |
| 7 | Enhanced search | Find languages by name, then faceted results | [3.3](#33-enhanced-search) |

The order follows the product roadmap, except that the small FundraiseUp piece moves first because of the year-end campaign. Search's first step (#379) is small and can ship early.

### Next

| Item | Spec |
|---|---|
| Download gateway follow-ups: the anonymization webhook (9b), retention gaps, the mobile email step, consent and cookies, smaller fixes | [4.1](#41-download-gateway-follow-ups) |
| Keep writing-system and genealogy terms in sync from Airtable | [6.1](#61-writing-system-and-genealogy-terms-from-airtable) |
| Operations: server cron, vulnerability monitoring, a third-party plugin audit | [7.1](#71-operations) |
| Native forms to replace the Airtable iframes, plus "report a problem" | [4.2](#42-native-forms) |
| Content cleanups after the People and Form 990 changes | [5.1](#51-content-cleanups) |

### Blocked on a decision

| Item | Waiting on |
|---|---|
| Membership, and the language passport and gamification built on it | The board's decision on what membership means ([8](#8-membership-blocked-on-the-board)) |

---

## Workstreams

### 1. Fundraising & donors

#### 1.1 FundraiseUp configuration and campaign banner

*Now #1. On production by mid-November.*

The FundraiseUp organization ID is hardcoded in `modules/page--head.php`, and a campaign change needs a deploy. Move all FundraiseUp configuration into an ACF options page ("Fundraising"), and add a campaign banner that editors control.

1. **Options page and fields.**
   - *Default campaign:* `fundraiseup_org_id`, `default_element_id`, `default_campaign_id`.
   - *Active campaign:* `active_campaign_status` (disabled, active or scheduled), `active_campaign_label` (admin only), `active_campaign_id`, `active_campaign_element_id`, `active_campaign_start`, `active_campaign_end`, and an `active_campaign_banner` group.
2. **`wt_active_campaign()`** in `template-helpers.php`. It returns the active campaign when its status is `active`, or when it is `scheduled` and today falls within its dates, and the default campaign otherwise. Every template reads IDs through it, so none are hardcoded.
3. **Campaign banner.** The `active_campaign_banner` group has `show_banner`, `heading`, `body`, `cta_label`, `variant` (standard or urgent) and `display_scope` (all, home, archive or singles). A new `modules/banners/banner--campaign.php` renders it. `header.php` places it before the existing alert banner (the `banner_alert_*` options), which stays for general announcements.

- **Before starting:** find every template and editorial link that opens a FundraiseUp element, so that none stays hardcoded.
- **Instrument:** `campaign_cta_click` (`campaign_slug`, `cta_type`). Keep `donate_cta_click` with `cta_location` on every donate placement.
- **Enables:** the year-end campaign, point-in-time drives and banner copy changes, all without a deploy, plus the frequency-default test in 1.2.

#### 1.2 Giving page redesign

*Now #3.*

`/donate` runs `template-donate.php`, which has live stats, the donation links and CTA tracking. The redesign should make the page do the persuading:

- **Content:** recurring vs. one-time giving, biased toward monthly; impact framing; social proof from donor recognition (1.3); the FundraiseUp modal.
- **Copy:** ship with minimal copy and iterate (decided 2026-09-13). The impact statement needs sign-off before it is final.
- **Frequency-default test** (analytics strategy, Q5): a second FundraiseUp element that defaults to monthly, placed on content pages, compared against the one-time default. It needs 1.1, so that element IDs are configurable, and the `fundraise_up_element_id` dimension registered in GA4.
- **Measure:** giving-page conversion and recurring share, before and after, in 30-day windows of engaged sessions.

#### 1.3 Donor recognition

*Now #4. Replaces the separate Donors post type in the roadmap (decided 2026-09-13).*

Donors are People: add a **Donor** term to `people-type`, so a board member who donates stays one record ([content-model.md → People](docs/content-model.md#people)).

- **Anonymity:** a per-person flag that keeps a donor off every public list while still counting them ("Join N supporters"). A donor who is also on the board still appears on the Board page.
- **Surfaces:**
  - a Donors section: a `template-people.php` gallery row filtered by type, with anonymous donors excluded
  - a donor count helper
  - a social-proof block for the giving page, the homepage and language pages
- **Content:** donor quotes, photos and permissions; start collecting them now. How donor records get in, by hand or imported from the donation platforms, is still open.
- **Privacy:** People stays locked down (no REST, no search, no archive). Anonymous donors are counted, never rendered.
- **Instrument:** `donor_story_view` (`surface`).
- **Follow-on:** donor cards in galleries on campaign pages and the homepage.

### 2. Pages & storytelling

#### 2.1 Pages and navigation

*Now #2, with minimal copy (decided 2026-09-13).*

From the roadmap (step 6):

- **Header navigation.** Proposed structure: Fellowship · Language Archive · Revitalization Resources · Projects · Contribute · Donate · Blog.
- **New pages:**
  - `/contribute`: the program, the ways to contribute, and CTAs.
  - `/contribute/record`: how to record an oral history. The *Recording an Oral History* document already exists.
  - `/contribute/faq`.
  - `/contribute/stories`, once there are contributor stories to tell.
  - `/resources/captioning` waits on a review of captioning's role in the organization.
- **CTA copy pass:** first person and action-oriented, with donation and newsletter CTAs moved higher.
- **Mostly editorial.** Pages compose from the existing editorial layouts; code changes are limited to the header and menus, and any new layout the pages turn out to need.
- **Instrument:** add the new pages to GTM's `content_type` lookup so they report as their own content type.
- **Later in this workstream:**
  - Move the blog from Medium and Substack onto `/blog`, for search value.
  - Turn the translate-a-document invitation, now a `mailto:` link, into a form (see 4.2).

### 3. Discovery

#### 3.1 Dynamic homepage

*Now #5.*

The homepage is static. Surface recent oral histories, the active campaign (from 1.1), featured languages and fellows, with a donation CTA in the stream. Decide which signals to use, such as the publication date or an editor-curated featured flag, and build from galleries server-side where possible. A "most recent video" block is a quick first step.

- **Measure:** homepage exit rate, pages per session, and the rate of homepage entries that end in a donation.
- **Evaluate** 60–90 days after launch by comparing donation rates for visitors who use discovery features against those who don't. That comparison is the thesis test, and its result decides what follows this queue.

#### 3.2 Maps on territory and region pages

*Now #6.*

An embedded map on `single-territories.php` and `taxonomy-region.php`, with markers that link to language pages.

- **Library:** Leaflet with OpenStreetMap tiles, or Mapbox GL. No unrestricted API key in the browser.
- **Data:** territories have a `wikidata_id` but no coordinates or shapes yet. Sourcing them, from Wikidata coordinates or a country-boundaries dataset keyed by ISO code, is part of this work.
- **Performance:** some territories list hundreds of languages (India: 403).
- **Instrument:** `map_interaction` (`territory`, `action`).

#### 3.3 Enhanced search

*Now #7.*

1. **Find languages by name (#379).** Full-page `?s=` results miss language pages, because a language's title is its ISO code, and WordPress search matches titles and content, not `standard_name` or `alternate_names`. The typeahead already matches names. Make the main search query match them too (`includes/template/search-filter.php`). In the same pass, fix the missing video thumbnails in results (#58) and the results page title that shows an ISO code.
2. **A gallery-powered results page** across languages, territories, genealogies, writing systems, videos and fellows. It should be faceted (region, family, content type) and relevance-weighted. Design it together with in-gallery filtering (#378).

- **Instrument:** `search_performed` (`query`, `results_count`) and `search_result_click` (`result_position`, `result_type`). The dimensions are already registered.

### 4. Email capture & engagement

#### 4.1 Download gateway follow-ups

*Next.* Each item is detailed in [download-gateway.md → Known gaps](docs/download-gateway.md#known-gaps-and-follow-ups).

- **Anonymization webhook (9b).** Retention anonymizes names and emails in WordPress but tells no other system. Select the affected IDs before the bulk update, queue a `type: anonymize` webhook for each person, and add a Make branch that clears the Airtable People record (and, later, the Mailchimp contact).
- **Retention gaps.** Delivered `person` webhook payloads (name, email) and the free-text organization answer outlive retention; purge them too.
- **Mobile email step.** In the first month, 60% of desktop visitors who saw the modal submitted it, against 7.7% on mobile (a small sample). Review the modal on phones: layout, keyboard behavior, number of fields.
- **Consent and cookies.** The "Receive updates" box is ticked by default, and the visitor cookie is set without a consent step. Decide both before any email marketing starts.
- **Smaller fixes:**
  - Skippable shows no skip button when there's no follow-up form.
  - Disabled doesn't hide the Wikimedia Commons link.
  - External downloads aren't logged.
  - *Skip and download* sends no GA4 `redirect` event.
  - No environment guard on webhooks, so local copies deliver to production Make.
  - Retry times mix time zones.
  - Expired tokens are never purged.
  - Unused schema.
  - Uninstall leaves settings behind.
- **Airtable cleanup.** Dedupe the Download records duplicated by the 2026-09-08 replay, on `download_event_id` ([airtable-sync.md](docs/airtable-sync.md#gateway-webhook-router)).
- **For the Mailchimp work (roadmap step 3):** add the language slug to the `intake` webhook payload, so Make can set the `LANG_SLUG` merge field.
- **Security hardening** from the March 2026 review is tracked privately.

#### 4.2 Native forms

*Next (roadmap step 12).*

Replace the Airtable iframe embeds (Submit a video, Submit a document) with native forms: custom REST endpoints and PHP-defined markup, no forms plugin, and data forwarded to Airtable through Make using the gateway's webhook pattern.

- **Report a problem:** a new form for content errors, such as a broken language page or a wrong ISO code.
- **Translate a document:** later, replace the invitation's `mailto:` link with a form.
- **Instrument:** a submit event for each form.

#### 4.3 Visitor engagement profile

*Later. Useful once nurture emails exist to consume it.*

A log of what each email-known visitor engages with, for personalizing retention emails and, eventually, a member's view of their own history.

- **Identity progression:** anonymous (GA4 only) → email-known (gateway or newsletter) → contributor → member. Membership is blocked (8).
- **Build:**
  - an engagement table keyed to `wp_gateway_people` (`visitor_id`, `content_type`, `content_slug`, `event_type`, timestamp)
  - a write hook for page views where the `gateway_vid` cookie maps to a known person
  - a read API that returns what an email hash has engaged with
- **Not now:** a user-facing passport, stamps or accounts.

### 5. Content model

Reference: [content-model.md](docs/content-model.md).

#### 5.1 Content cleanups

*Next. Small admin and data tasks.*

- **People.**
  - Sweep the retired list fields (`board_members`, `staff_members`, `interns_and_volunteers`, `team_banner_*`) from the Board, Advisors and Staff pages, once production is confirmed good.
  - Publish or retire the draft Partners page.
  - Decide whether the Board section should list co-founders first (*Selection order*) instead of alphabetically.
- **Form 990s.**
  - Delete the retired `reports` posts, their attachments, and the database copy of their ACF field group (`group_634b277f68bd7`).
  - Delete the stale "Reports" menu item.
- **Oral-history PDF links.** Three links in admin content still point at the PDF directly: the Language Revitalization page and two FAQs. Point them at its document page, so downloads go through the gateway (#619).

#### 5.2 Video state UI

*Later.* Video pages handle Processing and Private with plain text.

- **Audio-only:** map Airtable's `Type` field (`MovingImage` / `Sound`) to an explicit `media_type`, and show an audio placeholder from that. No detection is needed — the archive already records this. Today the gallery infers it from missing `metadata_width`/`metadata_height`, which is wrong in both directions: 38 videos render as audio, and 6 of 31 audio files render as video, because audio records store the string `N/A` for their dimensions rather than leaving them blank, and `N/A` is not empty. 110 records have a blank `Type`, so decide the fallback (backfill in Airtable, or treat unknown as video) before mapping.
- **Processing:** design the state (#61), with a "notify me when ready" affordance.
- **Private:** a "request access" affordance.
- **Removed:** a tier for fraud or abuse takedowns, separate from creator-private, with its own notice (#4).
- **Thumbnails:** one consistent treatment across all states.

#### 5.3 Video collections

*Later.* Editorial groupings across the archive: by person, by project (the Jewish Languages Project) or by expedition.

- **Model:** a `collections` post type with `description`, `featured_image`, an ordered `videos` relationship and a `collection_type`.
- **Pages:** a `/collections/` archive and a page per collection; optionally, a "Part of" link on video pages.
- **Source:** curated in WordPress; no Airtable sync needed.

#### 5.4 Creators: choose the model first

*Later (decided 2026-09-13).* Creators exist in Airtable and are linked from videos and captions. Before building anything, choose between:

- **A public `creators` post type synced from Airtable.** This is the earlier spec: profile pages at `/creators/{name}/`, relationships to languages and videos, and a nullable `user_id` for future membership.
- **A Creator type on People.** People is private, to protect donors, so this means reworking its lockdown into per-type visibility.

Make the same decision with the contributor program's needs in view (contributor profiles and stories).

#### 5.5 Shared banner definition

*Later. Verified feasible.* The editorial `banner_layout` banner and `revitalization_fellows_banner` are two definitions of the same thing, kept in step by hand.

An ACF Clone field collapses them: a location-less "Global: Banner" group that clones with `display: group` and `prefix_name: 1`, and reuses the editorial banner's field keys. Group-mode clones keep the original keys, so stored content keeps resolving. The editorial side stays byte-identical, and only `revitalization_fellows_banner`'s sub-field keys change. Own PR.

#### 5.6 People modelling

*Later.* `leadership_title` holds the role at Wikitongues for staff and board, but the outside affiliation for advisors, and there's only one per person. Someone who is both an advisor and a former board member therefore shows the same text in both places. Split role from affiliation.

### 6. Data quality & Airtable

Reference: [airtable-sync.md](docs/airtable-sync.md) and [content-model.md → Known data issues](docs/content-model.md#known-data-issues).

#### 6.1 Writing-system and genealogy terms from Airtable

*Next. Found 2026-09-13.*

The sync writes the legacy text fields `writing_systems` and `linguistic_genealogy`. Templates and the `/languages/` filters read the `writing-system` and `linguistic-genealogy` taxonomies, which only the February 2026 migration populated. Edits made since then never reached the terms, and languages created since have none.

- **Fix:** in `wt-airtable-sync`, map both payloads to terms: split on commas, create missing terms, call `wp_set_object_terms()`, and keep the text fields.
- **Catch up:** refresh from production and re-run the migrations for every language (local → staging → production).
- **Guard:** add the check to the data integrity command (6.2).

#### 6.2 Data integrity checks

*Later. Low effort.* A weekly `wp wt integrity check`, specified in [testing-strategy.md → Layer 5](docs/testing-strategy.md#layer-5--data-integrity-planned). It reports, and never blocks deploys. It checks for:

- duplicate ISO codes and names
- blank ISO codes, and slugs that don't match the ISO code
- missing standard names (#53)
- taxonomy terms out of step with their text fields
- Airtable records with no WordPress post
- **mapped field values that differ between Airtable and WordPress.** Compare values, not
  timestamps. Airtable's `last_modified` fires on automation and formula churn — 843 and 602
  video records were bumped on two days in April 2025 — so a timestamp comparison reports ~78%
  of the archive as diverged while the real figure is 0.4%. A presence check does not catch
  this either: the records exist, their values are stale (found 2026-10-04, see 6.3)

#### 6.3 Airtable reconciliation

*Later.*

1. **Incomplete WordPress records.** 520+ languages arrived without some fields. Fix it at the source: make the fields required in Airtable, and handle gaps before sync.
2. **Airtable records missing from WordPress.** As of March 2026: 2 languages, about 3 videos, 60 captions and 130 lexicons. Each is created when its record is next edited.

   **Bulk-touching does not reliably close the gap — it can open one.** Measured 2026-10-04: an
   Airtable edit stamped ~40 video records within two minutes; the sync wrote 29 and silently
   dropped 11, which were never retried. The `TriggerWatchRecords` cursor advances past a
   timestamp once a run hits its per-run record cap, so records sharing that timestamp fall
   behind it permanently. `plan.md` has catalogued "max records = 1" as an open blueprint issue;
   that cap is the likely cause. Raising it reduces the odds but does not remove them, because
   any batch larger than the cap straddles the cursor the same way. The value-divergence check
   in 6.2 is what actually detects this.

3. **Stale values on records that do exist.** Measured 2026-10-04 against a full Airtable
   export (1,862 video records): `public_status` differs on 8, every one of them `Public` in
   Airtable and `Processing` in WordPress — released oral histories that the site still gates
   behind "we're still processing this video", suppressing the embed and the downloads.
   `youtube_id` differs on 0; `Width` on 36. All 8 carry the same bulk-edit timestamp, so this
   is the same mechanism as above, not a separate fault. Re-touching those records individually
   clears them.

4. **`N/A` written into a number field.** Audio records carry `Width`/`Height` of `N/A`, which
   the sync passes through into ACF *number* fields. Normalise at the sync boundary, or stop
   depending on dimensions once `media_type` exists (5.2).
5. **Airtable table bloat.** The Videos table has 188 fields, mostly computed or lookups. Resolve linked records in Make subscenarios, as Captions already does, then delete the computed columns. Don't add more lookup fields. This is not only tidiness: computed columns are what drive the `last_modified` churn in 6.2, and that churn is what consumes the per-run record cap in 6.3.2.

Two related gaps:

- **Resources:** about 907 WordPress posts against 204 Airtable records. They must be reconciled before resources can sync.
- **Deletions don't propagate.** Agree a soft-delete convention (set the status to trash in Airtable first), or add a delete endpoint.

#### 6.4 `nations_of_origin` migration

*Later, after 6.3.* This comma-separated text field is matched with `LIKE`, so combined values hide languages (#241: South Korea). Territory relationships already exist as the structured alternative. Change the field, update the sync, and backfill.

#### 6.5 Language names and caption IDs

*Later.*

- Languages without a standard name fall back to the ISO code (#53).
- Comma-form names ("Gondi, Southern") read badly (#54). Choose between reordering them for display and fixing the data.
- Caption file IDs join languages with `,` instead of `+` (#72).

#### 6.6 Airtable change notifications in Slack

*Later.*

- **Plugin.** Add a `changed` diff to `Sync_Controller::sync()` responses. Read each mapped field's current value before writing, compare it after resolving the incoming value, and return only what changed: `{"field": {"old": …, "new": …}}`. On creation, return every written value with `"old": null`. Leave `video_thumbnail_v2` out.
- **Make.** After the sync request, post to Slack only when the record was created or `changed` is non-empty, for example `[Videos] Updated "Polynesian" — public_status: draft → publish`. Resolve relationship IDs to titles with a "Resolve WP Post" subscenario (`GET /wp/v2/{post_type}/{id}?_fields=id,title`, reusing the media-upload credentials). Each environment posts to its own channel.

#### 6.7 Canonical language registry

*Long-term.* A specification for a sourced language registry lives in `docs/local_docs/` (not committed): Glottolog, Wikidata and ISO 639-3 identifiers, with per-field provenance and licensing. It would inform 6.3–6.5 and replace hand-maintained language data. No website work is scheduled.

### 7. Engineering foundations

None of this blocks feature work (decided 2026-09-13).

#### 7.1 Operations

*Next.*

- **Server cron.** WP-Cron only runs on page views, and the gateway's webhook delivery and retention jobs depend on it. Add a cPanel cron on production and staging that runs `wp cron event run --due-now` every 5 minutes.
- **Vulnerability monitoring.** WPScan in CI was dropped when its API stopped being free. Install Patchstack or Wordfence on production instead.
- **Plugin audit.** Confirm which third-party plugins production still needs, and uninstall the rest.

#### 7.2 PHPStan baseline

*Ongoing.* On 2026-09-13 the baseline held 473 suppressed errors across 192 entries. That's up from 424, because the gateway and sync plugins joined PHPStan's scope; most are `get_field()` calls in templates. Fix a file's entries when you touch the file, then regenerate the baseline. There is no zero deadline.

#### 7.3 Tests

*Later.* [testing-strategy.md](docs/testing-strategy.md) specifies each layer.

- **Integration tests (Layer 3):** PHPUnit with `WP_UnitTestCase` against MySQL in CI, for REST endpoints, gallery queries, search and post type registration.
- **End-to-end and visual regression (Layer 4):** Playwright, with screenshot baselines for key templates. The baseline captures whatever exists when it's built.
- **Docker** for a reproducible local environment, if contributor onboarding needs one.

#### 7.4 Front-end build

*Later.* Stylus is largely unmaintained, and `npm audit` flags it (dev-only).

- **Option A, Dart Sass (recommended):** near one-to-one syntax. Rename the `.styl` files to `.scss`, adjust the imports, and replace the `$blue(tint)` function with `color.mix()`.
- **Option B, PostCSS and Vite:** CSS custom properties, plus a bundle for the theme's JavaScript. A larger change; A can come first.
- **Related:** version the compiled `main.css` so browsers don't keep stale styles, and move jQuery code (gallery pagination included) to plain JavaScript.

#### 7.5 Performance

*Later.* There is no production visibility into load times or queries. Known risks:

- territory pages with hundreds of languages (India 403, China 249, Brazil 200, USA 197)
- continent pages, whose fellows query has one `LIKE` clause per territory (#533; Asia has 215)
- relationship fields that hydrate full post objects

Set baselines for the language, territory, region and search pages, and monitor them with Query Monitor on staging or a scheduled synthetic check. Already done: territory pages read raw language IDs.

#### 7.6 Smaller items

*Later.*

- Move secrets from `wp-config.php` into a `.env` file (`vlucas/phpdotenv`), the separable part of the Bedrock evaluation.
- An accessibility (ADA) evaluation.
- A deploy health check that covers more than the homepage.
- Internationalization (long-term).

### 8. Membership (blocked on the board)

What membership means for Wikitongues (its scope, benefits, feel and impact) is a board decision that sits above website work. Nothing here starts until the board makes it. The contributor program's identity progression (email-known → contributor → member → fellow) and the visitor engagement profile (4.3) prepare the ground.

- **Language passport:** a member's view of their own engagement: languages explored, territories visited, videos watched, downloads.
- **Gamification:** stamps for core actions, and an onboarding flow. Write a separate spec first.

---

## GitHub issues

Open issues, and where each is tracked (2026-09-13):

| Issue | Tracked in |
|---|---|
| [#379](https://github.com/wikitongues/wikitongues.org/issues/379) Searching "russian" finds nothing | 3.3 (Now #7) |
| [#58](https://github.com/wikitongues/wikitongues.org/issues/58) Video thumbnails missing in search | 3.3 (Now #7) |
| [#378](https://github.com/wikitongues/wikitongues.org/issues/378) Gallery: dynamic querying | 3.3, designed with the results page |
| [#377](https://github.com/wikitongues/wikitongues.org/issues/377) Gallery: post-type fallback | [gallery.md → Known gaps](docs/gallery.md#known-gaps) |
| [#61](https://github.com/wikitongues/wikitongues.org/issues/61) "Processing" video page undesigned | 5.2 |
| [#4](https://github.com/wikitongues/wikitongues.org/issues/4) "Removed" videos | 5.2 |
| [#53](https://github.com/wikitongues/wikitongues.org/issues/53) Languages without standard names | 6.2, 6.5 |
| [#54](https://github.com/wikitongues/wikitongues.org/issues/54) Comma-form language names | 6.5 |
| [#72](https://github.com/wikitongues/wikitongues.org/issues/72) Caption file ID separators | 6.5 |
| [#241](https://github.com/wikitongues/wikitongues.org/issues/241) South Korea's languages missing | 6.4 |
| [#533](https://github.com/wikitongues/wikitongues.org/issues/533) Fellows query on continent pages | 7.5 |

## Ideas

Unprioritized. An idea moves into a workstream when data or capacity says so. Collected from the product roadmap's backlog and the readme's old to-do list.

- **Discovery:**
  - country landing pages
  - a "Guess the Language" game
  - galleries in place of carousels
  - archive filters, and an interactive taxonomy on language pages
  - a fellowship taxonomy archive
  - search results grouped by type
- **Storytelling:**
  - an About page refresh
  - an "Our impact" page with cohort stories
  - numbers at a glance
  - a fellowship information page
  - press and speaking
  - more testimonials
  - earmarked giving
- **Language pages:**
  - external resource links (Wikimedia, OLAC, Ethnologue, Omniglot), and clearer presentation of them
  - continent of origin
  - audio-only entries
  - a caption submission CTA (the PCF/Amara partnership)
  - links between fellows and their languages
- **Video pages:**
  - transcripts and translations
  - video authors
  - licensing and ethics notes
  - metadata toggles for multi-language videos and on mobile
  - embeds for videos that aren't on YouTube
- **Site-wide:**
  - a mobile style pass
  - 404 styling
  - an alert banner shown only to visitors who haven't been by in a week
  - an "About" dropdown in the header
  - browser notification opt-in
  - expiry for career posts
- **Fellows and toolkit:**
  - micro-blogging on fellow pages
  - newsletter, language and donate prompts in the Revitalization Toolkit

## Decided against

- **Bedrock** (2026-02-28): the host's web root can't be moved cleanly, and most plugins can't be managed through Composer.
- **A donation ask inside the download modal** (2026-03-25): the post-download email carries the ask.
- **A reporting screen for the download gateway** (2026-04-12): Airtable views and a database client cover it.
- **Gating Form 990s** (2026-09-11): public-disclosure documents stay one click away.
- **A separate Donors post type** (2026-09-13): donors are a People type.
- **A Google Ads monthly grant** (roadmap).
