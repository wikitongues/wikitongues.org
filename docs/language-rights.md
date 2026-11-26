# Language Rights report

How the *Report on the Global State of Language Rights* reaches wikitongues.org: its pages, its sources, how a release is cut, and the editorial contract the research team works to. The spec, phases and decision log are plan item [2.2](../plan.md#22-language-rights-report). The plugin is `wt-rights`; the pages are built (PRs #704–#715). Not built yet: the exporter reading the region and front-matter Docs, global findings on the hub, the treaty register, language counts, and "last changed" dates on the hub and region pages.

## Table of Contents

- [Pages](#pages)
- [Sources](#sources)
- [Releases, not a live sync](#releases-not-a-live-sync)
- [Statuses](#statuses)
- [Editorial contract](#editorial-contract)
  - [Airtable: Reports](#airtable-reports)
  - [Airtable: Changes](#airtable-changes)
  - [Airtable: Publishing Docs](#airtable-publishing-docs)
  - [Country Doc](#country-doc)
  - [The WordPress tab](#the-wordpress-tab)
  - [Region Doc](#region-doc)
  - [Front-matter Doc](#front-matter-doc)
- [Pre-flight checklist](#pre-flight-checklist)
- [Versions and the changelog](#versions-and-the-changelog)
- [Correcting a published report](#correcting-a-published-report)
- [Brief for Kristen's Claude](#brief-for-kristens-claude)
- [Links between reports and territories](#links-between-reports-and-territories)
- [Analytics](#analytics)
- [Staging rehearsal](#staging-rehearsal)

---

## Pages

| Page | URL | Built from |
|---|---|---|
| Project | `/language-rights/` | The launch page (`template-rights-project.php`): hero with "Get release updates", the page's own content (why it matters, the white paper), the donate band and the [schedule](#the-public-schedule). Its content is edited in WordPress |
| Report hub, and the global synthesis from the Complete Edition | `/language-rights/report/` | Front-matter Doc and the release schedule; global findings and the synthesis only from the Complete Edition |
| Region (7) | `/language-rights/report/{region}/`, e.g. `/language-rights/report/south-central-asia/` | Region Doc, plus answer counts computed from its country reports, and a map of its countries |
| Country report (one per Reports row) | `/language-rights/report/{country}/`, e.g. `/language-rights/report/afghanistan/` | Country Doc and its Airtable row, and its territory's map |
| Releases (admin) | Language Rights Reports → Releases | Each region's release status; see [below](#when-files-reach-a-server) |
| Short link | `/rights` | Redirects to the project page, `/language-rights/`, like `/ai`. Not to the report hub, which stays a 404 on production until a release makes it public |

A country's URL leaves out its region: regions are publishing groups a later edition may regroup, and an indexed URL should never move. Region and country slugs share one namespace, so the exporter never gives a country a region's slug.

**A report the visitor can't read redirects to its region.** That covers a region not yet released, one set back to upcoming or embargoed, and a report dropped from a release. The redirect is temporary (302), to the region page's country list, which shows only the release date and nation names until release, so no report text leaks and search engines keep the URL for when it returns. A slug no report has stays a 404, as does the print view with a wrong token.

## Sources

| Source | Holds | Owner |
|---|---|---|
| Airtable, *Language Rights Reports* base | One row per country: indicators, overall rating, region, status, Doc link, credit; treaties linked to each nation | Kristen |
| Google Docs, Drive `[1] Reports/{region}/` | One Doc per country (the report text and its WordPress tab), and one Doc per region | Kristen |
| Google Docs, Drive `[2] Notes & Templates/` | The front-matter Doc: methodology, indicator definitions, answer key, director's letter, global synthesis | Kristen |
| WordPress | Region release status, press access, the project page | Frederico |

Airtable decides the answers (the five indicators and the overall rating). The Doc decides the text. A release fails if the two disagree.

## Releases, not a live sync

The archive syncs from Airtable every 15 minutes. The report does not sync at all. Edits in Docs or Airtable reach the site only when someone cuts a release.

```
Airtable + Google Docs
        │  exporter: reads, parses, checks (fails on any contract violation)
        ▼
release file, e.g. rights-2027.1.json  ── private content repo, git tag rights-v2027.1
        │  the pull request diff is the editorial review
        ▼
wp wt rights import <file>   dry run by default → local → staging → production
```

- **The private repo.** Release files hold embargoed text, so they never enter this public repo. The exporter's code can live here.
- **Release numbers.** `2027.1`–`2027.7` are the regional releases in publication order; `2027.8` is the Complete Edition; a release carrying only corrections is a patch (`2027.1.1`). Release numbers track deploys and are internal. Readers see each report's own [version](#versions-and-the-changelog).
- **A release is cumulative.** `2027.2` holds South and Central Asia *and* Europe, so any one file rebuilds the whole report on an empty site. Every release also lists all seven regions with their planned dates and nation names, without any report text for regions it doesn't include, so the hub's schedule is always complete.
- **Import is idempotent.** It matches each country by its Reports record ID, writes absolute state, and reports 0 changes when run twice. Countries are never deleted by an import; a country dropped from a release is unpublished (set to draft) and listed in the dry run, and its URL then redirects to its region.
- **Preview.** `--preview` exports unfinished countries as drafts, for reading on staging only.

### Running the exporter

The exporter runs on one machine only, with two secrets that live in 1Password and are filled in for the length of one command. Nothing goes in `wp-config.php`, and production never has them: it only imports.

| What | Where |
|---|---|
| Google | Service account `rights-exporter@wikitongues-rights-worker.iam.gserviceaccount.com` (project `wikitongues-rights-worker`, Docs API only, no project roles). Viewer on `[1] Reports` and `[2] Notes & Templates`. Its JSON key is a 1Password Document item |
| Airtable | A personal access token, read-only (`data.records:read`, `schema.bases:read`), limited to the sandbox base. A production token is lent only when needed, then revoked |
| `~/.config/wikitongues/rights.env` | Two `op://` references, `WT_RIGHTS_GOOGLE_CREDENTIALS_JSON` and `WT_RIGHTS_AIRTABLE_TOKEN`, and no secrets |

```bash
op run --env-file ~/.config/wikitongues/rights.env -- wp wt rights ping --allow-root
op run --env-file ~/.config/wikitongues/rights.env -- wp wt rights check --region=south-central-asia --allow-root
op run --env-file ~/.config/wikitongues/rights.env -- wp wt rights export --region=south-central-asia --release=2027.1 --out=<releases repo>/rights-2027.1.json --allow-root
```

`WT_RIGHTS_AIRTABLE_BASE` picks the base: `sandbox` (default) or `production`, the real Language Rights Reports base; field IDs per base are in `wt-rights/config/airtable.php`. Real exports use `production`, with the lent read-only token. `export` refuses to write inside the WordPress folder.

### When files reach a server

A region's report text and PDFs are on a server only once that region goes to embargo. Before that, the server has its name, planned date and nation list, and nothing else. Staging previews are the one exception.

| When | Step | On the server afterwards |
|---|---|---|
| Any time | Import any release | The schedule: all seven regions, their dates and nation lists |
| Embargo (about two weeks before launch) | Export the release and its PDFs locally; copy them to the server's home folder (never `public_html`); `wp wt rights import <file> --execute`, then `wp wt rights status <region> embargoed --pdfs=<dir>` | Report text, readable by press and admins; PDFs in `WT_RIGHTS_PRIVATE_DIR` only |
| Launch day | **Language Rights Reports → Releases → Release**, or `wp wt rights status <region> released` | Pages public; PDFs in the media library, downloadable through the gateway (email required) |
| If a region is pulled back to upcoming | `wp wt rights status <region> upcoming` | Its PDFs are deleted from the private folder and the media library |

`import --pdfs` stages PDFs only for regions already embargoed or released, so a re-import with corrections works without a separate step.

**The Releases screen** (Language Rights Reports → Releases, admins only) shows each region's status, planned date, reports, PDFs staged and published, and its last change. Its buttons run the same code as `wp wt rights status`, after a confirmation that says what the change does; a repeat changes nothing. Every change, from the screen or WP-CLI, is logged on the region (term meta `status_log`, newest first, last 20) with who made it: a user's login, or `WP-CLI`. PDFs still reach the server only from the command line: after embargoing from the screen, run `import <file> --execute --pdfs=<dir>` to stage them; the screen warns when a region has reports without a PDF, and names whom to ask (`WT_RIGHTS_RELEASE_CONTACT` in `admin.php`, or the `wt_rights_release_contact` filter). Nothing is released automatically on the planned date.

**Each environment needs, once:** `WT_RIGHTS_MAILCHIMP_TAG` (the Mailchimp tag ID that marks a Language Rights signup) and `WT_RIGHTS_DONATE_URL` (the campaign link for report donations) in `wp-config.php`; without them signups go untagged and donations go to `/donate/`. And `WT_RIGHTS_PRIVATE_DIR` in `wp-config.php`, outside `public_html` (staging sits inside production's `public_html`, so neither can use a folder under it); and, in Settings → Download Gateway, the `rights_reports` row Required, with the `documents` follow-up form.

**PDFs** come from `wp wt rights pdf --out=<dir>`, run locally after importing the release: headless Chrome prints each report's print view (sources open, no site chrome) as `{slug}-v{version}-{hash}.pdf`.

## Statuses

Two separate status lists: one per country, set in Airtable by the editorial team, and one per region, set in WordPress at launch.

### Research status (Airtable, per country)

Kristen's existing options stay exactly as they are. Only **Held** is added. The exporter maps each option to what it means for a release; that mapping lives in code, so nobody has to learn new names.

| Airtable option (unchanged) | Rows on 2026-11-21 | Meaning for a release |
|---|---|---|
| Unitiated | 28 | Not written. Left out; the region page lists the country as "Report forthcoming" |
| In progress | 3 | Being written. Left out, and blocks its region's release |
| Pending review | 173 | Draft complete, under editorial review. Left out, and blocks its region's release |
| Fact checking | 0 | Text approved, sources being verified. Left out, and blocks its region's release |
| **Completed** | 0 | **Ready to publish.** Text, answers and sources are final. The only status that enters a release |
| Held *(new)* | — | Deliberately left out of its region's release; listed as "Report forthcoming". Does not block the release |

- A region can be released only when every country in it is **Completed** or **Held**. Unitiated, In progress, Pending review and Fact checking all block it: someone has to decide, country by country, between finishing and holding.
- Moving a Completed country back to Pending review is how a correction starts (see [below](#correcting-a-published-report)).
- Held is added in the sandbox base first, then the real one.

### Release status (WordPress, per region and for the global synthesis)

| Status | Public sees | Press accounts and admins see |
|---|---|---|
| Upcoming | Release date, nation list, "Get release updates"; report URLs redirect to the region | Press: the same as the public. Admins: the full region and its reports, under a "Preview: not yet released" banner |
| Embargoed | The same as Upcoming | The full region and its reports, under an "Embargoed until…" banner, not indexed |
| Released | Everything | Everything |

A report exported with `--preview` stays a draft at every status: only admins can read it.

**Press accounts** are made in Users → Add New, role **Press**. They land on the hub after logging in, never see wp-admin or the admin bar, and get a strip at the top of every page naming the account, with a **Log out** link back to the hub (`wt-rights/includes/press.php`).

The hub shows no global findings before the Complete Edition (decision 2026-11-18; Kristen is asked to confirm it, plan 2.2 open question 5), so no unreleased answer reaches an aggregate. The global synthesis has no status of its own yet: the schedule lists it as milestone 09 with its planned date from the front matter.

### The public schedule

One component (`modules/rights/schedule.php`) renders the schedule on the project page and the hub. It numbers *milestones*, not releases: the project announcement is 01, each region its release order + 1 (South and Central Asia is 02), the Complete Edition 09; region pages read `02 / 09`. Release numbers (`2027.1`…) are internal and unchanged. The announcement row appears when the release's `front_matter.announcement.release_date` is set, with "Read the white paper" when `paper_url` is. The exporter doesn't write `announcement` yet: until it reads it from the front-matter Doc or the Publishing Docs table, add it to the release file by hand.

**Announcement day (10 December) is a release step** (decision 2026-11-25): import a release with the schedule, front matter and announcement but no report text, so the project page and the hub show the schedule. The white paper is an open PDF in the media library, not a gated download.

## Editorial contract

What the research team provides, and what the exporter checks. Every rule below is checked automatically; a release that breaks one fails with a message naming the country and the rule.

### Airtable: Reports

One row per country or place.

| Field | Contract |
|---|---|
| Nation | Exactly one linked Nation |
| Publishing Region | One of the seven regions |
| Research Status | See [Statuses](#research-status-airtable-per-country) |
| Report link | The country Doc's URL, opening the Doc itself (not a copy or a PDF) |
| Indicator 1–4 | yes / no / unclear |
| Indicator 5 | yes / no / unclear / not applicable |
| Status quo ante | negative / neutral / positive |
| Trending direction | negative / neutral / positive |
| Credit line *(new)* | The researchers' names exactly as printed, e.g. "Kristen Tcherneshoff and Jane Doe". Empty means uncredited. The Author(s) select stays for internal tracking |
| Archive Nation ID *(new, synced)* | The Nation's record ID in the archive base, carried into this base by the Nations sync. Needed because a synced table gives its records new IDs; see [Links](#links-between-reports-and-territories) |

All indicator and rating fields are required for a Completed row.

### Airtable: Changes

*New table.* One row per change to a report after its region is released. It is the "why" in the public changelog; the release works out the "what" and the version number itself.

| Field | Contract |
|---|---|
| Report | One linked Reports row |
| Date | The day the change was made |
| Note | One or two plain sentences for readers, e.g. "Corrected the year of the Pashto Society's founding; added a 2026 source on Uzbek signage." |

A row is needed only for reports that are already Released. Changes before release are not logged.

### Airtable: Publishing Docs

*New table (created in the sandbox 2026-11-22).* One row per region, plus one for the front matter.

| Field | Contract |
|---|---|
| Name | The region's name, or "Front matter" |
| Kind | Region / Front matter |
| Publishing Region | For a Region row: the same option as in Reports |
| Doc link | The region overview Doc, or the front-matter Doc |
| Release date | For a Region row: the public release date. From that day, the region's reports count as published: a change needs a Changes row and raises the version |

### Country Doc

The current research template (v10.2026), as the Docs are already written. A dry run over all 171 Docs on 2026-11-22 found 169 that pass; the rules below describe them, so nothing needs reformatting.

1. **Title**, in the Title style: *The State of Language Rights in {Country}*.
2. **Six sections, as Heading 1, in this order, with these exact names:** Language inventory · Historical context · Current political context · Language rights dimensions · Overall Rating · Testimonies.
3. **Under Language rights dimensions**, the five questions as their own paragraphs, with the template's exact wording (any style; earlier wordings are accepted too). Under each:
   - one line `Your answer: Yes` (or No, Unclear, Not applicable), with no full stop;
   - the summary paragraphs;
   - the sources, after the summary: a paragraph of links only, one per line.
4. **Under Overall Rating:**
   - `Was the status quo ante negative, neutral, or positive? Your answer: Positive` (the answer may also go on the next line);
   - `… are politics trending negatively, neutrally, or positively for language rights? Your answer: Negative`;
   - **one bold sentence**: the headline shown in "At a glance" and in search results;
   - the explanation, then its sources.
5. **Testimonies** may be empty. The page then leaves the section out.
6. **Sources** are plain URLs or linked text. Each section's sources are shown collapsed beneath it.
7. **Language codes** in the inventory are written as `Name (iso)`, e.g. `Dari (prs)`. The site links each code to its language page.
8. **Suggestions and comments** are ignored: the exporter reads the Doc as if every open suggestion were rejected. Accept or reject them before locking.
9. **Answers must match Airtable.** If the Doc says `Your answer: Yes` and Airtable says `unclear`, the release fails.

### The WordPress tab

An optional second tab in the country Doc, named **WordPress** (any capitalization), for text that appears only on the site and should stay out of the volunteers' report. Today it holds one thing:

- **Story card.** The country's name as Heading 1 (optional), then a Heading 2 title (e.g. *A revised alphabet for Pashayi*), one paragraph of 60 words or fewer, then its links. Shown on the country report, and on the region page when the region Doc has no evidence cards of its own. A country without one simply has no card.

### Region Doc

*New, one per region,* in that region's folder, named *{Region}: regional overview*, with its link in the region's Publishing Docs row.

| Heading 2 | Content |
|---|---|
| Headline | One sentence, shown as the heading of the region's findings |
| Introduction | Two or three paragraphs, then an optional line of links |
| Findings | One Heading 3 per finding, each with its paragraphs and sources |
| Evidence cards | Optional. One Heading 3 per card, titled with the country's name, then the card's title as one bold line, one paragraph, and its links. When the region Doc has cards, the region page shows only these; without any, it shows each country's WordPress-tab story card |
| Director's letter | Optional. The letter for this region's release, shown at the foot of the region page, ending with the signature line (name, then role) |

### Front-matter Doc

*New, one for the edition,* in `[2] Notes & Templates/`, named *Report front matter*, with its link in the Publishing Docs row "Front matter". Its Heading 2 sections:

- **Summary.** One sentence: the line under the hub and region titles.
- **Methodology.** Opening paragraphs, then one Heading 3 per part (the first is shown as text, the rest as notes).
- **Indicators.** Five Heading 3 entries, each with how it is assessed and an example. The question itself comes from the site, so it can't drift from the country Docs.
- **Answer key.** An opening sentence, then Yes, No, Unclear, Not applicable, each with one sentence.
- **Director's letter.** The edition's letter, on the hub, ending with the signature line (name, then role). Each region's own letter goes in its region Doc.
- **Global synthesis.** Written for the Complete Edition; empty until then. Its text is exported but not shown until the synthesis is Released. Its planned date goes in the Publishing Docs row "Front matter".

### In the release file

The exporter turns the region and front-matter Docs into two optional parts of the release file. Both are written as absolute state: a release without them removes them from the site. The dry run lists them as "Region overview, {region}: create/update/remove" and "Front matter: …".

| Part | Shape | Stored as |
|---|---|---|
| `regions[].overview` | `headline`, `html` (introduction), `sources`, `findings[]` (`title`, `html`, `sources`), `cards[]` (`report_id`, `country`, `title`, `html`, `links`), `letter` (`html`, `name`, `role`) | Term meta `overview` and `overview_hash`. Shown only to visitors who may read the region, like its reports |
| `front_matter` | `summary` (the deck), `methodology` (`html`, `sections[]`), `indicators` (by key: `html`, `example`), `answer_key` (`html`, `answers`), `letter`, `synthesis.release_date` | The option `wt_rights_front_matter`. Public on the hub |

Links are `{url, label}`. Cards name their report by record ID, never by country name. `Editorial` (`class-editorial.php`) normalizes both parts and plans the change.

Until the exporter reads these Docs (the next step), a demo release with mock text can carry them: it is built outside the repo and imported like any release.

## Pre-flight checklist

Run by Kristen, with Frederico, before a region's text lock. Kristen's Claude can run the person-checked items first (see [its brief](#brief-for-kristens-claude)). Each item is either checked by the exporter (✓auto) or by a person.

1. Every country in the region is **Completed** or **Held**. ✓auto
2. Every Completed row has all five indicators, both ratings, and a Report link that opens. ✓auto
3. Every country Doc has the six sections, the five questions, and an answer under each. ✓auto
4. Answers in every Doc match Airtable. ✓auto
5. No open suggestions or comments in any Doc in the region.
6. Every source link opens; dead links are replaced or archived (e.g. web.archive.org).
7. Credit lines are confirmed with each researcher, including anyone who asked to stay anonymous.
8. Story cards are written, or deliberately absent.
9. The region Doc is complete.
10. Every Released report that changed has a Changes row since the last release. ✓auto
11. A preview release is built and read on staging: every country page, the region page, the totals.
12. Sign-off: Kristen confirms by name and date in the release pull request.

Frederico then tags the release, imports it to production as **Embargoed** for press, and switches it to **Released** on launch day.

## Versions and the changelog

Every country report has its own version, shown on its page ("Version 1.1 · updated 3 March 2027") with a list of its changes.

| Part | Lives in | Written by |
|---|---|---|
| Why it changed | Airtable, the [Changes](#airtable-changes) table | Kristen, one sentence |
| What changed, and the version number | The private release repo: the diff between two releases | The exporter, never by hand |
| The public changelog | The country report page in WordPress | Imported with each release; never edited in WordPress |

**How the exporter numbers versions.** It compares each Completed report with the previous release:

| What changed | Version |
|---|---|
| First release of the report | 1.0 |
| Text, sources or story card, with every answer the same | minor: 1.0 → 1.1 |
| Any indicator, status quo ante or trending direction | major: 1.x → 2.0, and the changelog names the answers that changed |
| Nothing | Unchanged |

**The rule that keeps the log honest.** If a Released report's content changed and it has no Changes row dated since the previous release, the release fails and names the country. A Changes row with no matching content change also fails, so a note never describes a change that didn't ship.

The log is cumulative: version 1.2's page lists 1.2 and 1.1, each with its date and note. The release repo keeps every earlier release, so any past version can be rebuilt exactly.

## Correcting a published report

1. Move the country to **Pending review** in Airtable and edit the Doc. The site does not change.
2. Add a row to **Changes**: the report, today's date, and a sentence for readers.
3. Take the country back through Fact checking to **Completed**.
4. Frederico cuts a patch release. The exporter bumps the report's version and adds the note to its changelog; the pull request shows exactly what changed.

## Brief for Kristen's Claude

Kristen's own Claude can do the pre-flight check before anything reaches Frederico. Give it this file's [Editorial contract](#editorial-contract), [Pre-flight checklist](#pre-flight-checklist) and [Versions](#versions-and-the-changelog) sections, read access to the region's Docs and the Airtable base, and this request:

> For the region {Region}, check every country against the editorial contract and the pre-flight checklist. For each country, read its Airtable Reports row and its Google Doc. Report, as a table: the country, its Research Status, and every problem you find (missing or misnamed sections, a missing answer, a Doc answer that differs from Airtable, open suggestions or comments, a source link that doesn't open, a missing story card, a Released report that changed without a Changes row). Don't change anything; list what needs fixing and who should fix it.

It reads and reports; it never edits. The exporter's own checks remain the gate.

## Links between reports and territories

**Maps.** Region and country pages draw their maps with wt-maps (docs/maps.md), from the reports' Archive Nation IDs. A country uses its territory's map. Each region with two or more mapped countries adds a map set through the `wt_maps_extra_sets` filter, so `wp wt-maps export` and `doctor` include it; build it with `node build.mjs --only=<key>` and commit `maps/`. A region or report without IDs shows no map.

- **Report → territory.** Each country report links to its territory page (`/territories/{region}/{territory}/`). Plan 6.8 may turn some non-ISO places (Abkhazia, Transnistria) into subdivisions without pages; their reports then link to the parent territory, or to nothing.
- **Territory → report.** Each territory page with a Released report shows a block linking to it, with the headline sentence and the five answers. An Upcoming or Embargoed report shows nothing.
- **Matched on record IDs, never names.** Names differ between the bases: the report's *Naoero* is the archive's *Nauru*, and *Sahrawi Arab Democratic Republic* is the territory *Western Sahara*. The territory page carries the archive Nation's record ID in `_airtable_record_id`; the rights base's synced Nations get new IDs (Afghanistan is `recUTYgBN887C9IGv` there and `recrY5jg8GmprukMo` in the archive). So the archive's Nations table gets a formula field, `RECORD_ID()`, which syncs into the rights base as **Archive Nation ID**. A report without one, or whose ID matches no territory, has no territory link, no block and no map. Adding the ID later updates the report on the next import, even when its text is unchanged.

## Analytics

The code only pushes to `window.dataLayer`; Google Tag Manager forwards to GA4, as everywhere on the site.

| Data | Pushed | Where |
|---|---|---|
| `rights_page` (project, hub, region, report), `rights_region`, `rights_country`, `report_version`, `release_status`, `viewer_role` (press, admin or empty) | On report pages, before GTM loads | `wt-rights/includes/analytics.php`, through the `wt_head_before_analytics` action in `modules/page--head.php` |
| `rights_updates_signup` with `rights_region` (`synthesis` for the Complete Edition card, `project` for the project page hero) | "Get release updates" clicked; it then opens the Mailchimp signup dialog | `wt-rights/assets/rights.js` |
| `rights_updates_subscribed` | Mailchimp accepted the address (the dialog submits in the page, through Mailchimp's JSONP endpoint) | `wt-rights/assets/rights.js` |
| `rights_paper_click` with `cta_location` (`project`, `schedule`) | "Read the white paper" clicked | `wt-rights/assets/rights.js` |
| `donate_cta_click` | Donate links, by `data-cta-location`: `rights-report`, `rights-report-sidebar`, `rights-region`, `rights-hub`, `rights-project`. They go to `WT_RIGHTS_DONATE_URL` when set, else `/donate/` | The site's existing donate tracking |
| The gateway's four funnel events | PDF downloads, with `post_type=rights_reports` and `download_source=rights-report` | The download gateway |

**To set up in GTM and GA4 (once):**

1. `content_type` RegEx Table: add `^/language-rights/report/` → `language_rights_report`, and `^/language-rights/?$` → `language_rights`. `rights_page` tells hub, region and report apart.
2. Data-layer variables for the six page details; add them as parameters on the GA4 page_view.
3. Custom Event triggers for `rights_updates_signup` (with `rights_region`), `rights_updates_subscribed` and `rights_paper_click` (with `cta_location`), each with a GA4 event tag.
4. Check that the donate trigger fires on the five new `data-cta-location` values.
5. Register `rights_page`, `rights_region`, `rights_country`, `report_version`, `release_status` and `viewer_role` as GA4 custom dimensions.
6. Leave out our own and journalists' views: a comparison or report filter excluding `viewer_role` press and admin.
7. Publish the container, then check every event in GA4 DebugView on staging.

## Staging rehearsal

Run once the build PRs are merged and deployed to staging, before South and Central Asia's text lock.

1. On staging: define `WT_RIGHTS_PRIVATE_DIR` outside `public_html`, flush rewrite rules (Settings → Permalinks), and in Settings → Download Gateway set `rights_reports` to Required with the `documents` form.
2. Locally: `wp wt rights export --region=south-central-asia --release=2027.1 --preview --out=<releases repo>/…` (through 1Password), import it locally, then `wp wt rights pdf --out=<releases repo>/pdf`. A preview export's reports stay drafts at every status, so press and the public never see them: to rehearse the press and public steps, import a copy with `draft` set to false, or the demo release (non-draft reports plus mock region and front-matter text, kept outside the repo).
3. Copy the release file and PDFs to the server's home folder (never `public_html`), then on staging: `import <file> --execute`, `status south-central-asia embargoed --pdfs=<dir>`.
4. Check, as an anonymous visitor, as a press account and as an admin: the hub lists seven regions; the region and reports follow the status table above; no PDF is downloadable.
5. **Releases → Release** (or `status south-central-asia released`): pages public, the PDF downloads through the gateway after the email step, and GA4 DebugView shows the page details, `rights_updates_signup`, the donate click and the download funnel.
6. **Back to upcoming** (or `status south-central-asia upcoming`): everything is gone from public view, report URLs redirect to the region, and the PDFs are gone from the server.

**Staging keeps its release across syncs.** Each sync from production empties the report on staging, since production has no reports until a release makes them public. Release files for staging live in `~/wikitongues-private/staging/releases/`; the symlink `current.json` names the one staging shows, and the sync re-imports it ([staging-sync.md](staging-sync.md#what-the-sync-does)). Regions come back upcoming; re-run any `status` change and recreate press accounts after a sync.

