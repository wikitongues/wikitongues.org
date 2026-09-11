# Staging Sync Runbook

Keeps `staging.wikitongues.org` in sync with production data so staging tests are reliable.

---

## How it works

Two GitHub Actions workflows handle the full sync:

| Workflow | File | Trigger |
|---|---|---|
| **Backup Prod DB** | `backup-prod-db.yml` | Every Monday 03:00 UTC, or manual |
| **Sync to Staging** | `sync-prod-to-staging.yml` | Auto after backup, or manual |

**Automated flow (weekly):**
1. Backup runs → dumps production DB to `~/public_html/tmp/prod_dump.sql` on the server
2. Backup workflow fires a `sync-staging` repository dispatch event
3. Sync workflow picks it up → imports dump into staging DB → rsync uploads → URL search-replace → verifies

**The sync workflow does NOT create the dump itself.** It always reads from the most recently written `prod_dump.sql`. If that file is stale (older than a week), run the backup first.

---

## Before changing database state

Run this **before** writing a migration or any bulk/scripted database change — not after.
The point is to develop and test against what production actually holds.

| # | Step | Where |
|---|---|---|
| 1 | **Backup Prod DB** | Actions → Run workflow |
| 2 | **Sync to Staging** (fires automatically) | Actions, ~2–5 min |
| 3 | `bash tool-sync-db-from-prod.sh` | locally |
| 4 | Write the change; run it local → staging → production | |

Step 1 does double duty: the dump it produces is both the pre-change backup of production
and the source both syncs read from. So a single run gets you a backup and two
environments that match prod.

### Why this matters

Environments drift, silently. In September 2026 the People migration was developed against
a local database synced from production weeks earlier, then run on staging — which turned
out to be one person short and carrying older spellings (`Frederico Andrade` vs
`Frederico Afrange de Andrade`). The same command produced different results in each
environment, and the difference only surfaced because the dry-run output was read closely.

A migration validated against a stale copy proves nothing about production.

### Check the backup workflow is still enabled

GitHub **disables scheduled workflows automatically after 60 days without repository
activity**, and resuming activity does *not* re-enable them — someone has to click
**Enable workflow** on the Actions page. This is silent: no run, no failure, no Slack
message. Absence of the weekly `:floppy_disk:` and `:truck:` notifications is the only
signal, and absence is easy to miss.

It has happened once already. **Backup Prod DB last ran 2026-06-22 and was found disabled
on 2026-09-11** — staging had been frozen on June data for almost three months, which
showed up as a person missing and stale names during the People migration.

The dangerous part is the interaction with the sync: **Sync to Staging does not create the
dump, it imports whichever `prod_dump.sql` was last written.** So if the backup has been
disabled, running the sync on its own restores staging from a months-old dump — worse than
the staleness you were trying to fix.

Before relying on a sync:

```bash
gh workflow list --all                                    # is Backup Prod DB active?
gh run list --workflow=backup-prod-db.yml --limit 3       # when did it last succeed?
```

If the last success is not recent, enable the workflow and run the backup **before**
syncing.

### Backup retention — read this before a production write

`backup-prod-db.yml` always writes the same path, `~/public_html/tmp/prod_dump.sql`, and
keeps **no history**. Running it again overwrites the previous dump. That is fine as a
rolling weekly snapshot, but it means:

- If a migration damages production and you then run the backup to "get a copy", you have
  overwritten the last good dump with the damaged state.
- Before any risky production write, SSH in and keep a dated copy first:

```bash
cd ~/public_html
wp db export tmp/prod_dump_$(date +%Y%m%d-%H%M).sql --allow-root
```

Keep it until the change is confirmed good, then delete it — these dumps are large and sit
in the web root's `tmp/`.

### Migrations should survive a sync

A sync from production **wipes whatever the migration wrote** on the target — terms,
field values, page template assignments. This is why data changes belong in idempotent,
dry-run-by-default WP-CLI commands rather than admin clicks: after any sync, re-running
the command restores the state. See `includes/cli/` in the child theme.

---

## Run a sync manually

### Option A — Fresh dump + sync (recommended)

1. Go to **Actions → Backup Prod DB → Run workflow**
2. The sync triggers automatically once the backup completes (~2–5 min)
3. Watch **Actions → Sync to Staging** for completion and Slack `#deploys` for the `:truck:` notification

### Option B — Sync only (reuse existing dump)

Use this when you know the dump is recent enough (e.g. within the same day as a Monday backup).

1. Go to **Actions → Sync to Staging → Run workflow**

---

## What the sync does

1. **Drops views** in the staging DB (prevents import conflicts with DEFINER mismatches)
2. **Imports** `prod_dump.sql` into the staging DB
3. **Verifies** published post count > 0 (fails the job if import was empty)
4. **Rsync uploads** from `~/public_html/wp-content/uploads/` → `~/public_html/staging.wikitongues.org/wp-content/uploads/`
5. **Search-replace** `https://wikitongues.org` → `https://staging.wikitongues.org` (and http variant)
6. **Verifies** `siteurl` and `home` options point to `staging.wikitongues.org`

---

## After a sync

- Staging now has production's DB and uploads
- ACF options (Airtable Link base/table/view IDs, any other options page values) are copied from production — staging will point to the same Airtable tables as production. This is expected.
- Make.com staging scenarios use a separate `wp_base_url` and keychain — they are not affected by the DB sync
- Any staging-specific wp-config.php constants (DB credentials, `WP_SITEURL`, `WT_SYNC_API_KEY`) are set in the staging `wp-config.php` directly on the server — they survive the sync because the sync only touches the DB and uploads, not PHP files

---

## Troubleshooting

**"No published posts found — import likely failed or dump was empty"**
The dump file exists but is empty or corrupt. Run the backup workflow again to regenerate it, then re-run the sync.

**"siteurl still points to prod after search-replace"**
The search-replace step failed or WP-CLI is not available in the staging directory. SSH into the server and run manually:
```bash
cd ~/public_html/staging.wikitongues.org
wp search-replace 'https://wikitongues.org' 'https://staging.wikitongues.org' --skip-columns=guid --allow-root
wp option get siteurl
```

**Staging looks broken after sync (white screen, wrong styles)**
Check that the staging `wp-config.php` has the correct staging DB credentials — the sync overwrites the DB but not the config file. If `DB_NAME`, `DB_USER`, or `DB_PASSWORD` were accidentally overwritten, restore them from the server's config backup.

---

## Frequency

The automated weekly sync runs every Monday at 03:00 UTC. For active feature development requiring up-to-date content (e.g. testing Airtable sync reconciliation, testing search with full language data), trigger a manual sync before starting.
