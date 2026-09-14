# Content Model

Every post type and taxonomy on wikitongues.org: what it holds, where it is authored, how it reaches the page, and what to watch for when changing it.

Field definitions live in `wp-content/themes/blankslate-child/acf-json/` (one JSON file per field group). Registrations live in `includes/taxonomies/`, one file per post type. Redirects live in `includes/template/router.php`.

## Table of Contents

- [Where content is authored](#where-content-is-authored)
- [Post types at a glance](#post-types-at-a-glance)
- [Taxonomies](#taxonomies)
- [The archive: languages, videos, captions, lexicons, resources](#the-archive-languages-videos-captions-lexicons-resources)
- [Territories and regions](#territories-and-regions)
- [Fellows](#fellows)
- [People](#people)
- [Documents and document files](#documents-and-document-files)
- [Financials (Form 990s)](#financials-form-990s)
- [Smaller types](#smaller-types)
- [Editorial content](#editorial-content)
- [Options pages](#options-pages)
- [Search, REST and redirects](#search-rest-and-redirects)
- [Changing the content model safely](#changing-the-content-model-safely)
- [Known data issues](#known-data-issues)
- [History](#history)

---

## Where content is authored

| Source | Post types | How it reaches WordPress |
|---|---|---|
| **Airtable** (the archive base) | `languages`, `videos`, `captions`, `lexicons` | Make.com watches for changed records every 15 minutes and posts them to `wt-airtable-sync`, which maps and writes the fields. **Edit these in Airtable, not WordPress**: the next sync overwrites WordPress edits. See [airtable-sync.md](airtable-sync.md). |
| **One-time imports** | `resources`, `territories` and the `region` taxonomy | Imported once, not synced. Resources came from the retired Make v1 scenarios; territories from a WP-CLI script. Edit in WordPress. |
| **WordPress** | Everything else: `fellows`, `people`, `documents`, `document_files`, `form_990`, `events`, `careers`, `faq`, `partners`, `blog`, pages | Authored in the admin. |

The download gateway's contacts (`wp_gateway_people`) are not content and not part of this model. See [download-gateway.md](download-gateway.md).

---

## Post types at a glance

| Post type | What it is | Public page | Template | REST |
|---|---|---|---|---|
| `languages` | A language profile (8,000+) | `/languages/{iso-code}/` | `single-languages.php` | Yes |
| `videos` | An oral history (1,800+) | `/videos/{slug}/` | `single-videos.php` | Yes |
| `captions` | A caption file for a video | None: redirects to its video | — | Yes |
| `lexicons` | A word list between languages | None: redirects to its source language | — | Yes |
| `resources` | An external resource about a language | None: redirects to its language | — | Yes |
| `territories` | A country or territory | `/territories/{region}/{territory}/` | `single-territories.php` | Yes |
| `fellows` | A Revitalization Fellow | `/fellows/{slug}/` | `single-fellows.php` | Yes |
| `people` | Staff, board, advisors, volunteers | None: redirects to the staff page | Sections of `template-people.php` pages | **No** |
| `documents` | A downloadable resource, such as a toolkit guide | `/documents/{slug}/` | `single-documents.php` | Yes |
| `document_files` | One version of a document (language, format) | None | Downloaded through the gateway | No |
| `form_990` | An annual IRS filing | Listed at `/financials/` | `archive-form_990.php` | **No** |
| `events` | An event | None: redirects to `/events` | `events-page.php` lists them | Yes |
| `careers` | A job or volunteer opening | `/careers/{slug}/` | `single-careers.php` | Yes |
| `faq` | A question and answer | Via the `[faqs]` shortcode | `faq-page.php` | Yes |
| `partners` | A partner organization | None: redirects home | `[partners]` shortcode, `template-about-partners.php` | Yes |
| `blog` | A blog post | `/blog/` and `/blog/{slug}/` | `archive-blog.php`, `single-blog.php` | Yes |

Archive URLs mostly redirect; see [Redirects](#redirects).

---

## Taxonomies

| Taxonomy | Attached to | Holds | Public URLs | Notes |
|---|---|---|---|---|
| `region` | `territories` | Two levels: continents, then sub-regions | `/territories/{region}/` (`taxonomy-region.php`) | Continent pages include their sub-regions' territories |
| `writing-system` | `languages` | Latin, Arabic, Unwritten… | None; used as the `?writing_system=` filter on `/languages/` | See [Known data issues](#known-data-issues): not kept in sync with Airtable |
| `linguistic-genealogy` | `languages` | Language families | None; used as the `?genealogy=` filter | Same issue |
| `fellow-category` | `fellows` | Program categories | `/fellow-category/{term}/` (`taxonomy-fellow-category.php`) | Registered in ACF (`taxonomy_6708065ac1b54.json`), not PHP. The bare `/fellow-category/` redirects to the first term. |
| `people-type` | `people` | Board Member, Staff, Volunteer, Advisor; Donor planned | None, deliberately | Drives page sections; see [People](#people) |
| `career_type` | `careers` | Kinds of opening | `/career-type/{term}/` | |
| `faq_category` | `faq` | FAQ groupings | `/faq-category/{term}/` | |

WordPress's own categories and tags are also registered for `languages`.

---

## The archive: languages, videos, captions, lexicons, resources

These are the documentation archive. The first four are synced from Airtable; the field map is in [airtable-sync.md](airtable-sync.md#field-maps).

### Languages

- **Identity.** A language's `post_title` and slug are its ISO 639-3 code (`nch`); its display name is `standard_name`. A slug that doesn't match the ISO code serves the wrong content: 19 languages did in February 2026 (the `wblu`/`blu` fix in the [archive](plan-archive.md)). Other identifiers: `iso_code`, `glottocode`, `autonym`, `alternate_names`, `egids_status`, `olac_url`, `wikipedia_url`, `wikipedia_editions`, `wikipedia_description`.
- **Geography.** `nations_of_origin` is legacy comma-separated text, matched with `LIKE` (#241). The structured links are territory relationships: each territory's `languages` and `official_languages` fields, and the language's own `territories` field.
- **Classification.** The `writing-system` and `linguistic-genealogy` taxonomies are what templates read. The legacy text fields `writing_systems` and `linguistic_genealogy` are what Airtable still writes.
- **Related content.** `speakers_recorded` (videos), `lexicon_source` and `lexicon_target` (lexicons), `external_resources` (resources). Counts are cached in `{field}_count` post meta for sorting the admin list.
- **Page.** `single-languages.php`: the sidebar, then galleries of videos, fellows, lexicons, resources and "other languages from" each territory the language is linked to.

### Videos

- **Fields.** `featured_languages` (languages), `video_title` / `video_custom_title`, `video_description`, `video_license` / `license_link`, `youtube_id` / `youtube_link` / `youtube_publish_date` (the page embeds YouTube), `video_thumbnail_v2` (an attachment Make uploads), `metadata` (a group).
- **Status.** `public_status` is Public, Processing or Private. Only Public videos show file downloads; the other two show a plain message.
- **Downloads.** `dropbox_link` and `wikimedia_commons_link`, both routed through the [download gateway](download-gateway.md).
- **Captions.** The page lists captions whose `source_video` points at it.
- **Editorial block.** Every video page shows one shared editorial block, configured under Videos → Video Settings (#621).

### Captions, lexicons, resources

| Type | Key fields | Notes |
|---|---|---|
| `captions` | `source_video`, `source_language`, `creator`, `file_url` (Dropbox) | Downloadable through the gateway. `source_video` is stored serialized, so queries match it with `LIKE '"{id}"'` (#562). |
| `lexicons` | `source_languages`, `target_languages`, `dropbox_link`, `external_link` | Shown as galleries on language pages. Only 22 of 152 Airtable lexicons had WordPress posts in March 2026; the rest appear as their records are next edited. |
| `resources` | `resource_title`, `resource_url`, `resource_language`, `resource_description`, `moderation_status` | **Not synced.** WordPress holds about 907 posts against 204 Airtable records; reconcile before syncing (plan: Data quality). |

---

## Territories and regions

Territories arrived in February 2026 (#445, #491) and give the archive a geographic entry point.

- **URLs.** `/territories/` lists all territories (`archive-territories.php`, filterable with `?region=`). `/territories/{region}/` is a region page, and `/territories/{region}/{territory}/` a territory. Custom rewrite rules in `territories.php` build these, and the permalink uses the territory's first `region` term.
- **Fields.** `endonym`, `languages`, `official_languages`, `wikidata_id`, `region`.
- **Source.** A one-time WP-CLI import (`temp/territories/import-territories.php`, not committed) built the continent → sub-region hierarchy and the territories. It is not synced from Airtable.
- **Pages.** Territory and region pages show a fellows gallery and a languages gallery, each linking to a filtered archive. Continent pages aggregate every sub-region.
- **Names.** `wt_prefix_the()` adds "the" where English needs it ("the Bahamas", "the Americas").
- **Performance.** Territory pages read `get_field( 'languages', $id, false )` (raw IDs), because some territories list hundreds of languages (India: 403). Continent fellows queries are the known hot spot (#533).

---

## Fellows

- **Fields.** `first_name`, `last_name`, `fellow_year` (the cohort), `fellow_language` and `fellow_language_preferred_name`, `fellow_category` (the `fellow-category` taxonomy), `fellow_territory` (one or more territories), `fellow_location`, `fellow_banner` (a group, which also holds the headshot), `fellow_testimonial`, `testimonial_link_back`, `marketing_text`, plus the shared **Global: Social Links** group.
- **Pages.** `single-fellows.php` renders the fellow's own editorial content, plus shared "about the fellowship" copy from the **Global: Fellowship** options page. The archive redirects to `/revitalization/fellows` unless filtered by `?territory=` or `?region=`.
- **Category pages.** `taxonomy-fellow-category.php` renders the term's own banner fields, then the fellows gallery, then the term's editorial content (#620). `wp wt migrate-fellow-category-banner` moved existing banners into that shape.
- **Territory links.** `fellow_territory` makes fellows show up on territory and region pages.

---

## People

**One record per human, typed by taxonomy.** The `people` post type (renamed from `team` on 2026-09-09, #622) is the organization's registry of the people behind it. A person holds one or more `people-type` terms (Board Member, Staff, Volunteer, Advisor), so a board member who also donates stays one record.

- **Fields.** `people_type` (a picker for the taxonomy), `leadership_title`, `contributor_location`, `languages`, `profile_picture`, `bio`, plus **Global: Social Links** (email, website and six social platforms).
- **Reading types.** Use `get_the_terms( $id, 'people-type' )`. **`get_field( 'people_type' )` returns null by design:** the ACF field runs with `load_terms` and `save_terms` on, and in that mode ACF drops its own meta so the taxonomy stays the only source of truth.
- **Pages.** The Board, Advisors and Staff pages share `template-people.php`. Each people section is a `gallery_layout` row in the page's editorial content, either filtered by type or hand-picked, and rendered wide or as a grid. A new people page needs no deploy. The banner comes from the page's `people_banner` field. The Partners page is separate, and still a draft.
- **Locked down.** `show_in_rest => false`, `exclude_from_search => true`, `has_archive => false`, and `people-type` is non-public. Singles redirect to `/about/staff-and-volunteers/`. Before this, `/wp-json/wp/v2/team` publicly listed all 52 people. Keep it this way before donor records exist.
- **Donors (next).** Donors will be a `people-type` term. The work needs a per-person flag that keeps a donor off the Donors page while still counting them ("N anonymous supporters"). This replaces the separate Donors post type the roadmap once specified. See [plan.md](../plan.md).
- **Not gateway contacts.** Download contacts live in `wp_gateway_people`, a different thing. A donor might plausibly appear in both.
- **Modelling limits.** `leadership_title` holds the role at Wikitongues for staff and board but the outside affiliation for advisors, and there is only one per person. Board order is alphabetical unless that section's *Order By* is set to *Selection order*.
- **Migration.** `wp wt migrate-people` (dry run by default; `--execute` to write) retyped the posts, derived types from the old page lists, and seeded each page's sections. `/team` and `/team/*` redirect to the staff page. The old list fields (`board_members`, `staff_members`, `interns_and_volunteers`, `team_banner_*`) remain on the three pages until swept.

---

## Documents and document files

A **document** is a resource page; **document files** are its downloadable versions.

| | `documents` | `document_files` |
|---|---|---|
| Is | The resource: title, thumbnail, editorial content, CTA text | One version: a language and format of the file |
| Fields | `selected_file` (the default version), `file_cta` | `parent_download` (its document), `version`, `version_date`, `language` (a language post), `format`, `file` |
| Public | `/documents/{slug}/`; the archive redirects to `/revitalization` | Not public; downloaded through the gateway |

- **The page.** `single-documents.php` shows the description, a banner with a download button for the selected file, a versions table that visitors can filter by language, and an invitation to help translate (a `mailto:` link to hello@wikitongues.org, #606).
- **Downloads.** Every download goes through the gateway (#598). Banners and editorial card blocks that point at a document download its `selected_file` (see [download-gateway.md](download-gateway.md#where-download-links-appear)).
- **Admin.** The `selected_file` picker only offers the document's own files. The document files list has sortable version, language and format columns.

---

## Financials (Form 990s)

`form_990` replaced the monthly `reports` post type on 2026-09-11 (#630).

- **Entries.** An admin adds one per filing: a **title**, which is the link text (for example "2023 Form 990"), the `tax_year`, which sets the order, and the PDF (`form_990_file`). Use the public-disclosure copy, with Schedule B contributor names and addresses removed.
- **Page.** `/financials/` (the post type's archive) lists every filing, newest tax year first, each linking straight to its PDF with the file size. The footer links to it beside the Candid seal.
- **Hidden elsewhere.** Entries have no page of their own (singles redirect to the archive). The type is not `public`, so it stays out of search, sitemaps and menus, and it has no REST route.
- **Not gated.** Filings link to the media library directly, not through the download gateway (decided 2026-09-11).
- **Raw meta.** The template reads `tax_year` and `form_990_file` with `get_post_meta()` rather than `get_field()`, so it renders even where ACF's reference meta is missing.
- **Redirects and leftovers.** `/reports` and `/reports/*` redirect to `/financials`. The eight old report posts, their attachments, and the database copy of their ACF group remain until deleted.

---

## Smaller types

| Type | Fields | Notes |
|---|---|---|
| `events` | `event_datetime`, `event_timezone`, `event_location`, `event_description`, `event_registration_link` | `events-page.php` lists upcoming and past events; singles redirect there |
| `careers` | `location`, `deadline`, `team_description`, `role_description`, `requirements`, `compensation`, `application` | Listed by galleries on `template-careers.php` and the staff page; typed by `career_type` |
| `faq` | Post content | Grouped by `faq_category`; shown by the `[faqs]` shortcode and `faq-page.php` sections |
| `partners` | `partner_logo`, `partner_website`, `partner_email`, `partner_bio`, `partner_type` | Shown by `[partners]` and `template-about-partners.php`; no public singles |
| `blog` | Post content | `/blog/`; most writing still lives on Medium and Substack |

---

## Editorial content

Most pages are composed in the admin from one flexible-content field, `main_content`, defined in **Global: Editorial page** (`group_678fbe627d614.json`) and rendered by `modules/editorial-content.php`.

| Layout | Renders |
|---|---|
| `text_layout` | Rich text |
| `banner_layout` | A page banner |
| `gallery_layout` | A gallery of any post type, filtered or hand-picked (see [gallery.md](gallery.md)) |
| `video_layout` | An embedded video |
| `testimonials_layout` | A testimonial carousel |
| `link_group_layout` | A group of links |
| `block_layout` | A card or navigation block. Its link type Download (and the secondary button on document links) downloads a document through the gateway. |

**Where it's available:** default pages, `template-editorial.php`, the revitalization home, fellows and toolkit templates, `template-archive-success.php`, `template-people.php`, fellows and documents singles, fellow-category terms, and the Video Settings options page. `editorial-content.php` reads the current page by default; a caller can set `$editorial_source` to render another source, as video singles do with the options page.

---

## Options pages

| Options page | Holds |
|---|---|
| General Options | Logos, address and contact details, the site-wide alert banner, and the analytics header script (Google Tag Manager) |
| Newsletter | The Mailchimp form settings and copy |
| Custom Search | Settings from an earlier Airtable-backed search |
| Airtable Links | The base, table and view IDs behind the "View in Airtable" button on synced posts |
| Global: Fellowship | The "about the fellowship" copy shown on fellow pages |
| Global: Video Settings | The shared editorial block on video pages |
| Settings → Download Gateway | Gate policies, follow-up forms, the webhook URL and retention ([download-gateway.md](download-gateway.md)). Not ACF |

Options live in the database, so each environment has its own values, and a production → staging sync overwrites staging's.

---

## Search, REST and redirects

### Search

- **Site search** (`?s=`, `search.php`) is limited to languages and videos (`includes/template/search-filter.php`). It matches WordPress titles and content, which means a language, whose title is its ISO code, isn't found by its name (#379).
- **The typeahead** (`GET /wp-json/custom/v1/search`) searches languages by `standard_name` and `alternate_names`.
- People, Form 990s and document files never appear in search.

### REST

Public post types are in the WordPress REST API except `people`, `form_990` and `document_files`. Content doesn't arrive through core REST: Airtable data comes through the sync endpoint (`/wikitongues/v1/sync/{post_type}`), and only video thumbnails are uploaded through `/wp/v2/media`.

### Redirects

From `includes/template/router.php`:

| Request | Goes to |
|---|---|
| Archives of `languages`, `videos`, `lexicons`, `resources`, `captions` | `/archive`, unless a supported filter is present (`?territory=`, `?genealogy=`, `?writing_system=` on languages; `?language=` on videos) |
| The `fellows` archive | `/revitalization/fellows`, unless filtered by `?territory=` or `?region=` |
| The `documents` archive | `/revitalization` |
| The `partners` archive and singles | Home |
| A caption, lexicon or resource | Its video or language, or `/archive` if that isn't published |
| An event | `/events` |
| A Form 990 | `/financials` |
| A person | `/about/staff-and-volunteers/` |
| `/fellow-category/` | The first fellow category |
| `/team`, `/team/*` | `/about/staff-and-volunteers/` (301) |
| `/reports`, `/reports/*` | `/financials` (301) |
| `/2024-fundraiser` | `/donate` (301) |

---

## Changing the content model safely

- **ACF field groups** are defined in `acf-json/` but also live in the database. After editing a JSON file by hand, bump its `modified` timestamp so the admin offers to sync. Deleting a JSON file doesn't retire a group; trash it in ACF → Field Groups too.
- **Don't regroup existing fields.** Wrapping existing fields in an ACF group changes their meta keys, so values already stored stop resolving without a data migration. To share a definition across groups, use a location rule, as **Global: Social Links** does, or a Clone field that keeps the original keys.
- **Script data changes.** Put them in idempotent WP-CLI commands under `includes/cli/`, dry run by default (`wp wt …`). Refresh from production first, then run them local → staging → production (see [staging-sync.md](staging-sync.md)). A staging sync wipes whatever a migration wrote there, and re-running the command restores it.
- **Flush rewrite rules after a URL change.** Adding a post type, archive or slug changes URLs, and deploys don't flush rewrite rules. Open Settings → Permalinks, or run `wp rewrite flush`, on each environment ([deployment.md](deployment.md)).
- **Check pages before removing a template.** Deploys delete files removed from the repo, so a page still assigned to a deleted template falls back to the default layout.
- **New synced fields** go in `wt-airtable-sync/config/field-maps.php` and in the Make scenario's request body.
- **New downloadable types** inherit the gateway's site-wide policy, which is Required on production ([download-gateway.md](download-gateway.md#adding-a-downloadable-content-type)).
- **Multi-value post objects.** ACF stores these serialized (`a:2:{i:0;s:2:"42";…}`), so a meta query must match `LIKE '"42"'` rather than compare numbers.

---

## Known data issues

Tracked in [plan.md](../plan.md) under *Data quality & Airtable* unless noted.

| Issue | Detail |
|---|---|
| Writing systems and genealogies drift | The sync writes the legacy text fields (`writing_systems`, `linguistic_genealogy`), not the taxonomies templates read. Terms reflect the February 2026 migration only, and languages created since have none. |
| `nations_of_origin` is comma-separated text | `LIKE` matching breaks on combined values: "South Korea, North Korea" hides South Korea's languages (#241). Planned migration to territory relationships. |
| Names | Some languages lack a standard name (#53); some use the comma form "Gondi, Southern" (#54). |
| Caption file IDs | Caption file IDs join languages with `,` instead of `+` (#72). |
| Resources diverged | About 907 WordPress posts against 204 Airtable records; not synced. |
| Record gaps | As of March 2026: 2 languages, about 3 videos, 60 captions and 130 lexicons in Airtable had no WordPress post. Each closes when its record is next edited. |
| Continent fellows query | One `LIKE` clause per territory: Asia has 215 (#533). |

---

## History

| When | Change | PRs |
|---|---|---|
| 2026-02 | Territories post type, `region` taxonomy, archive, "the" prefix, fellows ↔ territories | #445, #446, #450–455, #491 |
| 2026-02 | `writing-system` and `linguistic-genealogy` taxonomies replace text filters | #467, #471 |
| 2026-03 | Fellows ACF audit removed four unused fields | #541 |
| 2026-03 | Post type registrations moved to `includes/taxonomies/` and made consistent | #529 |
| 2026-09 | Shared **Global: Social Links** group | #616 |
| 2026-09 | Fellow-category banners from the term's own fields | #620 |
| 2026-09 | Shared editorial block on video pages | #621 |
| 2026-09 | `team` → `people`, typed by `people-type` | #622 |
| 2026-09 | `reports` → `form_990` and `/financials` | #630 |

The full record is in [plan-archive.md](plan-archive.md).
