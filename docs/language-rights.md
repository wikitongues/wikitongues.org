# Language Rights report

How the *Report on the Global State of Language Rights* reaches wikitongues.org: its pages, its sources, how a release is cut, and the editorial contract the research team works to. The spec, phases and decision log are plan item [2.2](../plan.md#22-language-rights-report). Nothing here is built yet; this file describes the agreed design and becomes the reference as it ships.

## Table of Contents

- [Pages](#pages)
- [Sources](#sources)
- [Releases, not a live sync](#releases-not-a-live-sync)
- [Statuses](#statuses)
- [Editorial contract](#editorial-contract)
  - [Airtable: Reports](#airtable-reports)
  - [Airtable: Treaty actions](#airtable-treaty-actions)
  - [Airtable: Changes](#airtable-changes)
  - [Country Doc](#country-doc)
  - [The WordPress tab](#the-wordpress-tab)
  - [Region Doc](#region-doc)
  - [Front-matter Doc](#front-matter-doc)
- [Pre-flight checklist](#pre-flight-checklist)
- [Versions and the changelog](#versions-and-the-changelog)
- [Correcting a published report](#correcting-a-published-report)
- [Brief for Kristen's Claude](#brief-for-kristens-claude)
- [Links between reports and territories](#links-between-reports-and-territories)

---

## Pages

| Page | URL | Built from |
|---|---|---|
| Project | `/language-rights/` | The existing editorial page (`template-editorial.php`) |
| Report hub, and the global synthesis from the Complete Edition | `/language-rights/report/` | Front-matter Doc, released regions, and the synthesis once released |
| Region (7) | `/language-rights/report/{region}/`, e.g. `/language-rights/report/south-central-asia/` | Region Doc, plus totals computed from its country reports |
| Country report (one per Reports row) | `/language-rights/report/{country}/`, e.g. `/language-rights/report/afghanistan/` | Country Doc and its Airtable row |
| Short link | `/rights` | Redirects to `/language-rights/report/`, like `/ai` |

A country's URL leaves out its region: regions are publishing groups a later edition may regroup, and an indexed URL should never move. Region and country slugs share one namespace, so the import refuses a country slug that equals a region slug.

## Sources

| Source | Holds | Owner |
|---|---|---|
| Airtable, *Language Rights Reports* base | One row per country: indicators, overall rating, region, status, Doc link, credit; treaties and treaty actions | Kristen |
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
wp wt rights-import <file>   dry run by default → local → staging → production
```

- **The private repo.** Release files hold embargoed text, so they never enter this public repo. The exporter's code can live here.
- **Release numbers.** `2027.1`–`2027.7` are the regional releases in publication order; `2027.8` is the Complete Edition; a release carrying only corrections is a patch (`2027.1.1`). Release numbers track deploys and are internal. Readers see each report's own [version](#versions-and-the-changelog).
- **A release is cumulative.** `2027.2` holds South and Central Asia *and* Europe, so any one file rebuilds the whole report on an empty site.
- **Import is idempotent.** It matches each country by its Reports record ID, writes absolute state, and reports 0 changes when run twice. Countries are never deleted by an import; a country dropped from a release is unpublished and logged.
- **Preview.** The exporter can also run nightly against live content and import to staging only, so work in progress can be read in place without production changing.

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
| Upcoming | Release date, nation list, "Get release updates" | The same |
| Embargoed | The same as Upcoming | The full region and its country reports, not indexed |
| Released | Everything | Everything |

The hub's global totals count **Released** regions only, so no unreleased finding leaks into an aggregate. The global synthesis has its own status and stays Upcoming until the Complete Edition.

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

### Airtable: Treaty actions

*New table.* The treaty register on region pages needs what each country did and when; today the base only links treaties to nations.

| Field | Contract |
|---|---|
| Nation | One linked Nation |
| Treaty | One linked Language Rights Treaty |
| Action | signature / ratification / accession / succession |
| Date | The date of the action |
| Reservations or declarations | Short text, or empty |
| Source | URL of the depositary record (e.g. the UN Treaty Collection) |

One row per action. A country that signed and later ratified has two rows.

### Airtable: Changes

*New table.* One row per change to a report after its region is released. It is the "why" in the public changelog; the release works out the "what" and the version number itself.

| Field | Contract |
|---|---|
| Report | One linked Reports row |
| Date | The day the change was made |
| Note | One or two plain sentences for readers, e.g. "Corrected the year of the Pashto Society's founding; added a 2026 source on Uzbek signage." |

A row is needed only for reports that are already Released. Changes before release are not logged.

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

An optional second tab in the country Doc, named exactly **WordPress**, for text that appears only on the site and should stay out of the volunteers' report. Today it holds one thing:

- **Story card.** The country's name as Heading 1 (optional), then a Heading 2 title (e.g. *A revised alphabet for Pashayi*), one paragraph of 60 words or fewer, then its links. Shown on the region page and the country report. A country without one simply has no card.

### Region Doc

*New, one per region,* in that region's folder, named *{Region}: regional overview*.

| Heading 2 | Content |
|---|---|
| Headline | One sentence, shown at the top of the region page |
| Introduction | Two or three paragraphs |
| Findings | One Heading 3 per finding, each with its paragraphs and sources |
| Evidence cards | Optional. One Heading 3 per card, titled with the country's name, then one paragraph and sources. Use this for region-specific cards; a country's WordPress-tab story card is pulled in automatically |

### Front-matter Doc

*New, one for the edition,* in `[2] Notes & Templates/`, named *Report front matter*. Its Heading 2 sections:

- **Methodology.**
- **Indicators.** Five Heading 3 entries, each with the question, how it is assessed, and an example.
- **Answer key.** Yes, No, Unclear, Not applicable, each with one sentence.
- **Director's letter.**
- **Global synthesis.** Written for the Complete Edition; empty until then. Its text is exported but not shown until the synthesis is Released.

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
10. Treaty actions are entered for every country in the region.
11. Every Released report that changed has a Changes row since the last release. ✓auto
12. A preview release is built and read on staging: every country page, the region page, the totals.
13. Sign-off: Kristen confirms by name and date in the release pull request.

Frederico then tags the release, imports it to production as **Embargoed** for press, and switches it to **Released** on launch day.

## Versions and the changelog

Every country report has its own version, shown on its page ("Version 1.1 · updated 3 March 2027") with a list of its changes. Region pages and the hub show the date of their last change.

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

> For the region {Region}, check every country against the editorial contract and the pre-flight checklist. For each country, read its Airtable Reports row and its Google Doc. Report, as a table: the country, its Research Status, and every problem you find (missing or misnamed sections, a missing answer, a Doc answer that differs from Airtable, open suggestions or comments, a source link that doesn't open, a missing story card, a missing treaty action, a Released report that changed without a Changes row). Don't change anything; list what needs fixing and who should fix it.

It reads and reports; it never edits. The exporter's own checks remain the gate.

## Links between reports and territories

- **Report → territory.** Each country report links to its territory page (`/territories/{region}/{territory}/`). Plan 6.8 may turn some non-ISO places (Abkhazia, Transnistria) into subdivisions without pages; their reports then link to the parent territory, or to nothing.
- **Territory → report.** Each territory page with a Released report shows a block linking to it, with the headline sentence and the five answers. An Upcoming or Embargoed report shows nothing.
- **Matched on record IDs, never names.** Names differ between the bases: the report's *Naoero* is the archive's *Nauru*, and *Sahrawi Arab Democratic Republic* is the territory *Western Sahara*. The territory page carries the archive Nation's record ID in `_airtable_record_id`; the rights base's synced Nations get new IDs (Afghanistan is `recUTYgBN887C9IGv` there and `recrY5jg8GmprukMo` in the archive). So the archive's Nations table gets a formula field, `RECORD_ID()`, which syncs into the rights base as **Archive Nation ID**. A report whose ID matches no territory is listed by the import and shows no block.
