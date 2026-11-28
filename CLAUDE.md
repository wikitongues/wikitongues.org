# Wikitongues – Claude Code Context

## Project overview
WordPress site for Wikitongues, a language documentation nonprofit.
- Local: MAMP at `http://localhost:8888/wikitongues` — MySQL on `127.0.0.1:8889` (socket `/Applications/MAMP/tmp/mysql/mysql.sock`)
- Production: shared hosting (SSH host, user, and document root live in the team's private ops notes — not committed)
- Staging: separate WP install on the same host

## Custom code locations — only edit these
```
wp-content/themes/blankslate-child/          # Child theme (PHP templates, functions, CSS, JS)
wp-content/plugins/download-gateway/         # Download gate plugin
wp-content/plugins/wt-airtable-sync/         # Airtable → WordPress sync plugin (Make.com webhooks)
wp-content/plugins/wt-gallery/               # Gallery plugin
wp-content/plugins/wt-maps/                  # Locator maps plugin (shape table, doctor)
wp-content/plugins/wt-rights/                # Language Rights report (Doc parser, releases, import)
wp-content/plugins/typeahead/                # Search typeahead plugin
tests/                                       # PHPUnit tests (unit layer)
docs/                                        # Architecture docs (airtable-sync.md is authoritative)
```
Do **not** edit: `wp-includes/`, `wp-admin/`, `wp-content/plugins/advanced-custom-fields-pro/`, or any other third-party plugin/theme.

## Architectural principles

### Idempotency, always
Every write path — sync endpoints, Make scenarios, WP-CLI commands, migrations, deploy
steps — must give the same result whether it runs once or ten times, and must not depend on
the order its inputs arrive in. Design for the re-run, because syncs retry, Make re-polls,
and migrations run local → staging → production and again after every staging refresh.

- **Key on stable IDs, never on names.** Anything from Airtable is matched by its Airtable
  record ID (`_airtable_record_id` on the post), not by title or display name. Names are
  labels: they get renamed, repeated (`Georgia`) and contain delimiters
  (`Washington,-D.C._US`). Title matching is only a transition fallback, and it logs.
- **Send lists as arrays of IDs**, not joined strings of names.
- **Write absolute state, not deltas.** Set a field to what the source says; never append,
  increment or toggle. Rebuild derived rows from scratch on each write.
- **Upsert, don't insert.** Look up by ID before creating.
- **A second run is a no-op.** Scripts dry-run by default, print what they would change,
  never overwrite a conflicting value silently, and report 0 changes when run again.
- **Make switches order-independent.** When a payload or field changes shape, ship code that
  accepts both the old and new shape first, then change the sender.

Apply this to new code and to code you touch; call out existing violations as follow-ups.

## Working principles — plan before building

Learned on the maps work (Nov 2026): a strong prototype went to production on an unsettled data
model, through five stacked PRs, a fallback patched over a model problem, and plan text too long
to review. These rules come first.

- **Spec before code.** No build on a workstream until the user has approved a one-screen spec in
  `plan.md`. When the model changes mid-build, stop and change the spec first; never patch around
  a model problem in code (a fallback, a special case).
- **One shape for every spec:** Entities · Rules · Example cases · Display · Open questions · Out
  of scope. Tables and one-line rules. No history in a spec; history goes to
  `docs/plan-archive.md`.
- **Example cases are the test.** A handful of real records (Tahitian, Hawaiian, Chamorro…) that
  every rule must answer. The user reviews the table, not the prose.
- **Data before display.** Settle what is true and where it is stored before deciding what a page
  shows. Keep the two in separate sections and separate PRs.
- **Start from the source of truth** (Airtable for archive data) and work outward to Make, then
  WordPress.
- **Name each thing once.** A spec carries a short glossary; each term has one meaning, and the
  same name in Airtable, Make and WordPress wherever possible. Flag a collision before building on
  it.
- **Decision log.** One line per decision: date, decision, why.
- **Write for review.** Concise and didactic: lead with the decision needed, then the options, then
  a recommendation. A prototype is evidence for a spec, not a spec.
- **Small steps.** Ship one reviewable change at a time; don't stack PRs on an unapproved model.

## Dev commands
```bash
composer test          # PHPUnit 9.6 (WP_Mock 1.x)
composer lint          # PHPCS (WordPress-Core standard)
composer lint:fix      # PHPCBF auto-fix
composer analyse       # PHPStan static analysis
```
Run `/test` before every PR — it runs `composer lint`, `composer analyse`, and `composer test` in sequence. **All three are CI gates** (PHPStan included), so don't skip `composer analyse`. CI runs on PHP 8.2.

## Git workflow
- Branch: `type/cc/description` — e.g. `feature/cc/thing`, `fix/cc/bug`
- Always open a PR after pushing; never commit directly to `main`
- PR target: `main`

## PHP / Composer
- CI runs PHP 8.2; local is PHP 8.5
- `config.platform.php` is pinned to `"8.2"` in `composer.json` — keep it there
- Run `composer update` (not `install`) after adding packages

## WordPress CLI
WP-CLI is available as `wp`. Always pass `--allow-root` on localhost. For production, SSH in (see private ops notes) then run WP-CLI from the document root.

## Database changes — sync and back up first
Before any **migration or bulk write** to database state (WP-CLI migrations, scripted
`update_field`/`wp_set_object_terms` runs, ACF changes needing a data migration), refresh
from production so the change is built and tested against real data:

1. **Actions → Backup Prod DB** — dumps production. This is both the pre-change backup
   *and* the source for the two syncs below, so it does double duty. **Check it is still
   enabled first** — GitHub silently disables scheduled workflows after 60 days of repo
   inactivity, and Sync to Staging imports whatever dump was last written, however old.
2. **Sync to Staging** fires automatically (~2–5 min) — staging now matches prod.
3. `bash tool-sync-db-from-prod.sh` — pulls prod → local (DB + uploads).
4. Only then write the migration, and run it **local → staging → production**.

Why: environments drift. A migration validated against a stale local or staging database
proves nothing about production — staging was found 1 person and several renames behind
prod in Sept 2026, so the same command produced different results in each environment.

Doesn't apply to one-off content edits in admin; this is for anything scripted or bulk.

**Backup retention caveat:** `backup-prod-db.yml` always writes the same
`~/backups/prod_dump.sql` and keeps **no history** — running it again overwrites
the previous dump. Before a risky production write, SSH in and keep a dated copy first,
also in `~/backups/`. Never write a dump anywhere under `public_html`: the web server
serves that tree, and this repo is public.

Full runbook: `docs/staging-sync.md`.

## Sync prod → local
```bash
bash tool-sync-db-from-prod.sh   # pulls DB + uploads from production
```

## Key architecture docs
- `plan.md` — the live website plan: an ordered Now/Next queue plus workstream specs. It owns the order of website work; the product roadmap (kept outside the repo) is organization-level strategy
- `docs/plan-archive.md` — completed work: an index of finished initiatives, each linked to its doc, and a dated log
- `docs/airtable-sync.md` — Make.com / wt-airtable-sync full reference (authoritative)
- `docs/download-gateway.md` — download gateway: policies, follow-up forms, webhooks, GA4 events, operations, known gaps
- `docs/content-model.md` — every post type and taxonomy: where it's authored, templates, redirects, known data issues
- `docs/content-styles.md` — the `.wt_prose` module: bare tags only inside it, prose vs layout, type/rhythm/link decisions
- `docs/gallery.md` — wt-gallery parameters, templates, pagination, editorial gallery rows
- `docs/maps.md` — wt-maps: the Natural Earth shape table keyed on record IDs, the curated focus list, `wp wt-maps doctor`
- `docs/maps-claims-guide.md` — how a person manages the claims table (who claims each contested area), and how Claude works on it: draft, rebuild, PR; never merge or decide a political call
- `docs/site-banner.md` — the site banner: the options page, how one banner is chosen (Eastern-time windows), display and events
- `docs/language-rights.md` — the Language Rights report: pages, tagged releases (no live sync), statuses, the editorial contract and pre-flight checklist
- `docs/deployment.md` — release flow, CI checks, what a deploy does, post-deploy steps
- `docs/staging-sync.md` — how to sync staging ↔ production
- `docs/testing-strategy.md` — PHPUnit layer strategy and upgrade path
- `docs/local_docs/` — additional internal specs (security audit, GA4 handoff, gateway brief, Make audit findings, language registry spec). Gitignored; never commit them

When a plan item ships: remove it from `plan.md`, add a log entry and an index row to `docs/plan-archive.md`, and make sure the system it built is described in a `docs/` file. Keep security specifics out of tracked files, because the repo is public.
