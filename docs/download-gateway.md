# Wikitongues Download Gateway

## Table of Contents

- [Overview](#overview)
- [Content Manager Guide](#content-manager-guide)
  - [Setting download policies](#setting-download-policies)
  - [Adding download links to pages](#adding-download-links-to-pages)
  - [Intake forms (step 2)](#intake-forms-step-2)
  - [What data is collected](#what-data-is-collected)
- [Dropbox Integration](#dropbox-integration)
  - [How it works (non-technical)](#how-it-works-non-technical)
  - [Setup (technical)](#setup-technical)
  - [How it works (technical)](#how-it-works-technical)
  - [Troubleshooting Dropbox](#troubleshooting-dropbox)
- [Developer Guide](#developer-guide)
  - [Registering intake field sets](#registering-intake-field-sets)
  - [Adding a new downloadable content type](#adding-a-new-downloadable-content-type-fileresolver)
- [System Architecture (technical)](#system-architecture-technical)
  - [Request flow](#request-flow)
  - [REST endpoints](#rest-endpoints)
  - [Policy system](#policy-system)
  - [Database tables](#database-tables)
- [Operations & Maintenance](#operations--maintenance)
  - [Enabling / disabling the gateway](#enabling--disabling-the-gateway)
  - [Data retention](#data-retention)
  - [Viewing download data](#viewing-download-data)
  - [Common problems](#common-problems)
  - [Logs](#logs)
  - [Uninstalling](#uninstalling)

---

## Overview

The Download Gateway controls who can download videos, captions, and documents from the Wikitongues archive. It logs every download, optionally collects visitor contact information before releasing a file, and routes downloads through short-lived secure links so that URLs cannot be shared indefinitely.

**Two modes of operation:**

- **Open** — file downloads with one click, no form shown. Useful for publicly-accessible resources.
- **Gated** — a modal prompt appears before the file is released. Visitors either provide their name and email ("hard gate") or can skip the prompt ("soft gate"). Either way, their download is logged.

**Why it exists:**

- Attribution — understanding who uses the archive
- Grant reporting — quantifiable download metrics per content type
- Relationship building — opt-in contact details for follow-up
- Access control — prevents permanent, shareable Dropbox links from circulating

---

## Content Manager Guide

### Setting download policies

**Where:** WordPress admin → Settings → Download Gateway

Policies control what a visitor sees when they click a download link. There are three levels; the most specific one wins:

1. **Per-file override** — set directly on the individual post edit screen (via the "Download Policy" metabox)
2. **Per content type default** — set in the settings page for Videos, Captions, and Documents separately
3. **Site-wide default** — the fallback for all content types

**Policy values:**

| Value | What happens |
|-------|-------------|
| **None** | File downloads with one click, no gate shown |
| **Soft** | Gate prompt shown, but visitor can click "Skip" to proceed without providing details |
| **Hard** | Visitor must enter their email address before the file is released |
| **Disabled** | Download link is hidden entirely on all pages — useful for embargoed content |
| **Inherit** | Falls through to the next tier (per-CPT → global) |

> **Note on soft gate:** The soft gate is a courtesy prompt, not an access barrier. A visitor who clicks "Skip" — or who accesses the download URL directly — will still receive the file. Use "Hard" if form submission is a requirement.

**Walkthrough:**

1. Go to Settings → Download Gateway
2. The "Gate policy" section shows a "Global default" selector and one row per content type (Videos, Captions, Documents)
3. Change a value and click "Save settings"
4. To override a single post, open the post in the editor — the "Download Gateway" metabox in the sidebar shows a per-resource policy selector

---

### Adding download links to pages

Use the `[gateway_download]` shortcode anywhere in post/page content or ACF text fields:

```
[gateway_download id="42"]
[gateway_download id="42" label="Download transcript"]
```

**Attributes:**

| Attribute | Required | Default | Description |
|-----------|----------|---------|-------------|
| `id` | Yes | — | Post ID of the downloadable resource |
| `label` | No | "Download" | Link text shown to the visitor |

**Finding the post ID:** Open the post in the WordPress editor. The URL in your browser will contain `post=123` — that number is the post ID.

**Examples:**

```
[gateway_download id="1234"]
[gateway_download id="1234" label="Download video"]
[gateway_download id="5678" label="Get caption file (SRT)"]
```

**Edge cases:**

- If the post ID is missing or zero, an HTML comment is rendered and no link is shown
- If the post's policy is "Disabled", nothing is rendered (no link, no comment)
- If the post does not exist, the download endpoint returns 404
- If the gateway is disabled site-wide (`GATEWAY_ENABLED = false`), links still render but clicking them returns a 404 — the gate does not fire

---

### Intake forms (step 2)

Intake forms are an optional second step in the download modal, shown after the visitor submits or skips the gate form. They collect supplementary information — for example, how the visitor intends to use a resource.

**Key behaviours:**

- Intake is **non-blocking**: the download proceeds regardless of whether the visitor fills in the form or dismisses it. A failed submission does not prevent the file from being released.
- Intake only appears if a field set has been configured for the content type being downloaded. If no fields are registered, the modal goes straight to the download.
- Intake is shown once per gate interaction. Within the same browser session, repeat downloads (passthrough) skip the gate and the intake step.

**Configuring intake forms:**

Intake fields are registered by a developer via the `gateway_intake_fields` WordPress filter — see [Registering intake field sets](#registering-intake-field-sets). There is currently no admin UI for this; a code change is required.

To check whether intake forms are active for a given content type, ask the developer to confirm whether fields are registered for that post type slug.

---

### What data is collected

**All visitors (anonymous):**

- A random visitor cookie (`gateway_visitor`) — persists in the browser until cleared; used to link download events across sessions
- A one-way hash of their IP address (not reversible)
- Download event: which file, when, referring page, UTM parameters

**Gated visitors (when they fill in the gate form):**

- Name and email address, stored as plaintext in the database alongside a SHA-256 hash of the email
- A session cookie (`gateway_gated`) that prevents the gate from re-appearing within the same browser session
- Consent flag (whether they agreed to be contacted)
- Intake responses if a step 2 form is configured for the content type

**Data retention:** Email addresses and names are automatically anonymised after a configurable number of months (default: 24 months). The download record itself is kept. See [Data retention](#data-retention) for details.

---

## Dropbox Integration

### How it works (non-technical)

Videos and captions are stored in Dropbox. When a visitor clicks a download link, the gateway asks Dropbox for a secure, short-lived link that expires in 4 hours. The visitor's browser receives this temporary link — never the permanent Dropbox URL.

This means:
- Sharing a received download link with someone else will not work after a few hours
- Re-downloading always requires going through the gateway, which logs the event and applies the current policy

### Setup (technical)

**Step 1 — Create a Dropbox app**

1. Go to https://www.dropbox.com/developers/apps
2. Click "Create app"
3. Choose: **Scoped access** → **Full Dropbox**
4. Note the **App key** and **App secret** from the app console

**Step 2 — Set permissions**

In the app console → Permissions tab, enable:
- `files.content.read`
- `sharing.read`

Click "Submit" to save.

**Step 3 — Generate a refresh token**

Use the OAuth2 PKCE or authorization code flow with `token_access_type=offline` to obtain a long-lived refresh token. The app key and secret are used as Basic auth credentials. This is a one-time step; the refresh token does not expire unless revoked.

**Step 4 — Add constants to wp-config.php**

```php
define( 'GATEWAY_DROPBOX_APP_KEY',       'your-app-key' );
define( 'GATEWAY_DROPBOX_APP_SECRET',    'your-app-secret' );
define( 'GATEWAY_DROPBOX_REFRESH_TOKEN', 'your-refresh-token' );
```

**Step 5 — Verify**

Go to Settings → Download Gateway. The "Dropbox integration" section should show:

> ✓ Dropbox credentials found in wp-config.php. Test by downloading a video.

**Step 6 — Test**

Load a video or caption post and click a `[gateway_download]` link. Confirm the browser is redirected to a `dl.dropboxusercontent.com` URL (not a `dropbox.com/sh/...` shared link).

### How it works (technical)

**DropboxAdapter** (`class-dropbox-adapter.php`) handles all Dropbox API interactions:

1. **Access token** — obtained via OAuth2 refresh token grant (`POST https://api.dropboxapi.com/oauth2/token`). Cached in a WordPress transient for 3.5 hours.
2. **File path** — shared URL resolved to a canonical file path via `sharing/get_shared_link_metadata`. The `path_lower` field is cached for 7 days (the path is stable for a given shared URL).
3. **Temporary link** — a 4-hour download URL issued by `files/get_temporary_link`. Cached for 3.5 hours.

All caching uses WordPress transients. Transient keys:

| What | Key | TTL |
|------|-----|-----|
| Access token | `gateway_dbx_access_token` | 3.5 hours |
| File path | `gateway_dbx_path_{md5(shared_url)}` | 7 days |
| Temporary link | `gateway_dbx_link_{md5(file_path)}` | 3.5 hours |

**VideoFileResolver** reads the ACF `dropbox_link` field on videos posts.
**CaptionFileResolver** reads the ACF `file_url` field on captions posts.

Both delegate to `DropboxAdapter`. If any step in the adapter fails, `null` is returned and the download is blocked. There is no silent fallback to the raw shared URL — if the adapter cannot produce a link, the visitor receives an error.

### Troubleshooting Dropbox

| Symptom | Likely cause | Fix |
|---------|-------------|-----|
| Settings page shows "✗ Dropbox not configured" | Constants missing or misspelled in wp-config.php | Check all three `GATEWAY_DROPBOX_*` constants are defined |
| 404 on video/caption download | File deleted from Dropbox or shared link revoked | Confirm the file exists and its shared link is active |
| Download works once then fails | Transient TTL mismatch or access token revoked | Regenerate the refresh token and update wp-config.php |
| `sharing/get_shared_link_metadata` error | App credentials don't match the account that owns the file | Ensure the Dropbox app belongs to the account that owns the videos/captions folder |
| Redirect goes to dropbox.com/sh/ (not dl.dropboxusercontent.com) | DropboxAdapter bypassed or resolvers not registered | Check `FileResolverRegistry::register` calls in download-gateway.php |

**Log location:** `wp-content/debug.log` (requires `define('WP_DEBUG_LOG', true)` in wp-config.php). Gateway errors are logged at `ERROR` level; informational events at `INFO`/`DEBUG`.

---

## Developer Guide

### Registering intake field sets

Intake fields are registered via the `gateway_intake_fields` WordPress filter, typically in a theme's `functions.php` or a site-specific plugin. The filter receives an array keyed by post type slug; each value is an array of field definitions.

**Field definition keys:**

| Key | Type | Description |
|-----|------|-------------|
| `key` | string | Machine name — stored as the response key in the database |
| `label` | string | Human-readable label shown in the modal |
| `type` | string | `text`, `textarea`, `select`, `radio`, or `checkbox` |
| `options` | array | Associative `value => label` array — required for `select` and `radio` |
| `required` | bool | Currently informational; the JS does not enforce required fields |

**Example:**

```php
add_filter( 'gateway_intake_fields', function ( array $fields ): array {
    // Show these questions after any video download.
    $fields['videos'] = array(
        array(
            'key'      => 'use_case',
            'label'    => 'How will you use this resource?',
            'type'     => 'select',
            'options'  => array(
                'research'  => 'Research',
                'teaching'  => 'Teaching',
                'archiving' => 'Archiving / preservation',
                'other'     => 'Other',
            ),
            'required' => false,
        ),
        array(
            'key'      => 'notes',
            'label'    => 'Anything else you'd like to share?',
            'type'     => 'textarea',
            'required' => false,
        ),
    );
    return $fields;
} );
```

To add intake for captions or documents, add entries keyed `'captions'` or `'document_files'` respectively.

**Storage:** Responses are stored as a JSON blob in `wp_gateway_intake_responses.responses`. The schema does not change when field definitions change — new or removed fields simply appear or disappear in the stored JSON going forward.

**Per-resource and per-CPT admin control of intake is planned** for a future sub-phase. Currently, registering fields via the filter applies them to all downloads of that post type with no per-record override.

---

### Adding a new downloadable content type (FileResolver)

To make a new WordPress CPT downloadable through the gateway:

1. Create `class-my-type-file-resolver.php` implementing `FileResolver`:

```php
class MyTypeFileResolver implements FileResolver {
    public function resolve( int $post_id ): ?string {
        // Return the file URL or null if unresolvable.
        $url = get_field( 'my_file_field', $post_id );
        return $url ?: null;
    }

    public function storage_type(): string {
        return 'dropbox'; // or 'media', 's3', 'external', etc.
    }
}
```

2. Add a `require_once` in `download-gateway.php`
3. Register the resolver:

```php
FileResolverRegistry::register( 'my_cpt_slug', new MyTypeFileResolver() );
```

4. The CPT will automatically appear in Settings → Download Gateway as a configurable policy row.

---

## System Architecture (technical)

### Request flow

```
Visitor clicks [gateway_download] link
  │
  └─ JS intercepts click, reads data-policy attribute
       │
       ├─ policy = none
       │    └─ GET /gateway/v1/download/{post_id}
       │         └─ DownloadController resolves post ID
       │              → FileResolver → 302 to file URL
       │
       ├─ policy = soft | hard (first download in session)
       │    └─ Modal opens → POST /gateway/v1/gate (submit or skip)
       │         └─ Token issued; gateway_gated cookie set
       │              → GET /gateway/v1/download/{token}
       │                   → DownloadController resolves token
       │                        → FileResolver → 302 to file URL
       │              → (optional) POST /gateway/v1/intake
       │                   fire-and-forget; download not blocked
       │
       └─ policy = soft | hard (repeat download in same session)
            └─ Silent passthrough: POST /gateway/v1/gate with cookie
                 └─ Token issued (no modal shown)
                      → GET /gateway/v1/download/{token}
                           → FileResolver → 302 to file URL
```

### REST endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/wp-json/gateway/v1/download/{id}` | Resolve token or post ID → 302 to file URL |
| `POST` | `/wp-json/gateway/v1/gate` | Submit or skip gate form → issue download token |
| `POST` | `/wp-json/gateway/v1/intake` | Submit supplementary intake responses (fire-and-forget) |

All endpoints are only registered when `GATEWAY_ENABLED` is `true`.

### Policy system

**Class:** `class-policy-resolver.php`

Three-tier lookup, most specific wins:

1. `_gateway_gate_policy` postmeta on the individual post
2. `gateway_cpt_policy_{post_type}` wp_option (per content type)
3. `gateway_global_gate_policy` wp_option (site-wide default)

Each tier returns `null` to signal "no override here — fall through". The global tier always returns a concrete value. Valid concrete values: `none`, `soft`, `hard`, `disabled`.

### Database tables

All tables are created on plugin activation and upgraded automatically on `plugins_loaded` when the stored schema version is outdated.

| Table | Purpose |
|-------|---------|
| `wp_gateway_tokens` | One-time download tokens (expire after use or TTL) |
| `wp_gateway_people` | Email-known visitors; anonymised after retention window |
| `wp_gateway_download_events` | Every download lifecycle event (click, gate view, redirect) |
| `wp_gateway_intake_responses` | Supplementary intake form payloads (JSON per submission) |

---

## Operations & Maintenance

### Enabling / disabling the gateway

The gateway ships disabled. Add this to `wp-config.php` to activate download interception:

```php
define( 'GATEWAY_ENABLED', true );
```

When disabled:
- The admin settings page is still accessible
- REST routes are not registered
- The modal JS/CSS is not enqueued
- Download links render normally in page content, but clicking them returns 404

### Data retention

**Default window:** 24 months
**Location:** Settings → Download Gateway → Data retention field

After the configured number of months, the retention job nulls out `email` and `name` on `wp_gateway_people` rows. The row itself — and all download events linked to it — are retained for analytics. The SHA-256 email hash and hashed IP are kept indefinitely.

**Automatic run:** Once per day via WP-Cron.

**Manual trigger:** Settings → Download Gateway → "Run retention now" button.

**Production recommendation:** Back up WP-Cron with a server cron so the job runs even if the site has no traffic:

```bash
wp cron event run --due-now
```

### Viewing download data

Download events are stored in `wp_gateway_download_events`. There is no built-in admin UI for browsing or exporting them — use WP-CLI or a DB tool:

```bash
# Count downloads per post type in the last 30 days
wp db query "SELECT post_type, COUNT(*) as downloads
             FROM wp_gateway_download_events
             WHERE event_type = 'redirect'
               AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY post_type;"

# Export all intake responses to CSV
wp db export --tables=wp_gateway_intake_responses intake-responses.sql
```

Intake responses are stored as JSON in `wp_gateway_intake_responses.responses`, linked to a person and post via foreign keys.

### Common problems

| Symptom | Likely cause | Fix |
|---------|-------------|-----|
| Modal opens but no gate form appears | JS not enqueued or `GATEWAY_ENABLED=false` | Check wp-config.php and browser console |
| Download returns 403 | Nonce expired | Hard-refresh the page (new nonce issued) |
| Download returns 404 | Post deleted or CPT has no FileResolver | Check post exists; check `FileResolverRegistry` registrations |
| Download returns 410 | Token already used or expired | Reload the page — a new token will be issued |
| Video redirects to dropbox.com/sh/ (not temp link) | Dropbox credentials missing | Check the three `GATEWAY_DROPBOX_*` constants in wp-config.php |
| Settings page shows no CPT rows | No resolvers registered | Check `require_once` and `FileResolverRegistry::register` calls in download-gateway.php |
| Retention job never runs | WP-Cron not firing | Add a server cron for `wp cron event run --due-now` |
| Intake form not appearing | No fields registered for the post type | Check `gateway_intake_fields` filter registration in theme/plugin code |

### Logs

| Source | Location | Level |
|--------|----------|-------|
| PHP errors | `wp-content/debug.log` | Requires `WP_DEBUG_LOG=true` |
| Gateway errors | `wp-content/debug.log` | `[ERROR]` prefix |
| Gateway info | `wp-content/debug.log` | `[INFO]` prefix (requires `WP_DEBUG=true`) |
| Download events | `wp_gateway_download_events` table | Queryable via WP-CLI or DB tool |

**Enable debug logging in wp-config.php:**

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

### Uninstalling

Deleting the plugin via WordPress admin triggers a full cleanup:

- All five gateway database tables are dropped
- All gateway `wp_options` entries are deleted (policies, retention setting, schema version)
- The daily retention WP-Cron event is unscheduled

**This is irreversible.** All download event history, people records, and intake responses are permanently deleted. Export any data you need before uninstalling.
