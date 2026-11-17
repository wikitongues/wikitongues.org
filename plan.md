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
| 6 | Maps: a locator map on language pages, then interactive territory and region maps | Discovery by place | [3.2](#32-maps) |
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

- **Status (2026-11-06):** the new layout is in, built from editorial blocks like the homepage ([content-model.md → Donate page](docs/content-model.md#donate-page)). Still open: copy sign-off, story photo consent, social proof (1.3), the frequency test, and the design's Graphik Light (body text) and Schnyder Wide L (section labels) web fonts. Our Commercial Type licence covers neither, so the page uses Graphik Regular and Schnyder Wide M until they're licensed. The gift box's campaign ID is a block field for now, and should move to `wt_active_campaign()` once 1.1 lands.

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
- **Built locally, not yet on production.** Both pages exist only in the local database and travel as content bundles ([content-model.md](docs/content-model.md#content-bundles)). Their bundles are in `docs/local_docs/content/`.
  - **Language Rights** (`/language-rights`, plus nine FAQs). Built, with banner and block images; it depends on the banner caption style (#660) and Numbered Columns (#661) being deployed. Before it goes to production:
    - Upload `2026/11/language-rights-banner.jpg` and `2026/11/hilario-poot-cahun-class.jpg` there; the import matches images by path.
    - Decide whether this is the pre-launch page or the launch page: the banner says "First edition forthcoming", but parts of the body read as if the report is already out.
    - Identify the delegate in the banner photo, then name them in its alt text and caption.
    - Confirm Robby Thigpen's permission covers reusing his photo of Hilario Poot Cahun's class beyond the blog post.
    - Confirm the list of languages the team reads, quoted in "Help us get it right".
  - **Native Language AI** (`/revitalization/resources/native-language-ai`). Its seven FAQs are on production, but the page isn't. Its banner upload (`2026/10/IMG_1620.jpeg`) is local only, so upload it to production first.
  - Add both to GTM's `content_type` lookup when they go live.
- **Later in this workstream:**
  - Move the blog from Medium and Substack onto `/blog`, for search value.
  - Turn the translate-a-document invitation, now a `mailto:` link, into a form (see 4.2).

### 3. Discovery

#### 3.1 Dynamic homepage

*Now #5.*

The homepage is static. Surface recent oral histories, the active campaign (from 1.1), featured languages and fellows, with a donation CTA in the stream. Decide which signals to use, such as the publication date or an editor-curated featured flag, and build from galleries server-side where possible. A "most recent video" block is a quick first step.

- **Measure:** homepage exit rate, pages per session, and the rate of homepage entries that end in a donation.
- **Evaluate** 60–90 days after launch by comparing donation rates for visitors who use discovery features against those who don't. That comparison is the thesis test, and its result decides what follows this queue.

#### 3.2 Maps

*Now #6. Spec drafted, prototyped and decided 2026-11-16.*

Maps in two steps, both from one new plugin, `wt-maps`:

1. **A static locator map in the language page sidebar** that shows where the language is spoken. It doesn't need to be interactive. This step builds the plugin, the country data and the visual style.
2. **Interactive maps on territory and region pages** (`single-territories.php`, `taxonomy-region.php`), with shaded countries that link to language pages. They reuse the step 1 data and style.

##### Decided

| Question | Decision |
|---|---|
| Imagery or vector | **Vector** country shapes, colored by CSS, with a raster terrain layer cut to the coast. Satellite tiles are licensed, heavy, can't highlight a single country, and lose their detail at sidebar width. |
| Rendering | **Ahead of time, no JavaScript.** SVG files made by a build script and placed inline by PHP. No tile server, no API key, no request to a third party. |
| Where the code lives | **A plugin**, `wt-maps`, following the `wt-gallery` pattern: a PHP API plus a shortcode. **In this repo** (2026-11-17), with the generated maps committed beside it, so a territory change, its rebuilt maps and the `doctor` output land in one PR. |
| Style | **Deferred; a setting, not a build choice** (2026-11-17). The plugin ships every image layer the four candidate styles use (hillshade, sea floor, coloured relief) and sets the style as a class on the map, chosen in the plugin's settings, so switching between them needs no rebuild or deploy. **Names** (2026-11-17): two axes, light or dark (a parchment world or a brand-blue one) and full or focused (the whole frame, or the map falling away around the language's territories): **Light full** (was Shaded), **Light focused** (was Fog parchment), **Dark full** (was Brand), **Dark focused** (was Fog). The working names stay in the Look notes below and in the prototype's code. Kristen's preference (2026-11-17): Dark full, then Light full; she isn't a fan of the focused styles. **Launch default: Light focused** (2026-11-17). Relief, Fog relief, flat, outline and a gray inverted style were tried and dropped. |
| Dark mode | **Not supported** (2026-11-17): the site has no dark mode, so the maps have one look, on parchment. |
| Territory coverage | **A cheap, steady review process over a complete list at launch** (2026-11-17). Launch with the content changes already decided below; add the remaining ISO 3166-1 entries and map focuses later, one small reviewed change at a time (see "Territory review process"). |
| Labels and caption | **No labels, and no caption from the plugin.** Any text around the map is composed by the theme template (2026-11-17); the summary sentence above it already says where the language is spoken. The SVG carries the same text as its accessible description. |
| Projection | **Lambert azimuthal equal-area, centered on each map's territories.** Areas are exact, and shapes stretch only with distance from the center: typically 1–5% over the highlighted territory. An Albers equal-area conic was tested for continent-sized countries and rejected, because over each country's own land the azimuthal distorts less every time (Russia: 3.0% median and 12.6% worst, against 3.4% and 28.8%; Canada: 6.4% worst, against 36.6%). Large figures only appear in the empty corners of very wide frames. A Waterman butterfly suits a whole-world map, not a locator. |
| Disputed borders | **Neutral.** Disputed areas get a dashed edge and a hatch, and are assigned to no one. The hatch takes the highlight color when a claimant is one of the language's territories, so a Ukrainian page shows Crimea as contested. Committed 2026-11-17: the plugin draws only this view, with no switch. See "Contested areas are never hidden" under Look for how framing and the fog treat them. Natural Earth's default (control today) and its US view were compared in the prototype and dropped; its UN view is empty. |
| Link from territory to shape | **An ACF field on territories, "Map shape"**, holding Natural Earth codes (`GAZ,WEB` for Palestine), editable in WordPress. |
| France | **Metropolitan France only** (`FXX`), because its overseas departments are territories of their own. |
| What counts as a territory | **An ISO 3166-1 entry, plus the breakaway regions already listed.** That is why the French overseas departments, Greenland, Guam and the Northern Mariana Islands are territories, and Hawaiʻi, Alaska, Wales and Scotland are not: those have ISO 3166-2 codes, inside a country. |
| A language spoken in part of a territory | **A "Map focus" field on the language** (ACF, WordPress only), holding Natural Earth sub-unit codes (`USH` Hawaiʻi, `USK` Alaska, `WLS` Wales, `SCT` Scotland). The map draws that part in place of the whole territory; the territory link, the summary sentence and Airtable stay as they are. Empty means the whole territory. |
| Languages in several territories | **Fit the frame to all of them.** The prototype showed this works (Russian), so there are no separate region maps for languages. |

##### Content changes that ship with the plugin

These are one-off edits in WordPress admin and Airtable, made before the backfill. Each follows the territory-change checklist below.

- **Fold Nagorno-Karabakh.** Natural Earth still draws the pre-2023 area, but Azerbaijan has controlled it since September 2023 and the republic dissolved in January 2024. Remove it from Armenian, its only language. Armenian keeps Armenia, Lebanon and Abkhazia. Don't add Azerbaijan, because a territory link says the language is spoken there now, and the Karabakh Armenian community left in 2023. Then unpublish the territory.
- **Add Greenland** as a territory (Natural Earth unit `GRL`, ISO `GRL`) and move Greenlandic to it from Denmark. In Airtable, change Greenlandic's `nations_of_origin` from Denmark to Greenland.
- **Add Guam and the Northern Mariana Islands** as territories (Natural Earth `GUM`, `MNP`). Link Chamorro to both and Carolinian to the Northern Mariana Islands; set their Airtable `nations_of_origin` to "Guam, Northern Mariana Islands" and "Northern Mariana Islands". In local data (2026-11-17) Chamorro and Carolinian have no territory and no `nations_of_origin`, so they get no map and no summary sentence; confirm against production after the sync.
- **Set Hawaiian's map focus to `USH`.** Its territory stays the United States. Other candidates: Central Yupik and other Alaskan languages (`USK`), Welsh (`WLS`), Scottish Gaelic (`SCT`). Languages tied to an area below a sub-unit (Navajo Nation, the Basque Country) need Natural Earth's admin-1 layer, which the field can accept later.
- **Later, through the review process (not a launch blocker): the other ISO 3166-1 entries we lack**, added when they have languages in the archive. Inhabited ones: Puerto Rico, the US Virgin Islands, American Samoa, Curaçao and Aruba (Papiamento), Sint Maarten, Saint-Martin, Saint-Barthélemy, the Faroe Islands (Faroese), Åland, the Isle of Man (Manx), Jersey (Jèrriais), Guernsey (Guernésiais), Niue (Niuean), Norfolk Island (Norfuk), Saint Pierre and Miquelon, Bermuda, the Cayman Islands, the Turks and Caicos, the British Virgin Islands, Anguilla, Montserrat, Saint Helena and the Falklands. Each addition runs the checklist below.
- **Rename "Sahrawi Arab Democratic Republic" to "Western Sahara"** (2026-11-17, from Kristen's review): Western Sahara is the UN territory name, and the SADR is the government in exile. The map already shows all of Western Sahara. The slug changes to `western-sahara`, with a redirect from the old one. Make the same change to the matching `nations_of_origin` values in Airtable.
- **Add a territory type field** (2026-11-17, before launch) with four values: sovereign state, autonomous territory (Greenland), dependency (Guam), breakaway region (Abkhazia). Partially recognised states (Kosovo, Taiwan, Palestine) count as sovereign states, so the field adds no political label of its own. It is ACF on territories, set for all of them in the backfill, and shared with the language rights reports, which need the same split.
- **Rename "Cape Verde Islands" to "Cabo Verde",** and make the same change to the matching `nations_of_origin` value in Airtable (one language).
- **"Federated Staes of Micronesia" is fixed** to "Federated States of Micronesia" in production by hand (2026-11-17). Synced to staging and local the same day; WordPress keeps the old slug as a redirect. Its 18 languages' `nations_of_origin` say "Micronesia", which never matched the title, typo or not: fix them in Airtable with the others below.
- **Keep as it is:** "Naoero", Nauru's official English name since August 2026.

##### Territory-change checklist

A territory touches up to four places. Change all of them together, or the map, the summary sentence or the territory pages drift apart. This goes in `docs/maps.md`.

1. **The territory post** (WordPress → Territories). Rename the title; leave the slug alone unless the URL must change, and if it does, add a redirect from the old slug. Unpublish rather than delete, so the old URL can redirect.
2. **Language links.** Edit the language's Territories field, or the territory's Languages field. The relationship is two-way, so either side updates both.
3. **`nations_of_origin` in Airtable** for each affected language. It is synced free text, and both the summary sentence and the map's primary highlight match it against territory titles exactly. A rename without this change makes the summary say "Also spoken in" about the language's own country.
4. **Map shape** on the territory: the Natural Earth code. A territory without one shows no map, never a wrong one.

##### Territory review process

Coverage grows by small changes, not a big push, so the process has to stay cheap enough to run for a single territory and safe enough to run without a cartographer.

- **One request, one record.** A territory or map-focus request is a GitHub issue from a template: the place, the languages it affects, the Natural Earth code, and a source for the claim if it is contested. The issue is the queue and the audit trail.
- **One change, one PR.** The WordPress edits and the Airtable edits follow the checklist above. The regenerated maps and the `doctor` output then go in a single PR, because the build lives in this repo.
- **The review is reading two reports.** `wp wt-maps doctor` must be clean (no missing shapes, no unmatched `nations_of_origin`, no languages left without a territory by the change). The PR shows the contested-areas diff: every contested area a changed map now reveals, frames or hatches, against the last release. A change that touches no contested area needs one reviewer; one that does needs a second (the site lead or Kristen, whoever didn't make the change), and a source in the issue.
- **Batch the slow parts.** Data syncs and production writes happen at most once a month, for whatever has been merged since. The claims table gets its yearly review then too, owned by Kristen, and after any news that moves a border.
- **Backlog to start from:** the ISO gaps listed above; the 508 published languages with no territory at all (local data, 2026-11-17), which show no map and no "spoken in" sentence; and map focuses for Alaskan languages, Welsh and Scottish Gaelic.

The plugin adds a check that turns this list into a report: `wp wt-maps doctor` lists territories without a map shape, map shapes that don't exist in the data, and `nations_of_origin` values that match no territory title. Run it after any territory change.

##### Data

- **Boundaries:** Natural Earth v5.1.2, which has no license restrictions: 1:50m for most maps, and 1:10m outlines for zoomed-in frames (under 6° radius) and for framing, because 1:50m drops most small islands (it has one of Tuvalu's nine atolls). Keep the source files and version in the plugin, so a rebuild gives the same result. Three layers: whole countries from `admin_0_countries`; the French overseas departments, Hong Kong, Macao, Kosovo, Taiwan, Somaliland, Northern Cyprus, Western Sahara, Palestine and Greenland from `admin_0_map_units`; the breakaway regions (Abkhazia, South Ossetia, Transnistria) and every disputed overlay from `admin_0_breakaway_disputed_areas`.
- **Matching.** `wikidata_id` is empty on all territories, on production too. Matching titles against Natural Earth's name fields finds 189 of 215 by name, and the other 26 come from a hand-written table; the prototype matched all 215. A WP-CLI command backfills "Map shape" from that table, and in the same pass fills `iso_code` and `wikidata_id` from Natural Earth where they exist. Five territories (Abkhazia, South Ossetia, Transnistria, Northern Cyprus, Somaliland) have no ISO code, and Kosovo uses the user-assigned XKX. The backfill is a bulk write, so follow the database-sync steps in CLAUDE.md first.
- **Too small to see.** Shapes under 1.5px are dropped at sidebar scale. A territory whose shape is still too small after zooming gets rings instead: one per island group (groups closer than 12 units merge), so Tuvalu shows as a chain of eight rings rather than one ring in empty sea.

##### Which countries to highlight

On local data, 7,536 language pages list exactly one territory, 44 list more than one, and none list zero.

- **Primary:** territories whose titles are also in `nations_of_origin`. This is the same split the language summary makes between "spoken in" and "also spoken in" (`wt_blurb_also_spoken_sentence()`). If no territory is also a country of origin, every territory is primary.
- **Secondary:** the other territories, highlighted more lightly.
- **Context:** the neighboring countries.
- Don't add a "primary territory" field. If `nations_of_origin` moves to territory relationships (6.4), the rule reads from that field instead.

##### Building the maps

- **A Node build script** in `wt-maps/build/` (`d3-geo`), with one shared projection module that the SVG renderer and both raster steps use, so they can't drift apart. Per territory it writes:
  - an SVG centered on the territory, framed on its main body (far-flung islands don't widen the frame). The frame grows from the largest island: a part joins if it is big (15% or more of it), near the main body, or, for parts of at least 1% of it, near any part already kept. So an island chain (Hawaiʻi, from the Big Island to Niʻihau) stays whole while specks far out (the Northwestern Hawaiian Islands) don't widen it. It is never zoomed tighter than about 45 km radius (0.4°), so a small country fills enough of the frame to read as a shape (Malta and Gozo take about a quarter of the width; at this zoom the neighbours drop out, and the scale bar says how small it is). When no single island reads as a shape (Tuvalu), the frame takes in all of them;
  - a hillshade WebP for the same frame (see "Look").
- **Simplify to sidebar resolution.** Douglas–Peucker at 0.6px, and drop rings under 1.5px. This takes Russia from 516 KB to 19 KB gzipped; the prototype median is 3 KB.
- **Languages in several territories** get a frame fitted to all of them. PHP can't refit a vector frame at request time, so the build also writes a frame for each distinct combination of territories in use (44 languages today), keyed by the sorted territory IDs.
- **Commit the build output.** It's small, changes rarely, and committing it means deploys don't need a new build step. Rerun the script by hand when the data or the style changes.
- **Each path carries a class and a data attribute** (`data-a3`, `data-territory`). PHP adds the primary and secondary classes when it renders, so one file serves every language in that territory.
- **The antimeridian** (Fiji, Kiribati, Chukotka) is handled by centering; the prototype checked Fiji.

##### PHP API

- **`wt_map( array $args ): string`** returns the `<figure>`. Its arguments:
  - `territories`: territory post IDs; the default is the current language's.
  - `primary`: territory IDs to highlight as primary; the default follows the rule above.
  - `class`: an extra class on the wrapper.
- A shortcode, `[wt_map]`, with the same arguments, for editorial pages.
- Markup: `<figure class="wt-map">`, containing the `<svg role="img">` with a `<title>` and a `<desc>` that names the territories.
- Read the SVG with `file_get_contents`, and cache the rendered result in the object cache, keyed by the territory IDs, the primary IDs and the plugin version. Add the highlight classes with the HTML API (`WP_HTML_Tag_Processor`), not regular expressions.
- **Where it goes:** `modules/languages/meta--languages-single.php`, after the summary (`.blurb`) and before the endonyms. The sidebar's mobile accordion leaves the map open.

##### Look

- **Brand (Dark full):** a deep blue sea, the brand hue at lower chroma (`oklch(0.33 0.11 285)`; the full `$blue()` `#3814a5` was too loud across most of the frame, against OS and Imhof's rule that saturated colour belongs in small areas), land in the site's `$blue(50)`, the language's primary territories in parchment with a deep-blue edge, and secondary territories in a pale blue between the two. Borders and the disputed-area hatch are blues too.
- **Terrain shading** on top, at a resolution matched to each frame. The land hillshade is computed from AWS Terrain Tiles (open elevation data, terrarium encoding; credit its sources per the Tilezen attribution list). The build picks the tile zoom from the frame's ground distance per pixel: zoom 3 for Russia at 13 km a pixel, zoom 10 for Malta at 195 m. It samples the tiles through the inverted d3 projection and shades from the north-west at 45°. Vertical exaggeration grows with pixel size (×2 for Malta, ×18 for Russia). Flat ground maps to neutral gray, and the result is cut to the vector coastline and blended with `mix-blend-mode: hard-light` (soft-light was tried and disappears on light fills). Natural Earth's SR_50M, used first, is fixed at about 3.7 km a pixel and went soft on small frames. The images ship at 600×450, quality 30: every test map is under 30 KB. Tiles are cached by the build, never fetched by the site.
- **Sea floor stays on Natural Earth** (GRAY_50M_SR_OB). The tiles' bathymetry has steps of several hundred metres between sources (near the antimeridian: Fiji, Tuvalu), and hillshading turns them into straight seams. The sea is background, so its softer resolution suits it.
- **Ocean floor, kept 2026-11-16.** Natural Earth's GRAY_50M_SR_OB carries seafloor relief: continental shelves, trenches, ridges and fracture zones. The build maps deep ocean to neutral gray, cuts the image to the sea, and blends it over the sea blue with `hard-light` at 65% opacity, fainter than the land shading so the coast stays the strongest edge. It adds 5–29 KB per page at 2×, less at the shipping size.
- **Contested areas** get dark dashes (`#24107a`, 1.4 units wide in the 400-unit frame, 3.6/2.2 dashes) on a parchment casing 2.6 wide, plus dark hatch lines. Not red: OS advises against red for sensitive subjects such as disputed land, and red on the land blue was 1.47:1, below the 3:1 minimum for graphics. The casing keeps the edge at 3:1 or better on sea, land and parchment alike. The hatch background still says how the area relates to the language: parchment when it involves one of the language's territories, the land blue when not. A territory that is itself a contested area (Abkhazia, Somaliland) is drawn solid, with the dashed edge.
- **Small territories** get a yellow (`$yellow`, `#ffff00`) ring when a shape is under about 40 px² at 400×300, with a thin dark halo so it holds on the land blue as well as the sea.
- **Secondary territories** are told apart by treatment, not tint: a pale fill with a parchment outline. Tints alone gave 1.48:1 between primary and secondary.
- **Contrast floor:** every mark that carries meaning reaches 3:1 against what it sits on (WCAG non-text contrast), and none relies on colour alone.
- **No compass.** A north arrow was tried and removed: readers assume north is up, and on these maps it is along the central meridian, so the arrow repeats that on most maps and leans on the wide ones (Russia about 30°), where it reads as a mistake. The interactive maps in step 2 can use MapLibre's north indicator, which turns with the map.
- **Scale bar, added 2026-11-17.** An abstract bar, no ticks: one round length (1, 2 or 5 × a power of ten km) at most 80 units long, labelled in km, bottom left. Measured at the centre of the map; an equal-area projection keeps area, not distance, so it is a guide to size, not a ruler (Russia's edges are off by up to 12%). It sits outside the fog mask, so it stays put in every style. Decorative: `aria-hidden`, since the description already names the place. Hidden where shapes stretch more than 5% anywhere in the frame (Russia, China, Greenland): OS leaves scale bars off continent-scale maps, because the scale isn't the same across them.
- **Contested areas are never hidden, cut or cropped.** Leaving one out reads as a political statement (Crimea fogged on a Ukrainian page says it isn't Ukraine's concern), so three rules apply in every style:
  1. **A contested area that one of the language's territories claims** (any of Natural Earth's point-of-view fields names it) is treated like the territory: always inside the frame, and fully revealed by the fog. Ukrainian: Crimea, Donetsk, Luhansk. Moroccan: Western Sahara. Kashmiri: all of Kashmir and the India–China border areas. Hebrew: the Golan Heights. Yue: Taiwan and the India–China border areas. Russian: Crimea and the Kurils.
  2. **The fog never cuts through a contested area.** If the reveal reaches one (within about 30 units of a territory or a claimed area), all of it is revealed, in its neutral hatch: Transnistria on a Ukrainian page, the West Bank and Gaza on a Hebrew page.
  3. **The rule is symmetric.** It follows claims, not control, so a Russian page shows Crimea hatched as contested exactly as a Ukrainian page does. Nothing is drawn as belonging to either side.
  - **The claims data dates from Natural Earth 5.1.2.** It lacks Russia's 2022 claims to Donetsk, Luhansk, Zaporizhzhia and Kherson, and the end of Nagorno-Karabakh. The plugin should keep a reviewed claims table in the repo that overrides Natural Earth, check it at least once a year and whenever a territory changes, and have `wp wt-maps doctor` list every contested area each map reveals so a person can read it before a release.
- **Locator globe, added 2026-11-17.** OS's guidance is that a locator map shows the area within its wider region. For readers who don't know where a mid-sized country is, any frame with a corner radius under 30° gets an orthographic globe about 65px across at a 300px sidebar (radius 44 in the 400-unit frame; enlarged from about 50px on 2026-11-17 after Kristen found it hard to see). That covers Malta, Fiji, Tuvalu, Hawaiʻi, Guam, Georgia, Israel, Ukraine, Somalia, Morocco, Peru and Greenland; it leaves out India (35°), China and Russia, which are their own context. The globe is centred on the map. Small frames get a ring on the spot; larger ones get the map's own view outlined, as OS advises for marking an inset's area. It takes whichever of the top-right, top-left and bottom-right corners the language's territories and claims crowd least (the scale bar has bottom-left), sits outside the fog mask, and is `aria-hidden`. Drawn from Natural Earth 1:110m land with no borders: 2–4 KB raw. **One treatment in every style** (chosen 2026-11-17 from five variants compared at sidebar width): the brand globe, with the muted blue sea and `$blue(50)` land on a parchment halo. Small places get a filled yellow dot with a thin dark edge, the least noisy mark at about 50px. Larger frames get their view outlined in yellow on a dark underline. In the light styles the globe flips its shades: a pale sea (`#d6cdee`) and brand-blue land, with a thin deep-blue rim. That keeps the classic dark-land figure and sits in the light page's key, while the colours and marks stay shared. Rejected: a grey globe with a blue mark, which was dark on dark for inland places, and a cased ring, which was too noisy on the Hebrew map.
- **Shaded (Light full), revised 2026-11-17.** The language's territories show the coloured relief (the same per-map pattern as Fog parchment) in place of a blue tint, with a brand-blue edge, dashed for secondary. Effectively Fog parchment with a frame and no fog.
- **Fog (Dark focused), under trial 2026-11-17.** The Brand map, revealed only around the territory like fog of war in a strategy game. Each SVG carries a mask built from its own highlighted shapes and rings. A shared filter grows them (dilate, blur, a turbulence-roughened edge), with a dimmer outer halo. Past that, the map dissolves into the page: no frame, no rectangle. A 9% edge fade stops a halo from ending in a straight cut where a territory sits near the frame. It costs nothing in bytes (the mask reuses the highlight paths with `<use>`), but it needs one shared `<filter id="fog">` per page, and the edge fade sits inside the SVG mask so the scale bar is not faded. Not yet chosen over Brand.
- **Fog relief, tried and hidden 2026-11-17.** The Fog composition with Natural Earth's coloured relief (HYP_50M_SR) and every other decision kept: the muted sea and ocean floor, neutral contested edges, rings and the scale bar. The language's territories show the relief at full colour through an SVG pattern (one `<pattern>` per map, so the fog mask still sees an opaque shape), with a parchment edge for primary and a dashed parchment edge for secondary. Context land gets a 62% land-blue wash so the territory leads, and the detailed hillshade sits on top at 75%.
- **Fog parchment (Light focused), under trial 2026-11-17.** Fog relief on a light world, between Shaded and Fog relief: context land is parchment with the hillshade, the sea a neutral grey (`#e5e5e5`, the same luminance as the lavender `#e8e3ec` it replaced) with faint ocean-floor shading, and the fog dissolves both into the page. The territory keeps its coloured relief, with a dark brand-blue edge (dashed for secondary). Yellow rings are about 1:1 on parchment, so rings are brand blue (`#3814a5`) with a parchment halo; contested edges keep their dark dashes on a parchment casing.
- **Tokens** in a `wt-maps` Stylus partial built from the site palette, so the colors live in one place.
- **Size:** full sidebar width (about 230–390px), with a fixed 4:3 aspect ratio so the layout doesn't shift as the page loads.

##### Step 2: interactive maps

- **MapLibre GL** with vector tiles that we host ourselves: a Protomaps PMTiles file served from the plugin or from uploads. No API key in the browser and no third-party tile server. The style reuses the Brand colors and the hillshade.
- **Territory pages:** the territory highlighted, with the languages listed beside the map. Languages don't have coordinates, so link from shaded countries, not language markers, until there's a source for language locations (Glottolog has some, keyed by `glottocode`).
- **Region pages:** each territory shaded by how many languages it has, linking to its territory page.
- **A world view**, if one is wanted (homepage, region index): compare a Waterman butterfly with Equal Earth in the Brand style before choosing.
- **Load only when visible.** Load MapLibre only when the map scrolls into view, and show the static step 1 SVG until then, and as the fallback when JavaScript is off.
- **Performance:** some territories list hundreds of languages (India: 403). Read raw IDs, as territory pages already do.
- **Instrument:** `map_interaction` (`territory`, `action`). Step 1 is static and has nothing to track.

##### Prototype

A private claude.ai page (2026-11-16, updated through 2026-11-17) shows ten test languages and six disputed-border languages at true sidebar width in each style, and the full territory mapping. Its build scripts (matching, rendering, simplification, raster reprojection and masking) are in [`docs/maps-prototype/`](docs/maps-prototype/README.md), with the full territory mapping, and are the starting point for `wt-maps/build/`.

##### Release checklist

Tracked live on a private claude.ai checklist page (updated 2026-11-17); this list is the record in the repo.

**Decide**
- [x] Style: deferred, shipped as a plugin setting with four styles (Light full, Light focused, Dark full, Dark focused) (2026-11-17).
- [x] Coverage: launch with the decided content changes; the rest goes through the territory review process (2026-11-17).
- [x] Dark mode: not supported (2026-11-17).
- [x] Contested areas: the neutral view only; claimed areas are framed and revealed, never fogged, cut or cropped (2026-11-17).
- [x] Captions: composed by the theme template; the plugin renders none (2026-11-17).
- [x] Locator globe: on frames under 30° corner radius; a yellow dot or the outlined view; shades flipped in the light styles (2026-11-17).
- [x] Packaging: `wt-maps` in this repo, with the generated maps committed beside it (2026-11-17).
- [x] Launch style: Light focused, as the setting's default; it can change at any time (2026-11-17).
- [x] Territory types: four values (sovereign state, autonomous territory, dependency, breakaway region) (2026-11-17).
- [x] Review: a contested-area change needs a second reviewer, the site lead or Kristen; Kristen owns the yearly claims review (2026-11-17).

**Data** (scripted writes, so sync first)
- [x] Sync: Backup Prod DB (check the workflow is still enabled) → Sync to Staging → `tool-sync-db-from-prod.sh` (2026-11-17).
- [x] Confirm on production data that Chamorro and Carolinian have no territory and no `nations_of_origin` (2026-11-17).
- [ ] The WordPress edits below are scripted in `docs/maps-prototype/territory-edits.php` (dry run by default, `execute` to apply, safe to rerun). **Done on local and staging 2026-11-17** and checked: the reverse links, the new territory pages, the language summaries and the redirect from the old Western Sahara slug. The script is on the server in `~/scripts/`, outside the web root. Next: production, dry run first, after keeping a dated copy of the dump. Found on the way: `wt_prefix_the()` knows only five names, so summaries read "Spoken in Northern Mariana Islands" (and "United States", "Philippines"). The new territories' official languages: Greenland Greenlandic; Guam English and Chamorro; Northern Mariana Islands English, Chamorro and Carolinian. Greenland sits in the Northern America region (UN M49).
- [ ] Fold Nagorno-Karabakh: remove it from Armenian, then unpublish the territory.
- [ ] Add Greenland and move Greenlandic to it from Denmark.
- [ ] Add Guam and the Northern Mariana Islands; link Chamorro to both and Carolinian to the Northern Mariana Islands.
- [ ] Rename "Sahrawi Arab Democratic Republic" to "Western Sahara", slug `western-sahara`, with a redirect from the old slug.
- [ ] Rename "Cape Verde Islands" to "Cabo Verde".
- [ ] The matching `nations_of_origin` edits in Airtable (Greenlandic, Cabo Verde, Western Sahara, Chamorro, Carolinian), and Micronesia: 18 languages (Chuukese, Kosraean, Pohnpeian, Yapese and others) have `nations_of_origin` "Micronesia", which matches no territory title, so their summaries say "Also spoken in" about their own country. Change them to "Federated States of Micronesia".
- [ ] The ACF fields: "Map shape" and "Territory type" on territories, "Map focus" on languages (JSON, with `modified` bumped).
- [ ] Set every territory's type in the backfill, and check the list with Kristen (it feeds the language rights reports too).
- [ ] Set Hawaiian's map focus to `USH`.

**Build**
- [ ] The `wt-maps` plugin: the PHP API, the shortcode, the backfill and `doctor` commands, and the prototype scripts moved into it.
- [ ] Whitelist it in `.gitignore` (`!/wp-content/plugins/wt-maps/`). Commit the generated SVGs and images outside `build/`, which `.gitignore` excludes for every plugin, because deploys ship only what is committed.
- [ ] Count the maps before generating them all: one per distinct set of territories (233 today), so check the total size against the repo and the deploy.
- [ ] Map focus support: the 1:50m and 1:10m sub-unit layers in the build, and the focus applied when picking shapes.
- [ ] The reviewed claims table that overrides Natural Earth's 2022-era data.
- [ ] All three image layers (hillshade, sea floor, coloured relief) and the locator globe built for every map, so any style works (check the total against the size estimate).
- [ ] The map tokens as a Stylus partial built from the site palette, with the four style classes.
- [ ] The sidebar placement in `meta--languages-single.php`, with the template composing any caption.
- [ ] A plugin setting that turns the sidebar map off without a deploy, and one that picks the style.
- [ ] Credits on the site's credits page: Natural Earth, and the Terrain Tiles sources.
- [ ] The review tooling: the territory-request issue template, and the contested-areas diff the build writes for each PR.
- [ ] Unit tests for the PHP API, and `/test` (lint, PHPStan, PHPUnit) green.

**Review before staging**
- [ ] A person reads the `doctor` report of contested areas per map.
- [ ] Spot-check about 20 languages at sidebar width in all four styles: the test set here, plus the five with the most territories.
- [ ] Each map's page weight stays within the budget (SVG ≤ 20 KB gzipped, images ≤ 30 KB).

**Staging**
- [ ] Merge the PR to `main`, then `/deploy staging`.
- [ ] Activate the plugin on staging (deploys don't activate plugins), then run the backfill (dry run, then `--execute`) and `doctor`.
- [ ] Check language pages on staging, including a contested one, a small island and a language with no territory.

**Production**
- [ ] Before writing anything, keep a dated copy of the production dump in `~/backups/`.
- [ ] `/deploy production`, activate the plugin, run the backfill (dry run, then `--execute`) and `doctor`.
- [ ] Spot-check the same pages, and hard-refresh, since the CSS has no version string.

**After**
- [ ] Move this item to `docs/plan-archive.md`, and write `docs/maps.md`: how maps are built and how to change a territory.
- [ ] Put the yearly claims review on Kristen's calendar.

##### Done when

- The content changes above are made, and `wp wt-maps doctor` reports nothing.
- Every territory has a map shape, and `iso_code` and `wikidata_id` where they exist.
- Every published language page with a territory shows a map.
- `docs/maps.md` describes the plugin, the decisions above and the territory-change checklist.
- There are unit tests for choosing the primary territories, choosing the frame, and assembling the markup.
- `composer lint`, `composer analyse` and `composer test` pass.

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
- **Resource card ability dedupes on URL.** `wikitongues/add-resource-card` refuses any URL already on the Resources page. That one check does two jobs: making retries safe, and an unplanned "one card per URL" content rule. It wrongly refuses genuine cards that share a URL, and lets the same resource through under a variant URL. When a second write ability lands, give all write abilities a caller-supplied request ID for retries, and add content rules only where someone asks for them.

#### 7.7 Content styles: one prose module

*In progress.* `prose.styl` and `.wt_prose` exist, and three surfaces use them: editorial text rows, the fellow "About" block and blog posts. The rules and decisions are in [content-styles.md](docs/content-styles.md). Still to migrate, one at a time and each checked against before-and-after screenshots of the pages that use it:

- **`page.php`** (`.entry-content`) and **FAQ items** in galleries, which want a `--compact` variant.
- **Careers**, **Archive success** and the **giving campaign**.
- **Retire the text row `image` / `image_caption` fields.** Images now go in the WYSIWYG; Valbina's profile is the only user, and her photo moved into her text locally on 2026-10-15; make the same edit on staging and production first. Remove them from the ACF JSON *and* trash them in the admin (deleting the JSON alone doesn't), then drop the field branch in `text-layout.php`.
- **Strip `.main-content` to layout** last. It is still both a layout box and a tag stylesheet (`ul li { float: left }`); the Revitalization application page's list is the last thing that depends on its float rules.

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
