# Wikitongues – Technical Debt & Improvement Plan

This file tracks known issues, deferred refactors, and planned improvements.
Items are grouped by area and roughly ordered by priority within each group.

---

## Code Quality

### Refactor dynamic IN clause in `wt-gallery` to eliminate raw SQL
**File:** `wp-content/plugins/wt-gallery/includes/queries.php` (~line 33–44)
**Context:** The `featured_languages` branch of `get_custom_gallery_query()` builds a
dynamic `IN ($placeholders)` clause using `array_fill('%s')` and `$wpdb->prepare()`.
This is the correct WordPress pattern, but PHPCS cannot statically verify that
`$placeholders` is safe, requiring a `phpcs:disable` suppression comment.
**Goal:** Refactor to avoid raw SQL entirely — query by post ID or slug instead of
`post_title`, which would allow `WP_Query` args (`post__in`) to replace the manual
`$wpdb` query. This also makes the query more robust (post titles are not unique).
**Prerequisite:** Understand how gallery shortcode callers pass `meta_value` for
`featured_languages` — the refactor must preserve that interface or update callers too.

---

## Plugins

### Track `wt-form` in version control when feature work resumes
**File:** `wp-content/plugins/wt-form/`
**Context:** Currently excluded from git. Plugin handles form submissions to Airtable
and Mailchimp. Not actively used; excluded from linting scope for now.
**Goal:** Add to `.gitignore` allowlist, include in PHPCS scan, and review for
security (form validation, API key handling).

### Track `integromat-connector` in version control when automation work resumes
**File:** `wp-content/plugins/integromat-connector/`
**Context:** Currently excluded from git. Custom Make.com connector with API token
auth. Not in active development; excluded from linting scope for now.
**Goal:** Add to `.gitignore` allowlist, include in PHPCS scan, review API token
handling and REST endpoint security.

---

## Infrastructure

### Migrate from Stylus to a maintained CSS preprocessor
**Context:** Stylus is largely unmaintained. Its dependency chain (`glob@7` →
`minimatch@3`) has known ReDoS vulnerabilities (dev-only, no production impact).
`npm audit` flags 3 high-severity findings with no clean in-place fix.
**Goal:** Migrate to PostCSS or Sass. Resolves audit findings and improves long-term
maintainability of the CSS build pipeline.

---

## Testing (Phase 3)

### PHPUnit + WP_Mock setup
Set up unit testing infrastructure for custom theme includes and `wt-gallery` plugin.
See Phase 3 of the operational improvement plan.
