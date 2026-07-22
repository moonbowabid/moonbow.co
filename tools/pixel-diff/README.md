# pixel-diff — staging ↔ production styling comparison

A reusable toolkit for finding **every** design/layout/content difference between
`staging.moonbow.co` and `moonbow.co` on a given page — down to borders, radius,
shadows and single-pixel margins that the height-based Playwright suite in
`../../tests/` can't see.

Built for Task 4/5 (compare staging→prod, deploy the diffs). Staging is the
**design target**; the goal is to make production a pixel clone of it.

## Files
| File | What it does |
|---|---|
| `css-diff.py` | **Primary tool.** Diffs the generated Elementor CSS of a page between the two sites, rule-by-rule, bucketed into REAL / VAR-only / STRUCTURAL. Deterministic, no browser needed. |
| `extract.js` | Browser-console helpers (`pdExtract`, `pdSection`, `pdForceLoadImages`, `pdStructural`) to read **computed** styles and verify/inspect what `css-diff.py` reports — needed for sections that were *rebuilt* (different element hashes). |

## Quick start
```bash
python3 css-diff.py /                      # home
python3 css-diff.py /services/ /ai-suite/  # any number of page paths
```
Override targets if hosts change:
```bash
STAGING=https://staging.moonbow.co PROD=https://moonbow.co python3 css-diff.py /
```

## How to read the output
- **REAL** — a concrete CSS property (border, padding, color, radius, shadow…)
  differs on an element that exists on **both** sites. These are the deploy-worthy
  design differences.
- **VAR-only** — only Elementor `--*` layout variables differ (e.g. `--margin-top`,
  `--min-height`). Usually still a real spacing/size difference — Elementor consumes
  these vars — but occasionally cosmetic (a `--min-height:0px` vs unset renders the
  same). Confirm the visible ones with `extract.js`.
- **STRUCTURAL** — an element hash present on only one side. Means a section was
  **rebuilt** (same content, new element instances) or a widget was added/removed.
  `css-diff.py` can't compare rebuilt sections by hash — use `pdSection()` to compare
  them by DOM order / rendered size instead.

## Why it aligns across two different documents
Staging and prod home are *different* Elementor documents (post 9628 vs 9172), but a
page duplicated between environments keeps the same per-element hashes
(`.elementor-element-<hash>`). `css-diff.py` normalises away the `.elementor-<docid>`
prefix and the host, so rules line up by hash. If a page was NOT duplicated (built
independently on each side) everything shows as STRUCTURAL — fall back to `extract.js`
+ visual comparison.

## Gotchas learned the hard way
- **Always cache-bust.** Cloudflare (prod) and the GoDaddy CDN serve stale generated
  CSS; Elementor also regenerates these files lazily. `css-diff.py` appends `?cb=<ts>`
  to force an origin-fresh read. Re-save the page in Elementor if you suspect the
  generated CSS lags the actual data, then re-run.
- **Lazy-load 0×0 trap.** An Elementor image with no explicit size collapses to 0×0
  until it loads, and a 0×0 element may never trigger its own lazy-load — so a
  programmatic off-screen measurement reads 0×0 even though a scrolling user sees the
  image fine. Call `pdForceLoadImages(scopeSel)` before measuring image sizes, or you'll
  chase a phantom "broken images" difference.
- **Screenshots reset scroll here.** In the automation harness the screenshot tool
  snaps back to the top of the page, so you can't rely on `scrollIntoView()` +
  screenshot to capture a below-fold section. Measure with `getBoundingClientRect()`
  in JS instead of eyeballing screenshots.
- **Global CSS is skipped.** post-11 (kit), post-15 (header) and post-17 (footer) are
  verified identical between the sites and excluded to cut noise. Re-check them
  separately if the header/footer/theme ever changes.

## Browser workflow (for rebuilt sections & verification)
1. Load `extract.js` in the console on **staging**; run `pdExtract()` /
   `pdSection('<headingHash>')`.
2. Do the same on **prod**.
3. Compare by DOM order (rebuilt sections) or by hash (shared elements). For a quick
   structural check, grab `Object.keys(pdExtract())` on one site and pass it to
   `pdStructural(otherHashes)` on the other.
