# Moonbow QA — Playwright verification suite

Verifies **production** against **staging** (the design target) after a deploy,
page by page, at **desktop (1440px)** and **mobile (iPhone 13 / 390px)**.

Lives at `html/tests/` and is tracked in the same repo as the site code. It is a
dev-only tool — it is never loaded by WordPress.

Checks the changes in `../docs/STAGING-VS-PROD-CHANGES.md`:

- **Scroll-to-top button** — the HFE `.hfe-scroll-to-top-wrap` is present.
- **Hero spacing** — each interior hero (Services, AI Suite, Our Work, Careers,
  Contact us) sits at the same vertical position as staging (±12px).
- **Nav items present** — the 4 top-level sections exist (label **casing is
  ignored** — production's lowercase is the correct version).
- **No regressions** — no console errors or broken (4xx/5xx) requests on the
  site's own assets; third-party noise (maps, recaptcha, analytics) is ignored.
- **No temp/mirror-domain assets** — no request (font, CSS, image, script) is
  loaded from a GoDaddy `*.myftpupload.com` host.
- **Home block spacing** — the "Why we exist" and "premium platform partners"
  home blocks match staging's height (desktop only).
- **Mobile menu** — the hamburger still opens the off-canvas panel.
- **Screenshots** — full-page captures of target + staging for every page.

---

## Steps to run

### 1. Prerequisites
- **Node.js 18+** and npm. Check with:
  ```bash
  node -v && npm -v
  ```
  If missing, install from https://nodejs.org (or `brew install node`).

### 2. Install dependencies (first time, or after `git pull` changes deps)
```bash
cd html/tests
npm install
```

### 3. Install the browser (first time on a machine only)
`npm install` pulls the Playwright test runner but not the browser binary.
Install Chromium once:
```bash
npx playwright install chromium
```
> On this machine the browsers are already cached, so this is usually a no-op.

### 4. Run the suite
```bash
# Full verification: PRODUCTION vs staging, desktop + mobile
npm test
```
Other useful runs:
```bash
# Layout gate only, desktop only (fastest signal)
npx playwright test --project=desktop -g "layout matches staging"

# A single page
npx playwright test -g "Careers"

# Only capture the before/after screenshots
npm run shots
```

### 5. Read the results
- The terminal prints a pass/fail line per test.
- For details (screenshots, traces, error messages) open the HTML report:
  ```bash
  npm run report
  ```

### 6. Interpreting pass/fail
- **Before you deploy:** running against production **fails** — correct, prod
  still shows the old state.
- **After you deploy + flush WP and GoDaddy CDN cache:** re-run `npm test`; it
  should go green. If a page still fails, the report screenshot shows what差s.
- A failing **"no console errors"** test with a *mixed-content font* message
  means Google-Fonts CSS is being served over `http://` — see the note below.

---

## Overriding the targets
`TARGET` = site under test, `BASELINE` = design target to match.
```bash
# Sanity dry-run: staging against itself (layout tests should all pass)
TARGET=https://staging.moonbow.co BASELINE=https://staging.moonbow.co npm test

# Explicit production run
TARGET=https://www.moonbow.co BASELINE=https://staging.moonbow.co npm test
```

---

## Files
| File | Purpose |
|---|---|
| `playwright.config.ts` | projects (desktop/mobile), `TARGET` / `BASELINE` env vars |
| `pages.ts` | page list + shared checks (nav presence, hero offset, scroll-to-top) |
| `staging-vs-prod.spec.ts` | the tests |
| `package.json` | scripts + the one dependency (`@playwright/test`) |

`node_modules/`, `test-results/`, and `playwright-report/` are git-ignored.

---

## Known findings (2026-07-22)

**Webfonts from a temp domain (resolved 2026-07-22, guarded).** Prod's Elementor
Google-Fonts CSS (`roboto.css`, `robotoslab.css`) briefly referenced font files on
`6g0.840.myftpupload.com` (CORS-blocked, fonts fell back) — but that was **stale
GoDaddy CDN cache**; after the purge propagated both files reference `moonbow.co`.
The **`no assets loaded from a temp/mirror domain`** test guards against this
recurring (it scans same-origin stylesheet content, so it's not cache-dependent).
If it ever fails again after a migration: Elementor → Tools → **Regenerate Files &
Data** (or search-replace the temp host in
`wp-content/uploads/elementor/google-fonts/css/*.css`), then purge caches.

**Staging http→https config (resolved 2026-07-22).** Staging previously served the
Elementor google-fonts CSS (and the favicon) over `http://` on an HTTPS page
(mixed content, browser-blocked). Root cause: **`siteurl`/`home` were
`http://staging.moonbow.co`**, so WordPress generated asset URLs from an http base;
Elementor also **cached the http font URL in the `_elementor_local_google_fonts` DB
option**, so deleting the physical files didn't help (the enqueue reads the stored
URL — see `core/files/fonts/google-font.php`). Fix that stuck:
1. **Settings → General** → set both WordPress Address and Site Address to
   `https://staging.moonbow.co`.
2. **Elementor → Tools → Replace URL**: `http://staging.moonbow.co` →
   `https://staging.moonbow.co` (no trailing slash). This is the only built-in path
   that calls `Google_Font::clear_cache()` (clears the option + files), so the CSS
   regenerates with the https base. "Clear Files & Data" alone does **not** clear it.
3. Purge WP + GoDaddy CDN cache.
Verified: no `http://` subresources on the staging front end, font files regenerated
with https `@font-face` URLs. Production was never affected (its siteurl is https).
