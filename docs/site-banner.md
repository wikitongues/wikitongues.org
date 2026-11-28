# Site banner

The strip above the header on every page, set by editors on **Site banner**, under Publishing in the admin sidebar, right after Pages (`includes/admin/admin-helpers.php`). Spec: [plan.md → 1.4](../plan.md#14-site-banner). It replaced the alert banner (`banner_alert_*` in Global Options) in November 2026; that banner's old option rows are still in the database, unused.

## How a banner is chosen

| Rule | Where |
|---|---|
| One banner shows: the first row that is **On** and whose window contains now. Off always wins | `wt_site_banner_pick()` |
| Start and end are optional, entered and checked in Eastern (`America/New_York`). The site clock is UTC; don't change it | `WT_SITE_BANNER_TZ` |
| Live from the start up to, not including, the end | `wt_site_banner_is_live()` |
| A start or end that is set but unreadable keeps the banner hidden (fails closed) | `wt_site_banner_is_live()` |
| Never on the page its button leads to. A donation form's page is `/donate/`. Paths compare without the query string, case or a subdirectory home | `wt_site_banner_target_path()`, `wt_site_banner_site_path()` |
| A row with no message, or a donation form with no campaign code, never shows | `wt_site_banner_pick()` |
| Decided on each request. Production has no page cache (checked 2026-11-28). If one is ever added, this check has to move client-side | `wt_site_banner()` |

All of it lives in `includes/template/site-banner.php`, with tests in `tests/unit/SiteBannerTest.php`.

## Fields

Options page `site-banner` (`ui_options_page_wt_site_banner.json`), group `group_wt_site_banner.json`. The `site_banners` repeater holds up to 10 banners; list order is priority.

| Field | Notes |
|---|---|
| `banner_id` | A slug, set once, unique in the list. Analytics and dismissals key on it |
| `message` | Plain text, up to 100 characters |
| `cta_label` | Up to 24 characters |
| `cta_type` | `link` or `form` |
| `cta_url` | For a link. Same tab |
| `cta_form` | For a form: the FundraiseUp **campaign key**, which starts with `FUN` (the General Fund is `FUNFLYUUBYT`). The button's href is `?form=KEY`, which FundraiseUp opens as a modal over the current page. A pasted `?form=` URL is saved as the bare key. The `X…` code in `donorsupport.co/-/…` links is an *element* (a text link), not a campaign: `?form=` ignores it, so the admin refuses it and a stored one never shows |
| `enabled`, `start`, `end` | The switch and the window (`Y-m-d H:i:s`, Eastern) |

## Display and analytics

`header.php` includes `modules/banners/banner--site.php` as the first child of the sticky `.wt_masthead`; styles are in `stylus/require/banner--site.styl`. About 50px tall on desktop: the message at 16px, with any text before its first colon (within 40 characters) set bold as a lead-in (`wt_site_banner_message_parts()`), and a yellow pill button that doesn't look like the nav. On phones the message wraps beside the button.

**Dismiss.** The × stores the banner ID in localStorage (`wt_site_banner_dismissed`, a JSON list of the last 20 IDs) and hides the strip. On later pages, an inline script printed early in `<head>` (`wt_site_banner_head_script()`) adds `wt-site-banner-dismissed` to `<html>` before the body renders, so a dismissed banner never flashes. A new banner ID shows again; edited copy under the same ID stays dismissed. If storage is blocked, the banner hides for that page only.

**Events.** `js/site-banner.js` pushes `site_banner_view` (once per page, when the banner is visible), `site_banner_click` and `site_banner_dismiss` (`banner_id`, `cta_type`) to the data layer. A donation-form button also carries `data-cta-location="site-banner"`, so it counts in `donate_cta_click`.

*Setup outside the code:* in GTM, two data-layer variables (`banner_id`, `cta_type`), custom-event triggers on `site_banner_view`, `site_banner_click` and `site_banner_dismiss`, and a GA4 event tag for each; in GA4, `banner_id` registered as a custom dimension. Check in GTM preview on staging first.

**Now showing and schedule status.** On the admin page, `js/site-banner-admin.js` puts a line at the top saying which banner shows now (the first live one in list order) or which starts next, and a pill on each banner's Schedule heading: Off, Scheduled, Live now, Live but outranked by a banner above, Ended, or End is before start. It applies the same rule as `wt_site_banner_is_live()` to the form as it stands, in Eastern time from the browser's clock, and refreshes every second. It doesn't know about rule 6 (hidden on the target page).

## Running a banner

1. Add the row with its ID, message, button and window, and switch it On. It appears at the start, on its own.
2. To stop it early, switch it Off.
3. For a donation form, get the campaign code from FundraiseUp first. The row can't be saved without one.
