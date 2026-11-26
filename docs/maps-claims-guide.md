# Managing the claims table

How a person keeps the maps' contested areas right: who claims what, where to look, what to press, and who does what. The rules themselves are in plan.md 3.2 ("Claims: contested areas"); how the code works is in [maps.md → Claims table](maps.md#claims-table).

**Owner:** Frederico Andrade edits and reviews the table and owns the yearly review.

## What you are editing

One file in the repository: [`wp-content/plugins/wt-maps/data/claims.json`](../wp-content/plugins/wt-maps/data/claims.json). Nothing lives in Airtable or the WordPress admin.

The file is a list of **layers**, applied in order:

- **Layer 1, `natural-earth`:** what Natural Earth says. A script writes it. **Never edit it by hand.**
- **Every later layer is a review:** our decisions on top of Natural Earth. **This is the only part you edit.**

A review is a list of **changes**. Each change names one area and says one thing about it:

```json
{
 "area": "C02",
 "add": ["rec69csgIMSP0BDGu"],
 "reason": "Russia claimed Donetsk by annexation, September 2022.",
 "source": "UN General Assembly resolution ES-11/4 (12 October 2022)"
}
```

| Field | Meaning |
|---|---|
| `area` | The contested area's code (`B00`, `C02`, `SAH`, or `UA-14` for one a review added). Find it by searching the file for the area's name. |
| `add` | Record IDs of places that now claim the area. |
| `drop` | Record IDs of places whose claim no longer counts (dormant or ended). |
| `status` | Leave it out for a normal contested area. `"ended"` draws the area as part of its territory again (Artsakh). `"duplicate"`, with `"of": "<code>"`, draws it once, as another area (rarely needed). |
| `shape` | Only to add a new area: `"admin1:<ISO 3166-2 code>"`, a province, state or oblast from Natural Earth's admin-1 layer. `area` is then that ISO code, and the change also needs a `name`. |
| `name` | The new area's name, with `shape`. |
| `reason` | Required. One sentence a stranger could check. |
| `source` | Required in practice: a law, treaty, UN document, official map or statement. |

**A claim counts** only if it is formal and current: a government's assertion in an official act (law, official map, UN statement). A claim never given up but not pressed is dormant, and one given up or overtaken is ended. Neither draws.

**Record IDs** are the places' Airtable record IDs. Look one up in [`data/shapes.json`](../wp-content/plugins/wt-maps/data/shapes.json): search for the place's name, and the `rec…` key above it is the ID. If the place isn't there, it has no map yet; add it to the shape table first (see [maps.md → Changing territories](maps.md#changing-territories)).

### Never

- **Edit or delete layer 1.** A Natural Earth upgrade replaces it, and your decisions must survive that.
- **Delete or rewrite an old change** to undo it. Add a new change that reverses it, with its own reason. The file is the record of every decision; git keeps who made it and when.
- **Name an area by anything but its code,** or a place by anything but its record ID. Names get renamed and repeated.

## Making a change

There are two ways to make a change: ask Claude to do it, or edit the file yourself on GitHub. Either way the change ends in one pull request, and the maps it affects are rebuilt in that same PR.

### With Claude (recommended)

The rebuild needs the build cache (2.5 GB of elevation data) on a computer that has it, which today is Frederico's. Claude can run it there.

1. Open a Claude Code session in this repository.
2. Say what changed and give a source. For example: *"Russia now formally claims Zaporizhzhia and Kherson; source: UN GA resolution ES-11/4. Add it to the claims table."*
3. Claude will then:
   - read this guide and the spec;
   - add the change to the latest review layer, or start a new dated layer (`wikitongues-YYYY-MM-DD`) for a new review session;
   - rebuild only the maps that change;
   - show you the list of maps that changed and why (reframed, claimed, revealed), and screenshots of the important ones;
   - run the checks;
   - open a PR.
4. **You review the PR on GitHub.** Read the reason and source, the list of changed maps, and the screenshots.
5. **You merge it.** Claude never merges, deploys or edits a review layer without being asked.

Claude flags, rather than decides, anything political: whether a claim is current, which source to trust, how to word a reason. Those are your calls, and the PR says which questions are open.

