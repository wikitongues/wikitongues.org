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
- **Drop cap:** opt-in with `.wt_prose--lede`. `editorial-content.php` gives it
  to a page's first text row only. The blog does not use it.

## Specificity

Every rule in `prose.styl` goes through `:where()`, so the whole module
weighs one class. A component can override any prose rule with a single
class of its own, without `!important` or longer selectors.
