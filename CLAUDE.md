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
wp-content/plugins/typeahead/                # Search typeahead plugin
tests/                                       # PHPUnit tests (unit layer)
docs/                                        # Architecture docs (airtable-sync.md is authoritative)
```
Do **not** edit: `wp-includes/`, `wp-admin/`, `wp-content/plugins/advanced-custom-fields-pro/`, or any other third-party plugin/theme.

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
`~/public_html/tmp/prod_dump.sql` and keeps **no history** — running it again overwrites
the previous dump. Before a risky production write, SSH in and keep a dated copy first.

Full runbook: `docs/staging-sync.md`.

## Sync prod → local
```bash
bash tool-sync-db-from-prod.sh   # pulls DB + uploads from production
```

## Key architecture docs
- `plan.md` — phased technical roadmap (Phases 1–8); source of truth for what's done vs. in-progress
- `docs/airtable-sync.md` — Make.com / wt-airtable-sync full reference (authoritative)
- `docs/download-gateway.md` — download gateway plugin architecture
- `docs/staging-sync.md` — how to sync staging ↔ production
- `docs/testing-strategy.md` — PHPUnit layer strategy and upgrade path
- `docs/plan-archive.md` — completed phases (reference only)
- `docs/local_docs/` — additional internal specs (security audit, GA4 handoff, gateway brief, Make audit findings, language registry spec)
