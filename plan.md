# Wikitongues – Website Plan

The live plan for wikitongues.org: what comes next, in order, and the spec for each piece of work. Finished work moves to [docs/plan-archive.md](docs/plan-archive.md); how the finished systems work is documented in `docs/`.

**Docs:** [content model](docs/content-model.md) · [language rights](docs/language-rights.md) · [download gateway](docs/download-gateway.md) · [Airtable sync](docs/airtable-sync.md) · [galleries](docs/gallery.md) · [deployment](docs/deployment.md) · [staging sync](docs/staging-sync.md) · [testing](docs/testing-strategy.md) · [completed work](docs/plan-archive.md)

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

Last reviewed 2026-11-18.

---

## Queue

### Now

| # | Item | Why now | Spec |
|---|---|---|---|
| 1 | FundraiseUp configuration in ACF, and a campaign banner | The year-end campaign has to launch and change without deploys; it needs to be on production by mid-November | [1.1](#11-fundraiseup-configuration-and-campaign-banner) |
| 2 | Language Rights report: hub, region and country pages, released from Airtable and Google Docs | Dated: announcement 10 December, first regional release 19 February | [2.2](#22-language-rights-report) |
| 3 | Pages and navigation, with minimal copy | Tell the story, and give contributors a way in (`/contribute`) | [2.1](#21-pages-and-navigation) |
| 4 | Giving page redesign | The page donors land on; bias it toward monthly | [1.2](#12-giving-page-redesign) |
| 5 | Donor recognition | Social proof for the giving page, built on People | [1.3](#13-donor-recognition) |
| 6 | Dynamic homepage | The first real test of the engagement thesis | [3.1](#31-dynamic-homepage) |
| 7 | Maps: a locator map on language pages, then interactive territory and region maps | Discovery by place. Its one data dependency is the subdivision sync slice of 6.8 | [3.2](#32-maps) |
| 8 | Enhanced search | Find languages by name, then faceted results | [3.3](#33-enhanced-search) |

The order follows the product roadmap, except that the small FundraiseUp piece moves first because of the year-end campaign. Search's first step (#379) is small and can ship early.

### Next

| Item | Spec |
|---|---|
| Download gateway follow-ups: the anonymization webhook (9b), retention gaps, the mobile email step, consent and cookies, smaller fixes | [4.1](#41-download-gateway-follow-ups) |
| Keep writing-system and genealogy terms in sync from Airtable | [6.1](#61-writing-system-and-genealogy-terms-from-airtable) |
| Operations: server cron, vulnerability monitoring, a third-party plugin audit | [7.1](#71-operations) |
| Native forms to replace the Airtable iframes, plus "report a problem" | [4.2](#42-native-forms) |
| Content cleanups after the People and Form 990 changes | [5.1](#51-content-cleanups) |
| Language geography cleanup across Airtable, Make and WordPress: origins, subdivisions, "also spoken in" | [6.8](#68-language-geography-across-airtable-make-and-wordpress) |
| GTM: give Native Language AI (live since 2026-11-18) its own `content_type`, and Language Rights when it launches | [2.1](#21-pages-and-navigation) |

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

*Now #4.*

`/donate` runs `template-donate.php`, which has live stats, the donation links and CTA tracking. The redesign should make the page do the persuading:

- **Status (2026-11-06):** the new layout is in, built from editorial blocks like the homepage ([content-model.md → Donate page](docs/content-model.md#donate-page)). Still open: copy sign-off, story photo consent, social proof (1.3), the frequency test, and the design's Graphik Light (body text) and Schnyder Wide L (section labels) web fonts. Our Commercial Type licence covers neither, so the page uses Graphik Regular and Schnyder Wide M until they're licensed. The gift box's campaign ID is a block field for now, and should move to `wt_active_campaign()` once 1.1 lands.

- **Content:** recurring vs. one-time giving, biased toward monthly; impact framing; social proof from donor recognition (1.3); the FundraiseUp modal.
- **Copy:** ship with minimal copy and iterate (decided 2026-09-13). The impact statement needs sign-off before it is final.
- **Frequency-default test** (analytics strategy, Q5): a second FundraiseUp element that defaults to monthly, placed on content pages, compared against the one-time default. It needs 1.1, so that element IDs are configurable, and the `fundraise_up_element_id` dimension registered in GA4.
- **Measure:** giving-page conversion and recurring share, before and after, in 30-day windows of engaged sessions.

#### 1.3 Donor recognition

*Now #5. Replaces the separate Donors post type in the roadmap (decided 2026-09-13).*

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

*Now #3, with minimal copy (decided 2026-09-13).*

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
- **Built locally, not yet on production.** Language Rights exists only in the local database and travels as a content bundle ([content-model.md](docs/content-model.md#content-bundles)). Its bundle is in `docs/local_docs/content/`.
  - **Language Rights** (`/language-rights`, plus nine FAQs). Built, with banner and block images; it depends on the banner caption style (#660) and Numbered Columns (#661) being deployed. Before it goes to production:
    - Upload `2026/11/language-rights-banner.jpg` and `2026/11/hilario-poot-cahun-class.jpg` there; the import matches images by path.
    - Decide whether this is the pre-launch page or the launch page: the banner says "First edition forthcoming", but parts of the body read as if the report is already out.
    - Identify the delegate in the banner photo, then name them in its alt text and caption.
    - Confirm Robby Thigpen's permission covers reusing his photo of Hilario Poot Cahun's class beyond the blog post.
    - Confirm the list of languages the team reads, quoted in "Help us get it right".
- **GTM `content_type` (open, done in GTM rather than in code).** `content_type` is a RegEx Table variable in GTM that maps `{{Page Path}}` to a content type and goes out on every `page_view`. Pages it doesn't match get no content type of their own, so their traffic can't be separated out in GA4.
  - **Native Language AI:** live since 2026-11-18 and not mapped yet. Match `/revitalization/resources/native-language-ai/`. The `/ai` short link redirects there before the page loads, so it needs no row of its own.
  - **Language Rights:** add `/language-rights` (and its FAQs) when it launches, and `/language-rights/report/` when the first region releases (2.2).
  - **Then:** publish the GTM container, and check the content type in GA4's real-time report or DebugView.
  - **Still open:** whether these pages get a shared type (for example `resource`) or one each.
- **Later in this workstream:**
  - Move the blog from Medium and Substack onto `/blog`, for search value.
  - Turn the translate-a-document invitation, now a `mailto:` link, into a form (see 4.2).

#### 2.2 Language Rights report

*Now #2: dated. The project announcement is on 10 December 2026; South and Central Asia, the first of seven regional releases, goes to press under embargo on 5 February 2027 and launches on 19 February. Dates are from the publishing plan, kept outside the repo.*

The *Report on the Global State of Language Rights* on wikitongues.org: a hub, seven region pages and one page per country or place (204 rows in Airtable, 171 Docs written). Content is authored in Airtable and Google Docs and reaches the site only through tagged releases, so editing never threatens production. How it works, the statuses and the editorial contract: [docs/language-rights.md](docs/language-rights.md).

##### Glossary

- **Country report:** one country's or place's assessment: a Google Doc plus its Airtable Reports row.
- **Region:** one of the seven publishing regions. A publishing group, not the archive's `region` taxonomy.
- **Release:** a tagged, checked JSON file holding every Completed country so far (`2027.1` … `2027.8`, patches `2027.1.1`).
- **Research status** (Airtable, per country, Kristen's names unchanged) and **release status** (WordPress, per region): two separate lists.
- **Version:** a report's own number (1.0, 1.1, 2.0), shown to readers. Not the release number.

##### Rules

1. Nothing syncs live. The site changes only when a release is imported, local → staging → production.
2. Airtable decides the answers; the Doc decides the text. A release fails if they disagree.
3. Only Completed countries enter a release; a region releases only when every country in it is Completed or Held. Kristen's status names stay as they are; Held is the one addition.
4. Release files hold embargoed text, so they live in a private repo, never in this one.
5. The import keys on Airtable record IDs, writes absolute state, and is a no-op on a second run.
6. Totals count Released regions only. The global synthesis appears only at the Complete Edition.
7. Reports and territory pages link both ways, matched on the archive Nation's record ID.
8. Every report has its own version. The exporter numbers it (minor for text, major when an answer changes); a released report that changes without a Changes note fails the release.

##### Example cases

| Case | Result |
|---|---|
| Afghanistan: Completed, five answers matching Airtable, a story card in its WordPress tab | `/language-rights/report/afghanistan/` with "At a glance", six sections, the card; the Afghanistan territory page shows a report block |
| An editor changes Afghanistan's Doc after release | Nothing changes on the site until a patch release is cut and imported. Without a Changes row the release fails; with one, Afghanistan goes to 1.1 and its page lists the note |
| Afghanistan's Indicator 3 changes from unclear to yes after release | Version 2.0; the changelog names the changed answer |
| Mexico's Doc has five answers, not seven | The Americas release fails, naming Mexico and the missing answers |
| A Doc says `Your answer: Yes.`, Airtable says `yes` | Passes: answers are compared without case or trailing punctuation |
| A country is Held | Left out of the release; its region page lists it as "Report forthcoming" |
| *Naoero* (rights base) vs *Nauru* (territory) | Linked through the Archive Nation ID, never by name |
| Europe is Embargoed | The public sees "Upcoming"; press accounts and admins read the full region |

##### Display

- **Hub** `/language-rights/report/`: the release schedule, methodology, indicators, answer key, director's letter, released regions; the global synthesis and global totals from the Complete Edition.
- **Region** `/language-rights/report/{region}/`: headline, introduction, findings, evidence and story cards, indicator comparison across its countries, the country grid, the treaty register (collapsible).
- **Country** `/language-rights/report/{country}/`: "At a glance" (headline sentence, language count, baseline and trend, five answers), the six sections with sources collapsed, story card, link to its territory and region.
- **Territory pages:** a block linking to the country's Released report.
- **Look** lifts the prototype's HTML into Stylus modules; the map is the 3.2 component; print CSS collapses sources and points to the PDF.
- **Instrument:** GTM `content_type` for `/language-rights/report/` (see 2.1); the gated PDF reports through the download gateway's existing GA4 funnel events.

##### Steps

Built in the week of 23 November 2026, unrelated to the release dates. One plugin, `wt-rights`; each PR passes `/test`.

| PR | Ships | Status |
|---|---|---|
| 1 | Structure config, Docs adapter, Doc parser, validator, versioner | [#705](https://github.com/wikitongues/wikitongues.org/pull/705) |
| 2 | Airtable and Google clients; `ping`, `check`, `export`; release dates and Changes notes | [#706](https://github.com/wikitongues/wikitongues.org/pull/706) |
| 3 | Post type, regions, routes, press role; `import`, `status` (region status sets post status) | [#707](https://github.com/wikitongues/wikitongues.org/pull/707) |
| 4 | Hub, region and country templates; territory card; donate placements; `/rights` | [#708](https://github.com/wikitongues/wikitongues.org/pull/708) |
| 5 | Gated PDFs, private until embargo and public at release; press login redirect | [#709](https://github.com/wikitongues/wikitongues.org/pull/709) |
| 6 | Data-layer page details and `rights_updates_signup` | [#710](https://github.com/wikitongues/wikitongues.org/pull/710) |

Stacked: merge in order. Then GTM and GA4 setup and the staging rehearsal, both in [docs/language-rights.md](docs/language-rights.md).

**Data first:** the Held option and the Changes and Publishing Docs tables go into the sandbox base before PR 2 reads them. **Project page** live for the 10 December announcement (2.1).

**Decided for the build (2026-11-22):** the exporter is PHP WP-CLI in this repo, run locally; reports are posts with HTML content and one JSON meta value (no ACF); region and front-matter Docs are listed in an Airtable "Publishing Docs" table with each region's release date; embargo is WordPress post status; no PDF exists at any public URL until its region is released; region and Complete Edition PDFs, when designed, are ordinary `documents` posts.

##### Open questions

1. **Kristen:** approve the editorial contract, the Held status and the Changes table in [docs/language-rights.md](docs/language-rights.md).
1. **Kristen:** is the list of treaties each nation is party to enough for region pages, or are actions and dates needed (and from where)?
2. **Language count:** the prototype shows 30 languages for Afghanistan; the Nations table says 34. Which is shown?
3. **Press access:** how journalist accounts are issued and revoked (tracked privately).
4. **Google access:** a service account with read access to the report folders.

##### Out of scope

Live sync from Docs or Airtable; editing report text in WordPress; earlier editions online; a designed PDF beyond the existing download gateway; translations of the report.

##### Decision log

| Date | Decision | Why |
|---|---|---|
| 2026-11-18 | Releases, not a live sync | Content edits must never threaten production; a release diff is the editorial review |
| 2026-11-18 | The global synthesis appears only at the Complete Edition | A synthesis updated for six months makes no launch (Kristen) |
| 2026-11-21 | URLs under `/language-rights/report/`, with `/rights` as a redirect | One home beside the project page; country URLs leave out the region so they never move |
| 2026-11-21 | Release files in a private repo; Google service-account access | Embargoed text stays out of the public repo |
| 2026-11-21 | Embargoed regions are readable by press accounts and admins, built in WordPress | Press needs the region before launch day; admins review it in place on production |
| 2026-11-21 | Kristen enters treaty actions to a written contract | The treaty register needs actions and dates Airtable lacks |
| 2026-11-23 | Proposed, open for Kristen: no Treaty actions table; region pages list the treaties each nation is party to, from the existing Nations ↔ Treaties links, without action or date | Supersedes the row above if she agrees. Keeps her work to Changes and Publishing Docs; if she wants actions and dates, look at fetching them from the UN Treaty Collection before asking anyone to type them |
| 2026-11-21 | Kristen, with Frederico, locks countries, following a pre-flight checklist | One owner for "ready", checked automatically where possible |
| 2026-11-21 | Territory pages link to their Released report | Two-way discovery between the archive and the report |
| 2026-11-22 | Keep Kristen's research status names; add only Held, and map the rest in code | No renames to coordinate or retrain; nothing in Airtable moves |
| 2026-11-22 | Build plan approved: see Steps. Simplified to WordPress post statuses for embargo, local export, no admin screen | Every goal met with the least machinery |
| 2026-11-22 | Each report has its own version and public changelog: the why in an Airtable Changes table, the what and the number from the exporter, shown in WordPress | Kristen writes one sentence, never touches GitHub; the log can't drift from what shipped |

### 3. Discovery

#### 3.1 Dynamic homepage

*Now #6.*

The homepage is static. Surface recent oral histories, the active campaign (from 1.1), featured languages and fellows, with a donation CTA in the stream. Decide which signals to use, such as the publication date or an editor-curated featured flag, and build from galleries server-side where possible. A "most recent video" block is a quick first step.

- **Measure:** homepage exit rate, pages per session, and the rate of homepage entries that end in a donation.
- **Evaluate** 60–90 days after launch by comparing donation rates for visitors who use discovery features against those who don't. That comparison is the thesis test, and its result decides what follows this queue.

#### 3.2 Maps

*Now #7. Spec drafted, prototyped and decided 2026-11-16.*

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
| Link from territory to shape | **A shape table in the plugin** (`wt-maps/data/shapes.json`), keyed on Airtable record IDs and holding Natural Earth codes (`units:GAZ`, `units:WEB` for Palestine). Replaced the planned ACF "Map shape" field (2026-11-19), so a shape change is reviewed with its rebuilt maps. |
| France | **Metropolitan France only** (`FXX`), because its overseas departments are territories of their own. |
| What counts as a territory | **An officially assigned ISO 3166-1 place** (6.8, 2026-11-19). Greenland, Guam, the Northern Mariana Islands and the French overseas departments are territories; Hawaiʻi, Alaska, Wales and Scotland are subdivisions, and so are non-ISO states such as Abkhazia and Kosovo. |
| A language spoken in part of a territory | **Its subdivisions** (6.8). For each territory, the map draws the subdivisions the language lists there, else the whole territory (Hawaiian → Hawaii; Tibetan → Tibet AR and four Indian states). The plugin translates each subdivision to a Natural Earth code through the shape table. Until the province-level layer and the subdivision data land (6.8 Steps), a curated focus list in the shape table covers the launch cases. |
| Languages in several territories | **Fit the frame to all of them.** The prototype showed this works (Russian), so there are no separate region maps for languages. |

##### Content changes that ship with the plugin

These are one-off edits in WordPress admin and Airtable, made before the backfill. Each follows the territory-change checklist below.

- **Fold Nagorno-Karabakh.** Natural Earth still draws the pre-2023 area, but Azerbaijan has controlled it since September 2023 and the republic dissolved in January 2024. Remove it from Armenian, its only language. Armenian keeps Armenia, Lebanon and Abkhazia. Don't add Azerbaijan, because a territory link says the language is spoken there now, and the Karabakh Armenian community left in 2023. Then unpublish the territory.
- **Add Greenland** as a territory (Natural Earth unit `GRL`, ISO `GRL`) and move Greenlandic to it from Denmark. Done 2026-11-17. Its Airtable origin moves from Denmark to Greenland when the coded rows move into the Territories table (6.8 Steps).
- **Add Guam and the Northern Mariana Islands** as territories (Natural Earth `GUM`, `MNP`). Link Chamorro to both and Carolinian to the Northern Mariana Islands. Done 2026-11-17. Their Airtable origin moves from the United States to Guam and the Northern Mariana Islands with the same row move.
- **Link Hawaiian to `Hawaii_US`** in Subject [Territories]. Its origin stays the United States, with Hawaii as its subdivision. Other candidates: Alaskan languages (`Alaska_US`), Welsh (`Wales_GB`), Scottish Gaelic (`Scotland_GB`). Areas below a subdivision (Navajo Nation, the Basque Country) need Natural Earth's admin-1 layer.
- **Later, through the review process (not a launch blocker): the other ISO 3166-1 entries we lack**, added when they have languages in the archive. Inhabited ones: Puerto Rico, the US Virgin Islands, American Samoa, Curaçao and Aruba (Papiamento), Sint Maarten, Saint-Martin, Saint-Barthélemy, the Faroe Islands (Faroese), Åland, the Isle of Man (Manx), Jersey (Jèrriais), Guernsey (Guernésiais), Niue (Niuean), Norfolk Island (Norfuk), Saint Pierre and Miquelon, Bermuda, the Cayman Islands, the Turks and Caicos, the British Virgin Islands, Anguilla, Montserrat, Saint Helena and the Falklands. Each addition runs the checklist below.
- **Rename "Sahrawi Arab Democratic Republic" to "Western Sahara"** (2026-11-17, from Kristen's review): Western Sahara is the UN territory name, and the SADR is the government in exile. The map already shows all of Western Sahara. The slug changes to `western-sahara`, with a redirect from the old one. Make the same change to the matching `nations_of_origin` values in Airtable.
- **Territory type: not needed** (2026-11-19). A territory is any ISO 3166-1 place; each nation's sovereign status (UN member, observer, de facto) comes from Airtable Nations and is stored, not a type.
- **Rename "Cape Verde Islands" to "Cabo Verde".** Done in WordPress 2026-11-17; the Airtable nation is already "Cabo Verde", and a nation rename there carries through to every language's `nations_of_origin`.
- **"Federated Staes of Micronesia" is fixed** to "Federated States of Micronesia" in production by hand (2026-11-17), and the Airtable nation renamed the same day, which updates its 18 languages' `nations_of_origin` (from "Micronesia"). WordPress keeps the old slug as a redirect.
- **Three nation names differed between Airtable and WordPress**: Congo / Republic of the Congo (37 languages), Macedonia / North Macedonia (2), Vatican State / Vatican City (1). The Airtable nations were renamed to the WordPress names on 2026-11-17, and the sync's temporary aliases come out in #674.
- **Keep as it is:** "Naoero", Nauru's official English name since August 2026.

##### Territory-change checklist

A territory touches up to four places. Change all of them together, or the map, the summary sentence or the territory pages drift apart. This goes in `docs/maps.md`.

1. **The territory post** (WordPress → Territories). Rename the title; leave the slug alone unless the URL must change, and if it does, add a redirect from the old slug. Unpublish rather than delete, so the old URL can redirect.
2. **Language links.** Edit the language's Territories field, or the territory's Languages field. The relationship is two-way, so either side updates both.
3. **The territory in Airtable.** `nations_of_origin` holds links to territory posts, matched by Airtable record ID (#687). Rename the Airtable record and the WordPress territory together; the Airtable change propagates to every linked language.
4. **The shape table** (`wt-maps/data/shapes.json`): the Natural Earth code for the territory's record ID. A territory without one shows no map, never a wrong one.

##### Territory review process

Coverage grows by small changes, not a big push, so the process has to stay cheap enough to run for a single territory and safe enough to run without a cartographer.

- **One request, one record.** A territory or map-focus request is a GitHub issue from a template: the place, the languages it affects, the Natural Earth code, and a source for the claim if it is contested. The issue is the queue and the audit trail.
- **One change, one PR.** The WordPress edits and the Airtable edits follow the checklist above. The regenerated maps and the `doctor` output then go in a single PR, because the build lives in this repo.
- **The review is reading two reports.** `wp wt-maps doctor` must be clean (no missing shapes, no unmatched `nations_of_origin`, no languages left without a territory by the change). The PR shows the contested-areas diff: every contested area a changed map now reveals, frames or hatches, against the last release. A change that touches no contested area needs one reviewer; one that does needs a second (the site lead or Kristen, whoever didn't make the change), and a source in the issue.
- **Batch the slow parts.** Data syncs and production writes happen at most once a month, for whatever has been merged since. The claims table gets its yearly review then too, owned by Kristen, and after any news that moves a border.
- **Backlog to start from:** the ISO gaps listed above; languages with neither territories nor origins, which show no map (16 on local data); and subdivisions for Alaskan languages, Welsh and Scottish Gaelic.

The plugin adds a check that turns this list into a report: `wp wt-maps doctor` lists territories without a shape, shape-table entries that match no place, languages whose places include one with no shape, and map sets with no built map. Run it after any territory change.

##### Data

- **Boundaries:** Natural Earth v5.1.2, which has no license restrictions: 1:50m for most maps, and 1:10m outlines for zoomed-in frames (under 6° radius) and for framing, because 1:50m drops most small islands (it has one of Tuvalu's nine atolls). Keep the source files and version in the plugin, so a rebuild gives the same result. Three layers: whole countries from `admin_0_countries`; the French overseas departments, Hong Kong, Macao, Kosovo, Taiwan, Somaliland, Northern Cyprus, Western Sahara, Palestine and Greenland from `admin_0_map_units`; the breakaway regions (Abkhazia, South Ossetia, Transnistria) and every disputed overlay from `admin_0_breakaway_disputed_areas`.
- **Matching.** `wikidata_id` is empty on all territories, on production too. Matching titles against Natural Earth's name fields finds 189 of 215 by name, and the other 26 come from a hand-written table; the prototype matched all 215. A WP-CLI command backfills "Map shape" from that table, and in the same pass fills `iso_code` and `wikidata_id` from Natural Earth where they exist. Five territories (Abkhazia, South Ossetia, Transnistria, Northern Cyprus, Somaliland) have no ISO code, and Kosovo uses the user-assigned XKX. The backfill is a bulk write, so follow the database-sync steps in CLAUDE.md first.
- **Too small to see.** Shapes under 1.5px are dropped at sidebar scale. A territory whose shape is still too small after zooming gets rings instead: one per island group (groups closer than 12 units merge), so Tuvalu shows as a chain of eight rings rather than one ring in empty sea.

##### Which countries to highlight

On local data, 7,536 language pages list exactly one territory, 44 list more than one, and none list zero.

- **Primary:** territories that are also the language's origins (`nations_of_origin` links). This is the same split the language page makes between "traditionally spoken in" and "also spoken in". If no territory is also an origin, every territory is primary.
- **Secondary:** the other territories, highlighted more lightly.
- **Context:** the neighboring countries.
- Don't add a "primary territory" field: `nations_of_origin` is already structured links (#671).

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
- **Where it goes:** `modules/languages/meta--languages-single.php`, under the "Where it's spoken" heading and above the territories it shows, with a caption naming them. The section is not a mobile accordion, so the map stays open.

##### Look

- **Brand (Dark full):** a deep blue sea, the brand hue at lower chroma (`oklch(0.33 0.11 285)`; the full `$blue()` `#3814a5` was too loud across most of the frame, against OS and Imhof's rule that saturated colour belongs in small areas), land in the site's `$blue(50)`, the language's primary territories in parchment with a deep-blue edge, and secondary territories in a pale blue between the two. Borders and the disputed-area hatch are blues too.
- **Terrain shading** on top, at a resolution matched to each frame. The land hillshade is computed from AWS Terrain Tiles (open elevation data, terrarium encoding; credit its sources per the Tilezen attribution list). The build picks the tile zoom from the frame's ground distance per pixel: zoom 3 for Russia at 13 km a pixel, zoom 10 for Malta at 195 m. It samples the tiles through the inverted d3 projection and shades from the north-west at 45°. Vertical exaggeration grows with pixel size (×2 for Malta, ×18 for Russia). Flat ground maps to neutral gray, and the result is cut to the vector coastline and blended with `mix-blend-mode: hard-light` (soft-light was tried and disappears on light fills). Natural Earth's SR_50M, used first, is fixed at about 3.7 km a pixel and went soft on small frames. The images ship at 600×450, quality 30: every test map is under 30 KB. Tiles are cached by the build, never fetched by the site.
- **Sea floor stays on Natural Earth** (GRAY_50M_SR_OB). The tiles' bathymetry has steps of several hundred metres between sources (near the antimeridian: Fiji, Tuvalu), and hillshading turns them into straight seams. The sea is background, so its softer resolution suits it.
- **Ocean floor, kept 2026-11-16.** Natural Earth's GRAY_50M_SR_OB carries seafloor relief: continental shelves, trenches, ridges and fracture zones. The build maps deep ocean to neutral gray, cuts the image to the sea, and blends it over the sea blue with `hard-light` at 65% opacity, fainter than the land shading so the coast stays the strongest edge. It adds 5–29 KB per page at 2×, less at the shipping size.
- **Contested areas** get dark dashes (`#24107a`, 1.4 units wide in the 400-unit frame, 3.6/2.2 dashes) on a parchment casing 2.6 wide, plus dark hatch lines. Not red: OS advises against red for sensitive subjects such as disputed land, and red on the land blue was 1.47:1, below the 3:1 minimum for graphics. The casing keeps the edge at 3:1 or better on sea, land and parchment alike. The hatch background still says how the area relates to the language: parchment when it involves one of the language's territories, the land blue when not. A territory that is itself a contested area (Abkhazia, Somaliland) is drawn solid, with the dashed edge.
- **Small territories** get a yellow (`$yellow`, `#ffff00`) ring when their largest island is under 150 square units in the 400×300 frame (about 6 px across in the sidebar), with a thin dark halo so it holds on the land blue as well as the sea.
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
- [x] Territory types: parked, out of scope, not a launch blocker (2026-11-18).
- [x] Review: a contested-area change needs a second reviewer, the site lead or Kristen; Kristen owns the yearly claims review (2026-11-17).

**Data** (scripted writes, so sync first)
- [x] Sync: Backup Prod DB (check the workflow is still enabled) → Sync to Staging → `tool-sync-db-from-prod.sh` (2026-11-17).
- [x] Confirm on production data that Chamorro and Carolinian have no territory and no `nations_of_origin` (2026-11-17).
- [x] The WordPress edits below are scripted in `docs/maps-prototype/territory-edits.php` (dry run by default, `execute` to apply, safe to rerun). **Done on local, staging and production 2026-11-17** and checked: the reverse links, the new territory pages, the language summaries and the redirect from the old Western Sahara slug. The script is on the server in `~/scripts/`, outside the web root. Production ran after a dated dump (`~/backups/prod_2026-11-17_pre-maps-territories.sql`); its language pages don't show the summary sentence yet, because that feature is only on staging. Found on the way: `wt_prefix_the()` knows only five names, so summaries read "Spoken in Northern Mariana Islands" (and "United States", "Philippines"). The new territories' official languages: Greenland Greenlandic; Guam English and Chamorro; Northern Mariana Islands English, Chamorro and Carolinian. Greenland sits in the Northern America region (UN M49).
- [x] Fold Nagorno-Karabakh: remove it from Armenian, then unpublish the territory (2026-11-17).
- [x] Add Greenland and move Greenlandic to it from Denmark (2026-11-17).
- [x] Add Guam and the Northern Mariana Islands; link Chamorro to both and Carolinian to the Northern Mariana Islands (2026-11-17).
- [x] Rename "Sahrawi Arab Democratic Republic" to "Western Sahara", slug `western-sahara`, with a redirect from the old slug (2026-11-17).
- [x] Rename "Cape Verde Islands" to "Cabo Verde" (2026-11-17).
- [x] Airtable nation renames that propagate to languages: Western Sahara, Cabo Verde, Federated States of Micronesia (2026-11-17).
- [x] Airtable: Chamorro's and Carolinian's nation set to United States, with `Guam_US` and `Northern-Mariana-Islands_US` as their territories (checked 2026-11-18).
- [x] Airtable: the nations Congo, Macedonia and Vatican State renamed to Republic of the Congo, North Macedonia and Vatican City (2026-11-17); nations of origin became territory links in WordPress (#671).
- [x] The subdivision objects, 6.8 phase B's maps slice: the `subdivisions` post type and Territories sync, the language link field, and the launch cases on production (2026-11-19; see the [archive](docs/plan-archive.md)).
- [ ] The ACF fields: "Map shape" on territories and subdivisions, and the `subdivisions` link field on languages (JSON, with `modified` bumped).
- [ ] Check the Airtable territory links with Kristen before the backfill.
- [ ] The plugin's table from Airtable territories to Natural Earth sub-units (`Hawaii_US` → `USH`), starting with the ones linked at launch.

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

*Now #8.*

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

*Done 2026-11-17; see the [archive](docs/plan-archive.md).* Nations of origin are territory links now. The number stays so later items keep theirs.

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

#### 6.8 Language geography across Airtable, Make and WordPress

*Spec approved 2026-11-20. Phases A and B's maps slice are done; history is in the [archive](docs/plan-archive.md). Working files (Airtable exports, the origin review and its results) are kept outside the repo.*

##### Glossary

| Term | Meaning | Airtable | WordPress |
|---|---|---|---|
| **Territory** | An officially assigned ISO 3166-1 place (249) | Territories (renamed from Nations, plus the 49 coded rows moved in) | `territories` post |
| **Subdivision** | A place below ISO level, inside one territory | Subdivisions (renamed from Territories, plus 6 non-ISO states moved in) | `subdivisions` post |
| **Origin** | The territories where a language is traditionally spoken (indigenous to) | Subject [Nation of Origin] (name kept) | `nations_of_origin` |
| **Also spoken in** | A language's territories minus its origin | derived | derived |

##### Rules

1. A territory is an officially assigned ISO 3166-1 place.
2. A language's territories are every place a local population speaks it. Origin is a subset of them, and may be empty (diaspora languages such as Romani).
3. A subdivision means "spoken in this part of the territory", whether or not the territory is an origin. Its territory is always one of the language's territories.
4. Never stored, always derived: "also spoken in", and a territory's nation.
5. Non-ISO states are subdivisions of their ISO territory, following ISO 3166-2 where it lists them (Abkhazia GE-AB, Kosovo RS-KM, Transnistria MD-SN).
6. Everything is keyed on Airtable record IDs. A row that moves table gets a new record and ID; one cutover re-stamps WordPress while Make is paused. No dual-ID support.

##### Example cases

| Language | Territories | Origin | Subdivisions |
|---|---|---|---|
| Tahitian | French Polynesia | French Polynesia | — |
| Hawaiian | United States | United States | Hawaii |
| Chamorro | Guam, Northern Mariana Islands | Guam, Northern Mariana Islands | — |
| Abkhaz | Georgia, Russia, Turkey | Georgia | Abkhazia |
| Tibetan | China, India, Nepal | China | Tibet AR; Sikkim and three more (India) |
| English | UK, Dominica, Saint Kitts and Nevis… | UK | — |
| French | France, Belgium, Switzerland, Lebanon… | France, Belgium, Switzerland | — |
| Romani | several | — | — |

##### Display

- **Language page:** "Traditionally spoken in" (origin), then "Also spoken in"; "Spoken in" when there is no origin.
- **Map:** for each territory, draw the subdivisions the language lists there, else the whole territory; origins primary. No per-language map setting.
- **Territory sidebar:** ISO territories with at least one language.
- **Territory pages:** their subdivisions, each with its languages, then the rest. Subdivisions have no pages or routes of their own.
- A non-ISO subdivision may be named on its own on a page; maps keep its disputed-area hatching.

##### Steps

- [ ] **Airtable sandbox:** rename Territories → Subdivisions, then Nations → Territories; move the 49 coded rows and the 6 non-ISO states as new records; add South Sudan's code (SS).
- [ ] **Origins:** apply the reviewed changes for the 46 multi-origin languages (2026-11-20) in the sandbox, then in the real base after approval.
- [ ] **Also spoken in:** decide its storage, a presence table (one row per language and territory, with a source) or a link field; seed it from open sources (Glottolog, the Nations official-language columns, Unicode CLDR).
- [ ] **Sync territories:** Make sends each language's origins (Nation of Origin) and presence as two complete lists of record IDs; the sync writes `nations_of_origin` = origins and `territories` = presence ∪ origins, through ACF so the territory pages' Languages field updates too. Rule 2 is enforced here and nowhere else. Until presence exists in Airtable, `territories` stays the February 2026 copy.
- [ ] **WordPress**, in small PRs that read both data shapes: the map rule, territory pages, the sidebar rule, the labels.
- [ ] **Cutover:** pause Make, move the rows in the real base, re-stamp WordPress posts and the map shape table, resume Make.
- [ ] **Maps:** add the province-level layer and subdivision shape codes, then rebuild (about 626 maps). Gets its own spec.
- [ ] **Remove the maps' origins fallback** (temporary since maps v1: a language with no territories draws its origins). Do it after the territories sync, once every origin is a territory; a rebuild and `wp wt-maps doctor` should show no change. Removing it earlier drops the maps of the ~490 languages with origins and no territories.

##### Open questions

1. About 50 single-origin languages record the sovereign instead of the coded territory (New Caledonia's ~35 languages and Tahitian → France). The 2026-11-20 review covered only multi-origin languages.
2. Retired ISO 639-3 codes (229 records): merge each into its successor, or keep it as historical?
3. Records that aren't single languages: `apa` (a collective code), `fre` (a duplicate of `fra`), and the macrolanguages Akan, Latvian, Norwegian and Sanskrit.
4. Storage for "also spoken in" (Steps).
5. Whether WordPress may edit synced archive data at all, or synced fields become read-only in wp-admin. Applies to every field `wt-airtable-sync` writes.
6. Nagorno-Karabakh: a WordPress page with no Airtable record.
7. The 2,641 subdivision proposals for languages with an origin and no subdivision, to review under the new rules.
8. Phase A loose ends: check Ngundu, Darkhat and Korean Sign Language; 42 multi-origin records list their origins in reverse order; Slovenian Sign Language has no origin in Airtable; `yug` and `yim` have no WordPress post; six records share the identifier `wart`.

##### Out of scope

Subdivision pages and routes; historical origin as a field; cell-based geography (H3); Ethnologue data without a licence; reorganizing Airtable beyond the two renames and row moves.

##### Decision log

| Date | Decision | Why |
|---|---|---|
| 2026-11-19 | A territory is an officially assigned ISO 3166-1 place | One standard; Greenland and Guam are places in their own right |
| 2026-11-19 | The territory sidebar lists ISO territories with at least one language | Not only UN members; empty places add noise |
| 2026-11-19 | Subdivisions get no pages or routes; they are listed on their territory's page | Keeps the work finishable |
| 2026-11-19 | Non-ISO states are subdivisions of their ISO territory | Follows rule 1; display may still name them alone |
| 2026-11-19 | Origin means traditionally spoken in (indigenous to), not historical origin | Attestable; historical origin is contested and belongs in prose |
| 2026-11-19 | The Airtable origin field keeps its name; "Traditionally spoken in" is a label | Archival policy |
| 2026-11-19 | Map focus is per territory: its listed subdivisions, else the whole territory | Precise without dropping territories; no per-language setting |
| 2026-11-19 | Subdivisions outside the origin are kept | One meaning for subdivisions |
| 2026-11-19 | Rename the Airtable tables; moved rows become new records with new IDs | Each name means one thing; no dual-ID support |
| 2026-11-19 | Make is paused during the cutover | Nothing syncs mid-change |
| 2026-11-19 | A diaspora language may have no origin | Choosing one by convention would be a false claim |
| 2026-11-20 | Make sends origins and presence as two lists; the sync writes territories as their union | Rule 2 in one place; absolute state; the archival origin field stays the only source of origins |
| 2026-11-20 | Maps v1 ships with the origins fallback, documented as temporary; `territories` stays the February copy until presence exists | Nothing new to build now; the February copy holds ~66 real extra places the review hasn't replaced |
| 2026-11-20 | Origin review approved for 46 languages (186 decisions); retired codes, `apa` and Wikitongues codes set aside | Evidence from Glottolog; one decision per card |

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

#### 7.8 Shared editorial styles

*In progress; spec approved 2026-11-23.* The donate page and the Language Rights report (2.2) share a look but not code: each defines its own eyebrow label, wash, rule, focus ring, disclosure toggle and px sizes, with slightly different values. Promote one set into `global.styl` and switch both pages to it.

##### Glossary

- **Eyebrow:** the small uppercase label above a heading (the report's "kicker").
- **Wash:** the tinted section background. **Rule:** a thin warm divider, not the blue `$border-color`.
- **Wide column:** the editorial page width, `$content-wide`. `$content` stays the site default.

##### Entities (new in `global.styl`)

| Name | Value | Replaces |
|---|---|---|
| `$wash` · `$rule` | `#f3eee0` · `#dcd5c3` | the report's wash and rule; donate's `#f2ead6` |
| `$grey-65` · `$red-text` | `blend(rgba($black,.65), $parchment)` = `#64635e` · `#b54a35` (AA on parchment and wash) | the report's muted text and red |
| `$content-wide` | `1200px` | the report wrap, donate's `1240px` |
| `$type-label/-small/-body/-lead/-deck` · `$space(n)` | 13/14/16/18/21px · `n × 8px` | literal sizes and spacing in both files |
| `eyebrow(color)` · `focus-ring(color)` | 13px/600, `.06em`, uppercase, links inherit · 3px outline on `:focus-visible` | the report kicker, donate eyebrow, donate's red ring |
| `disclosure()` · `signup-field()` / `signup-button()` | `+` that turns to × when open, reduced motion · the newsletter field and button | donate FAQ and report toggles · the report dialog's own field |

##### Rules

1. A colour, width or size is a literal in one place, its token; page files never rename a token.
2. PR 1 adds tokens only: the compiled CSS is unchanged.
3. PRs 2 and 3 change no rendered value except those listed in Display.
4. Breakpoints use `$mobile` / `$tablet` / `$smallScreen`; the report's own 640/780/940px queries move to the nearest.
5. `.wt_prose` owns bare tags; page files style their own classes. Sizes stay px (no rem shrink).

##### Example cases

| Element | Before | After |
|---|---|---|
| Hub hero eyebrow | `$red` 13px/700, 4.43:1 | `eyebrow($red-text)`, ≈5.4:1 |
| Region eyebrow with its report link | 13px text, 16px link | 13px throughout |
| Donate FAQ | `#f2ead6`, its own `+` | `$wash`, `disclosure()`; looks the same |
| Hub methodology indicators | no open/closed marker | `disclosure()` `+` |
| Report wrap at 1800px | 1657px wide | 1200px |
| Release-updates dialog field | black box, no focus style | the newsletter field |

##### Display

- **Donate:** wash `#f2ead6` → `#f3eee0`, column 1240 → 1200px, eyebrow tracking `.065` → `.06em`, focus ring red → blue.
- **Report:** muted text → `#64635e`, eyebrow weight 700 → 600, a focus ring and disclosure markers, the dialog field restyled, the column capped at 1200px.

##### Steps

| PR | Ships |
|---|---|
| 1 `refactor/cc/editorial-tokens` | `global.styl` tokens and mixins; compiled CSS unchanged |
| 2 `refactor/cc/rights-use-tokens` | `language-rights.styl`, the territory card class, the dialog; `newsletter.styl` moves to `signup-*()` |
| 3 `refactor/cc/donate-use-tokens` | `donate.styl` |

Each passes `/test` and a before/after check at 375, 1024 and 1280px on the hub, a region, a country, donate and one territory page. No database changes.

##### Out of scope

Moving editorial sizes to rem; link colour, button corners, what red means in the brand, report heading sizes; the report's other layout bugs; any other page's look.

##### Decision log

| Date | Decision | Why |
|---|---|---|
| 2026-11-23 | Promote shared editorial styles to `global.styl` | One system instead of two pages each inventing one |
| 2026-11-23 | Focus ring `$blue()`, `$parchment` on dark sections; donate switches from red | Red's brand meaning is undecided; blue is the site's interaction colour |
| 2026-11-23 | `$content-wide` 1200px; donate narrows from 1240px | One editorial width |
| 2026-11-23 | `$wash` `#f3eee0` | Used on more surfaces |
| 2026-11-23 | `signup-field()` carries the input reset (`border none`, `appearance none`) so it stands alone; `newsletter.styl` switches in PR 2 | Using it in the newsletter adds those two declarations, so PR 1 could not keep the compiled CSS byte-identical |

### 8. Membership (blocked on the board)

What membership means for Wikitongues (its scope, benefits, feel and impact) is a board decision that sits above website work. Nothing here starts until the board makes it. The contributor program's identity progression (email-known → contributor → member → fellow) and the visitor engagement profile (4.3) prepare the ground.

- **Language passport:** a member's view of their own engagement: languages explored, territories visited, videos watched, downloads.
- **Gamification:** stamps for core actions, and an onboarding flow. Write a separate spec first.

---

## GitHub issues

Open issues, and where each is tracked (2026-09-13):

| Issue | Tracked in |
|---|---|
| [#379](https://github.com/wikitongues/wikitongues.org/issues/379) Searching "russian" finds nothing | 3.3 (Now #8) |
| [#58](https://github.com/wikitongues/wikitongues.org/issues/58) Video thumbnails missing in search | 3.3 (Now #8) |
| [#378](https://github.com/wikitongues/wikitongues.org/issues/378) Gallery: dynamic querying | 3.3, designed with the results page |
| [#377](https://github.com/wikitongues/wikitongues.org/issues/377) Gallery: post-type fallback | [gallery.md → Known gaps](docs/gallery.md#known-gaps) |
| [#61](https://github.com/wikitongues/wikitongues.org/issues/61) "Processing" video page undesigned | 5.2 |
| [#4](https://github.com/wikitongues/wikitongues.org/issues/4) "Removed" videos | 5.2 |
| [#53](https://github.com/wikitongues/wikitongues.org/issues/53) Languages without standard names | 6.2, 6.5 |
| [#54](https://github.com/wikitongues/wikitongues.org/issues/54) Comma-form language names | 6.5 |
| [#72](https://github.com/wikitongues/wikitongues.org/issues/72) Caption file ID separators | 6.5 |
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
