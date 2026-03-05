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