### By hand on GitHub

1. Open the repository on github.com, go to `wp-content/plugins/wt-maps/data/claims.json`, and press the **pencil icon** (Edit this file).
2. Scroll to the last layer (after layer 1's long `rows` list) and add your change to its `changes` list. Copy an existing change to get the commas and brackets right.
3. Press **Commit changes…**, choose **Create a new branch for this commit and start a pull request**, name the branch `data/<your-initials>/claims-<topic>`, and press **Propose changes**, then **Create pull request**.
4. The CI checks run on the PR. The **PHPUnit** check includes a test that the committed table is clean: every claimant is a known place, every change has a reason, and every area exists. Red means fix the file.
5. **The maps are not rebuilt yet.** Comment on the PR "needs a rebuild", and Frederico (or Claude on his machine) checks out the branch, rebuilds the affected maps, and pushes them to the same PR. Don't merge a claims change without its maps: the site would keep showing the old claims.

## Reviewing a change

A change to who claims an area is a political statement on every page that shows the area, so it gets two people:

- **The person who made the change** (or asked Claude to) writes the reason and the source.
- **A second person approves the PR.** When Claude drafted it, Frederico is the reviewer. When Frederico wrote it himself, someone else on the team approves before it merges.

What the reviewer checks, in the PR:

1. **The source** supports the reason, and the claim is formal and current.
2. **The changed-maps list** has only the maps you would expect. A claim on Crimea should touch Russian, Ukrainian and nearby sets, not South America.
3. **The screenshots** of the main changed maps look right.
4. **The checks are green.**

## After merging

1. **Deploy to staging:** in Claude Code, run `/deploy staging`, or open a PR from `main` to `staging` on GitHub, which Frederico merges.
2. **On staging, run `wp wt-maps doctor`.** It must end in success. Its "Problems in data/claims.json" section must be empty. Read "changes that change nothing", if any: an override that no longer changes anything has gone stale.
3. Open two or three changed language pages on staging and look at the map.
4. **Deploy to production** the same way, and run `doctor` there too.

## The yearly review

Once a year, and after any news that moves a border:

1. **Read layer 1 against the news.** Ask Claude for a summary: *"List every contested area with its effective claimants, and flag any that recent events may have changed."* Claude reads the table and lists them; it does not decide.
2. **Decide each flagged area.** Record each decision as a change in a new review layer, `wikitongues-YYYY-MM-DD`.
3. **When Natural Earth releases a new version:**
   1. Claude updates the downloads and runs `node build/seed-claims.mjs` (a dry run) to show how layer 1 would change.
   2. With your go-ahead, it runs the script again with `--write`.
   3. Then `doctor` lists the overrides that now change nothing, so you can retire them with a reversing change, or keep them.
4. **Put the next review on the calendar.**

## Asking others for input

- **Team members and partners** can propose a change by opening a GitHub issue that names the area, the place, and a source. (An issue template for this is a plan 3.2 follow-up; until then a plain issue is fine.) The issue is the queue; the PR that closes it is the record.
- **Speakers or community members** who report a map as wrong (by email, or through a partner) get the same treatment: open an issue with what they said and any source they gave, then decide it in a review.
- **Every decision is visible in the repo.** The reason, the source and the PR are public, because the repository is public. Write reasons for a stranger, and keep personal details out of them.

## Common tasks

| You want to… | Change |
|---|---|
| Add a claimant | `"add": ["rec…"]` |
| Remove a dormant or ended claim | `"drop": ["rec…"]` |
| Mark a dispute as over (the area goes back to its territory) | `"status": "ended"` |
| Undo an earlier change | A new change that does the opposite, with its own reason |
| Add an area that is a whole province, state or oblast (the 2022 oblasts) | `"shape": "admin1:<ISO 3166-2 code>"`, `"name"`, and `"add"` for its claimants. The area's code is the ISO code (`UA-14`) |
| Add an area that isn't a whole province (East Jerusalem) | Not a table edit: it needs a shape drawn first. Ask Claude |
| See what the maps show today for an area | Ask Claude: *"Who claims B89 now, and which maps show it?"* |
