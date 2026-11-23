# Content styles

How rich text (anything a WYSIWYG field, the block editor or `the_content()`
produces) is styled. Planned in `plan.md` 7.7.

## The rule

**Bare tags are styled only inside `.wt_prose`.** Components style their own
classes and never `p`, `ul`, `a` and so on. A new surface that renders rich
text adds `wt_prose` to its container instead of writing its own tag rules.

`.wt_prose` lives in `wp-content/themes/blankslate-child/stylus/require/prose.styl`.

## Prose versus layout

`.wt_prose` styles what is *inside* a block of text. It never sets the
container's width or outer margins; the container's own class does that.
Running text sits in a reading column `$measure` wide (760px, defined in
`global.styl`), about 80 characters a line.

| Surface | Container (layout) | Template |
|---|---|---|
| Editorial text rows | `.wt_text-row` | `modules/flexible-content/text-layout.php` |
| Fellow "About the Fellowship" | `.wt_text-row` | `single-fellows.php` |
| Blog post body | `.blog-post .entry-content` | `single-blog.php` |

FAQ galleries (`.custom-gallery.full.faq`) are not prose yet but share the
`$measure` column.

## Decisions

The defaults come from the blog, the most-read prose surface.

- **Type:** body 1.6rem at 1.5 line height. `h2` in the display face at 3rem,
  `h3` bold at 2rem.
- **Rhythm:** every text block (paragraph, list, figure, quote, table, heading)
  has a 1.6rem bottom margin, at any depth, because pasted content wraps
  paragraphs in extra `div`s (several blog posts came from Medium). Headings
  get more space above (4.8rem for `h2`, 3.2rem for `h3`) and 0.8rem below.
- **Links:** blue and underlined, with the `anchor()` hover pill.
- **Lists:** blue markers. A bold lead-in stays inline, so it reads as part of
  the item rather than as another subheading.
- **Tables:** right-aligned numbers in tabular figures, first column left.
  Wrap a table in `.table-scroll` so it scrolls sideways on phones.
- **Images:** full column width with rounded corners. Captioned images
  (`.wp-caption`) run 5% wider than the column on each side, with a small
  grey caption centred below.
- **Images in text rows go in the WYSIWYG** (Add Media), with or without a
  caption, not in the row's separate `image` field. Text rows run the same
  filters as `the_content` (`wp_filter_content_tags`, `do_shortcode`), and
  rewrite `sizes` to the column width, so any inserted size loads sharply.
  The `image` / `image_caption` fields still render but are being retired.
- **Drop cap:** opt-in with `.wt_prose--lede`. `editorial-content.php` gives it
  to a page's first text row only. The blog does not use it.

## Specificity

Every rule in `prose.styl` goes through `:where()`, so the whole module
weighs one class. A component can override any prose rule with a single
class of its own, without `!important` or longer selectors.

## Editorial pages: shared tokens

Pages laid out as editorial spreads rather than prose (the donate page and the
Language Rights report) build from one set of tokens and mixins in
`global.styl`, instead of each defining its own. A colour, width or size is a
literal in one place, its token; page files never rename a token.

| Token | Value | Use |
|---|---|---|
| `$wash` | `#f3eee0` | tinted section background |
| `$rule` | `#dcd5c3` | warm divider (`$border-color` is the blue one) |
| `$grey-65` | `#64635e` | muted text |
| `$red-text` | `#b54a35` | red as text; passes AA on `$parchment` and `$wash` (`$red` is for fills) |
| `$content-wide` | 1200px | the editorial column (`$content` stays the site default) |
| `$type-label` … `$type-deck` | 13 / 14 / 16 / 18 / 21px | editorial type scale |
| `$space(n)` | `n × 8px` | spacing |

| Mixin | Does |
|---|---|
| `eyebrow(color)` | the small uppercase label above a heading; links inside keep its size and colour. Pass `inherit` on dark boxes |
| `focus-ring(color)` | 3px outline on `:focus-visible`; blue by default, `$parchment` on dark sections |
| `disclosure(toggle-size, spread)` | on a `<details>`: hides the marker and adds a `+` that turns to × when open, with reduced motion. `spread` puts the `+` at the far edge; `false` keeps it beside the text |
| `signup-field()` / `signup-button()` | the newsletter's email field and button, used by the report's signup dialog. The field is 16px at every width so iOS doesn't zoom on focus |

- **Sizes stay px.** The site's mobile rem shrink would change these pages;
  moving editorial sizes to rem is a separate decision.
- **Breakpoints** use `$mobile`, `$tablet` and `$smallScreen`.
- **Never bare `min()` or `max()`.** Stylus evaluates them at compile time:
  `min(100%, 1200px)` compiles to `100%`. Use `max-width`, or `unquote()`.
- **No `$token/1.4` in a `font` shorthand.** Stylus divides it; write the
  properties out.

