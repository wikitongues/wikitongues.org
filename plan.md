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

Last reviewed 2026-11-23.

---

## Queue

### Now

| # | Item | Why now | Spec |
|---|---|---|---|
| 1 | Typeahead: Airtable lookups through WordPress | Header and archive search are down; the fix keeps the Airtable credential on the server | [7.9](#79-typeahead-airtable-lookups-through-wordpress) |
| 2 | Secret scanning in CI | Stop credentials, including ones inside build output, before they're committed | [7.10](#710-secret-scanning-in-ci) |
| 3 | Browser credential scan | Check what visitors actually receive, before each production deploy | [7.11](#711-browser-credential-scan) |
| 4 | FundraiseUp configuration in ACF, and a campaign banner | The year-end campaign has to launch and change without deploys; it needs to be on production by mid-November | [1.1](#11-fundraiseup-configuration-and-campaign-banner) |
| 5 | Language Rights report: hub, region and country pages, released from Airtable and Google Docs | Dated: announcement 10 December, first regional release 19 February | [2.2](#22-language-rights-report) |
| 6 | Pages and navigation, with minimal copy | Tell the story, and give contributors a way in (`/contribute`) | [2.1](#21-pages-and-navigation) |
| 7 | Giving page redesign | The page donors land on; bias it toward monthly | [1.2](#12-giving-page-redesign) |
| 8 | Donor recognition | Social proof for the giving page, built on People | [1.3](#13-donor-recognition) |
| 9 | Dynamic homepage | The first real test of the engagement thesis | [3.1](#31-dynamic-homepage) |
| 10 | Maps, step 2: interactive territory and region maps (step 1, the locator map, shipped) | Discovery by place | [3.2](#32-maps) |
| 11 | Enhanced search | Find languages by name, then faceted results | [3.3](#33-enhanced-search) |

The order follows the product roadmap, with two exceptions: the typeahead and scanning work (#1–3) goes first because search is down, and the small FundraiseUp piece goes before the rest because of the year-end campaign. Search's first step (#379) is small and can ship early.

**Audit, 2026-11-23:** the work hasn't followed this order. Every PR since 6 November went to the Language Rights report (#5), maps (#10), language geography (6.8) and shared styles. #4 hasn't started, and #6–9 haven't moved since the donate redesign. Either return to the order or re-order the queue.

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

*Now #1. On production by mid-November. **Not started as of 2026-11-23**: the date has passed, and the organization ID is still hardcoded in `modules/page--head.php`.*

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

- **Status (2026-11-06; styles updated 2026-11-23 to the shared editorial tokens, #718):** the new layout is in, built from editorial blocks like the homepage ([content-model.md → Donate page](docs/content-model.md#donate-page)). Still open: copy sign-off, story photo consent, social proof (1.3), the frequency test, and the design's Graphik Light (body text) and Schnyder Wide L (section labels) web fonts. Our Commercial Type licence covers neither, so the page uses Graphik Regular and Schnyder Wide M until they're licensed. The gift box's campaign ID is a block field for now, and should move to `wt_active_campaign()` once 1.1 lands.

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
- **On production as a draft.** **Language Rights** (`/language-rights`) is a draft on production, with its nine FAQs published and its banner and block images uploaded; going live is publishing it. While it is a draft, a logged-out visit is guessed by WordPress into one of the FAQs. Before publishing:
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
- **Region** `/language-rights/report/{region}/`: a map of its countries, headline, introduction, findings, evidence and story cards, indicator comparison across its countries, the country grid, the treaty register (collapsible), the region's letter from the director.
- **Country** `/language-rights/report/{country}/`: its territory's map, "At a glance" (headline sentence, language count, baseline and trend, five answers), the six sections with sources collapsed, story card, link to its territory and region.
- **Territory pages:** a block linking to the country's Released report.
- **Look** lifts the prototype's HTML into Stylus modules; the map is the 3.2 component; print CSS collapses sources and points to the PDF.
- **Instrument:** GTM `content_type` for `/language-rights/report/` (see 2.1); the gated PDF reports through the download gateway's existing GA4 funnel events.

##### Steps

Built in the week of 23 November 2026, unrelated to the release dates. One plugin, `wt-rights`; each PR passes `/test`.

| PR | Ships | Status |
|---|---|---|
| 1 | Structure config, Docs adapter, Doc parser, validator, versioner | Merged, [#705](https://github.com/wikitongues/wikitongues.org/pull/705) |
| 2 | Airtable and Google clients; `ping`, `check`, `export`; release dates and Changes notes | Merged, [#706](https://github.com/wikitongues/wikitongues.org/pull/706) |
| 3 | Post type, regions, routes, press role; `import`, `status` (region status sets post status) | Merged, [#707](https://github.com/wikitongues/wikitongues.org/pull/707) |
| 4 | Hub, region and country templates; territory card; donate placements; `/rights` | Merged, [#708](https://github.com/wikitongues/wikitongues.org/pull/708) |
| 5 | Gated PDFs, private until embargo and public at release; press login redirect | Merged, [#709](https://github.com/wikitongues/wikitongues.org/pull/709) |
| 6 | Data-layer page details and `rights_updates_signup` | Merged, [#710](https://github.com/wikitongues/wikitongues.org/pull/710) |
| 7 | Match the review prototype; region overview and front matter in the release; region maps | Merged, [#714](https://github.com/wikitongues/wikitongues.org/pull/714) |
| 8 | Releases screen: set a region's status from the admin, logged; reports a visitor can't read redirect to their region | Merged, [#715](https://github.com/wikitongues/wikitongues.org/pull/715) |
| 9 | The exporter reads the region and front-matter Docs into the release file's `overview` and `front_matter` | Next |

PRs 1–8 are merged and deployed; production stays dark until a release is imported (decision log, 2026-11-23). Next: PR 9, then GTM and GA4 setup and the staging rehearsal, both in [docs/language-rights.md](docs/language-rights.md). Not built yet: global findings on the hub (open question 5), the treaty register (open question 2), language counts (open question 3), "last changed" dates on the hub and region pages, and a report-specific gateway question set (4.1).

**Data first:** the Held option and the Changes and Publishing Docs tables go into the sandbox base before PR 2 reads them. **Project page** live for the 10 December announcement (2.1).

**Decided for the build (2026-11-22):** the exporter is PHP WP-CLI in this repo, run locally; reports are posts with HTML content and one JSON meta value (no ACF); region and front-matter Docs are listed in an Airtable "Publishing Docs" table with each region's release date; embargo is WordPress post status; no PDF exists at any public URL until its region is released; region and Complete Edition PDFs, when designed, are ordinary `documents` posts.

##### Open questions

1. **Kristen:** approve the editorial contract, the Held status and the Changes table in [docs/language-rights.md](docs/language-rights.md).
2. **Kristen:** is the list of treaties each nation is party to enough for region pages, or are actions and dates needed (and from where)?
3. **Language count:** the prototype shows 30 languages for Afghanistan; the Nations table says 34. Which is shown?
4. **Press access:** how journalist accounts are issued and revoked (tracked privately).
5. **Kristen: global findings before the Complete Edition?** The latest prototype's hub shows key findings and per-indicator charts across all 170 scored reports now; the decision log (2026-11-18) says global numbers appear only at the Complete Edition. *Impact:* showing them early puts unreleased regions' answers in public aggregates before those regions are fact-checked or embargoed; keeping the rule means the hub shows only the schedule, methodology and letter until 25 August 2027. The component can be built either way.

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
| 2026-11-23 | Production stays dark until the release plan calls for it; `/rights` points to the project page | wt-rights is deployed but set up on production by hand: once it is active with a release imported, the hub shows names, dates and nations even while upcoming, so activating it is itself a release step. A short link to the hub would 404 until then |
| 2026-11-23 | Match the latest prototype's look, reusing site styles; region overview, front matter and a per-region director's letter travel in the release file (optional, absolute state); maps come from wt-maps (a region set per release region) | Frederico's browser review; demo on staging with mock content |
| 2026-11-23 | No automatic release on the planned date; a person releases each region, from the Releases screen or WP-CLI, and every change is logged | Launch day stays a deliberate act |
| 2026-11-23 | A report a visitor can't read redirects (302) to its region instead of a 404 | Shared and indexed links keep working when a region is pulled back or a report leaves a release; the region shows no report text before release |
| 2026-11-22 | Each report has its own version and public changelog: the why in an Airtable Changes table, the what and the number from the exporter, shown in WordPress | Kristen writes one sentence, never touches GitHub; the log can't drift from what shipped |

### 3. Discovery

#### 3.1 Dynamic homepage

*Now #6.*

The homepage is static. Surface recent oral histories, the active campaign (from 1.1), featured languages and fellows, with a donation CTA in the stream. Decide which signals to use, such as the publication date or an editor-curated featured flag, and build from galleries server-side where possible. A "most recent video" block is a quick first step.

- **Measure:** homepage exit rate, pages per session, and the rate of homepage entries that end in a donation.
- **Evaluate** 60–90 days after launch by comparing donation rates for visitors who use discovery features against those who don't. That comparison is the thesis test, and its result decides what follows this queue.

#### 3.2 Maps

*Now #7. Step 1, the locator map on language pages, shipped 2026-11-23 (see the [archive](docs/plan-archive.md) and [maps.md](docs/maps.md)). Step 2 is next.*

##### Step 2: interactive maps

- **MapLibre GL** with vector tiles that we host ourselves: a Protomaps PMTiles file served from the plugin or from uploads. No API key in the browser and no third-party tile server. The style reuses the Brand colors and the hillshade.
- **Territory pages:** the territory highlighted, with the languages listed beside the map. Languages don't have coordinates, so link from shaded countries, not language markers, until there's a source for language locations (Glottolog has some, keyed by `glottocode`).
- **Region pages:** each territory shaded by how many languages it has, linking to its territory page.
- **A world view**, if one is wanted (homepage, region index): compare a Waterman butterfly with Equal Earth in the Brand style before choosing.
- **Load only when visible.** Load MapLibre only when the map scrolls into view, and show the static step 1 SVG until then, and as the fallback when JavaScript is off.
- **Performance:** some territories list hundreds of languages (India: 403). Read raw IDs, as territory pages already do.
- **Instrument:** `map_interaction` (`territory`, `action`). Step 1 is static and has nothing to track.
- **Choose the approach first (open, 2026-11-23).** The bullets above assume MapLibre GL. Two explorations on 2026-11-23 built on the static step 1 SVGs instead: hover labels and click-through on the sidebar map (spec approved and parked on branch `claude/maps-interactivity-01b803`), and prebuilt maps on every territory page and the 22 sub-region pages (spec on branch `claude/maps-place-pages-b22447`). The Language Rights region maps (#714) already build a wt-maps set per release region. Settle one approach here before building.
- **Idea (2026-11-23): two sidebar maps** for a language with more than one territory: an origin map (where it's traditionally spoken) and a range map (everywhere it's spoken), as Wikipedia's language infoboxes do. The range map needs 6.8's "also spoken in" data; until then the two maps would differ only through the February `territories` copy.

##### Step 1 follow-ups

- [ ] A reviewed claims table that overrides Natural Earth's 2022-era claims (Russia's 2022 claims, the end of Nagorno-Karabakh).
- [ ] Credits on the site's credits page: Natural Earth, and the Terrain Tiles sources.
- [ ] Review tooling: a territory-request issue template, and the contested-areas diff the build writes for each PR.
- [ ] Put the yearly claims review on Kristen's calendar.
- [ ] Two maps over budget: Tajikistan's hillshade (32.9 KB) and French's six-place set (20.9 KB gzipped SVG).
- [ ] Run the `decorate()` test in CI; it skips there because WordPress core isn't in the repository.
- [ ] The deploy's delete pass stops at 100 files, so a rebuild that drops more than about 25 maps fails it. Handle before the subdivision rebuild (6.8, Maps step).
- [ ] Remove the origins fallback (6.8 Steps).

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
- **A follow-up question set for the Language Rights report.** Today report PDFs use the general `documents` follow-up form. Design a report-specific set asking who the reader is (journalist, advocate, researcher, government, community member) and what they'll use it for; start from the intake brief in `docs/local_docs/`.
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
- **Editorial styles, left open by 7.8** (shipped; [content-styles.md](docs/content-styles.md#editorial-pages-shared-tokens)): move editorial sizes to rem with the mobile type shrink; one link colour (the report's `$rights-note` or `$blue()`); what red means in the brand; report button corners and heading sizes.
- A deploy health check that covers more than the homepage.
- Internationalization (long-term).
- **Resource card ability dedupes on URL.** `wikitongues/add-resource-card` refuses any URL already on the Resources page. That one check does two jobs: making retries safe, and an unplanned "one card per URL" content rule. It wrongly refuses genuine cards that share a URL, and lets the same resource through under a variant URL. When a second write ability lands, give all write abilities a caller-supplied request ID for retries, and add content rules only where someone asks for them.

#### 7.7 Content styles: one prose module

*In progress.* `prose.styl` and `.wt_prose` exist, and three surfaces use them: editorial text rows, the fellow "About" block and blog posts. The rules and decisions are in [content-styles.md](docs/content-styles.md). Still to migrate, one at a time and each checked against before-and-after screenshots of the pages that use it:

- **`page.php`** (`.entry-content`) and **FAQ items** in galleries, which want a `--compact` variant.
- **Careers**, **Archive success** and the **giving campaign**.
- **Retire the text row `image` / `image_caption` fields.** Images now go in the WYSIWYG; Valbina's profile is the only user, and her photo moved into her text locally on 2026-10-15; make the same edit on staging and production first. Remove them from the ACF JSON *and* trash them in the admin (deleting the JSON alone doesn't), then drop the field branch in `text-layout.php`.
- **Strip `.main-content` to layout** last. It is still both a layout box and a tag stylesheet (`ul li { float: left }`); the Revitalization application page's list is the last thing that depends on its float rules.

#### 7.9 Typeahead: Airtable lookups through WordPress

*Now, #1.* The typeahead's Airtable mode called Airtable from the browser, so its credential shipped in the built JS. Airtable revoked it on 2026-11-23, and header and archive search have been down since. Move the call to the server and keep the query, so results don't change. Two repos: `wikitongues/typeahead` (the app) and this one (the plugin and its committed build).

**Entities**

| Term | Meaning |
|---|---|
| Typeahead app | The React app in `wikitongues/typeahead`; its build is committed to `wp-content/plugins/typeahead/build` |
| Proxy endpoint | New: `GET /wp-json/wt/v1/typeahead?q=…`, in the typeahead plugin |
| Search settings | The existing ACF options page *Custom Search*: token, base, table, field IDs. Unused by code today |

**Rules**

- The token lives only in Custom Search, entered per environment. It never reaches the browser, a build or a repo.
- The new token is read-only (`data.records:read`) on one base.
- The endpoint runs the app's current query (`FIND` on the label and identifier fields, same fields returned), so results match.
- The query is escaped before it goes into `filterByFormula`. Today it is interpolated raw.
- A query under 2 characters returns `[]` without calling Airtable.
- Answers are cached per normalised query (trimmed, lowercase) in a transient for 10 minutes. With the app's 300 ms debounce, this keeps us under Airtable's 5 requests/second per base.
- On an Airtable error or timeout: return `[]` and write an `error_log` line. Never pass Airtable's error body to the browser.
- The app drops `apiKey`, base, table and field IDs from its settings and `.env`, calls the endpoint, and stops logging API responses to the console.
- Order: the endpoint ships first, then the rebuilt app.

**Example cases**

| Input | Expected |
|---|---|
| `haw` | Hawaiian in the results, same order as the old Airtable mode |
| `tahitian` | Tahitian |
| `cha` | Chamorro among the results |
| `h` | `[]`, no Airtable call |
| `a"b` | Normal results or `[]`; no formula error |
| Same query twice within 10 minutes | One Airtable call |
| Token missing, or Airtable down | `[]`; the search box still renders; error logged |

**Display:** no change.

**Open questions:** is 10 minutes the right cache, or hours, since language names rarely change? Retire the WordPress mode (`custom/v1/search`) or keep it as a fallback?

**Out of scope:** better search results, which belong to [3.3](#33-enhanced-search).

#### 7.10 Secret scanning in CI

*Now, #2.* Catch credentials before they're committed, including in committed build output.

**Rules**

- gitleaks runs on every PR in this repo and in `wikitongues/typeahead`. A finding fails the check. It scans `build/` folders too.
- At setup, one full-history scan of each repo. Findings are reviewed privately, never in issues.
- GitHub secret scanning and push protection turned on in both repos, where the plan allows it for the private repo.
- Allowlist in `.gitleaks.toml`, one reason per entry. Public identifiers (Airtable base, table and field IDs, GA4 measurement ID, publishable keys) are allowed by name.

**Example cases**

| Change | Expected |
|---|---|
| A PR whose built JS contains an Airtable `pat…` token | Fails |
| A PR that adds an Airtable base ID (`app…`) | Passes |
| A PR that adds a private key or `.env` file | Fails |

**Open question:** does the organization's GitHub plan include push protection on private repos?

**Out of scope:** secrets in the database, and moving `wp-config.php` secrets to `.env` ([7.6](#76-smaller-items)).

#### 7.11 Browser credential scan

*Now, #3.* Check what visitors actually download, which a repo scan can miss.

**Rules**

- A Playwright script loads a fixed page list on a target URL (local or staging): home, archive search, a language, a video, a territory, the giving page, the Language Rights hub.
- It records every response body (HTML, JS, JSON) and the page's `window.*Settings*` objects, and checks them against the gitleaks rules from 7.10.
- It reports the URL and the rule that matched, never the matched value.
- It runs from the deploy workflow against staging; a hit blocks the production deploy. It can also be run by hand.
- This is the first Layer 4 piece of [7.3](#73-tests).

**Example cases**

| Target | Expected |
|---|---|
| A page loading the old typeahead bundle | Flagged |
| The site after 7.9 ships | Clean |
| A page with the GA4 measurement ID | Clean (allowlisted) |

**Open question:** do third-party scripts (FundraiseUp, GTM) get scanned, or skipped as not ours?

**Out of scope:** logged-in and admin pages.

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
