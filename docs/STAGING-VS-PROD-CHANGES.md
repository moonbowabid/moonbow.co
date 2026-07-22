# Staging → Production: Page-wise Design Changes

> **Goal:** bring **production** (`www.moonbow.co`) in line with **staging**
> (`staging.moonbow.co`). Each section lists the visible difference and exactly
> how to apply it on production using the **Elementor editor** and **theme CSS**
> where required.
>
> **Method:** live visual comparison in Chrome at 1440px desktop width, page by
> page, plus computed-style measurement of the DOM. Date: 2026-07-22.
>
> ⚠️ **Important — apply changes item by item and confirm intent on flagged ones.**
> Staging is the design target. On the **homepage** the two pages have since
> converged in structure, but production renders a couple of blocks **taller**
> than staging (see Home below); those need reducing on prod, not a blind page
> swap. (An earlier note that prod's homepage had "polish staging lacks" — the
> script *for* word and hero image — is now stale; both heros match.)
>
> ✅ **Correction to the earlier (HTTP-only) Task 4 note:** the "staging = mega
> menu / prod = flat 7-item menu" finding was WRONG — an artifact of parsing the
> rendered HTML. Verified visually: **both** environments have the identical
> ElementsKit mega-menu (same dropdown columns, items, and feature card). The
> only header differences are label casing and the scroll-to-top button (below).

---

## GLOBAL — applies to every page (header/footer templates)

### G1. Top-level nav label casing — ❌ NO CHANGE (production is correct)
- **Prod (now):** `Services · AI suite · Our work · About us` ✅ **correct — keep as is.**
- **Staging:** `Services · AI Suite · Our Work · About us` — this Title Case is the
  **mistake**; do not carry it over.
- **Action: none on production.** If you promote the header or home page from
  staging, **preserve production's lowercase labels** ("AI suite", "Our work") —
  re-lowercase them in Appearance → Menus if the staging import changes them.

### G2. "Scroll to Top" button  — ✅ DONE & VERIFIED (2026-07-22)

> **Now live on production.** The Playwright `scroll-to-top button present` test
> is green on all 7 pages (`.hfe-scroll-to-top-wrap` present site-wide). Steps
> below are retained for reference / re-deploys.

- **Staging:** enabled. **Prod:** ~~absent~~ → now enabled.
- **It is NOT a footer widget.** It is a Header-Footer-Elementor **extension**
  that renders `<span class="hfe-scroll-to-top-button">` (inside
  `.hfe-scroll-to-top-wrap`) injected at the end of `<body>` via `wp_footer`
  (site-wide), with its own inline jQuery. Source of truth verified in the plugin
  code (`inc/widgets-manager/extensions/class-scroll-to-top*.php`) and against
  staging's live DOM.
- **Staging uses all DEFAULT styling** — the only change is the on-switch. The
  kit CSS contained no custom size/colour rules, so the button is the plugin
  default: 50×50 black circle, white `fas fa-chevron-up`, 15px from bottom/right.

**Two things must both be set (extension enable + global toggle):**

1. **Enable the extension:** WP Admin → sidebar **UAE** → **Widgets** →
   **"Scroll to Top"** → toggle **ON**. (Writes WP option `_hfe_widgets`.)
2. **Switch it on globally:** Elementor (edit any page) → ☰ → **Site Settings** →
   **Scroll to Top** tab → **Enable Scroll To Top = Yes**.
   (Writes kit control `hfe_scroll_to_top_global = yes` on the Elementor kit,
   post 11.)
3. **Leave styling at default** to match staging: Position **Bottom Right**,
   Icon **`fas fa-chevron-up`**, Media Type **Icon**, Responsive Support **Yes**.
4. **Update**, then **Elementor → Tools → Regenerate Files & Data**, then flush
   WP + GoDaddy CDN cache.

> 🔴 **Critical gotcha (verified on local 2026-07-22):** after enabling the
> toggle the button may appear **on the homepage only**. This is NOT a plugin or
> settings bug — it is **stale cached page output**. Only pages that get
> re-rendered after the toggle (e.g. the freshly-saved homepage) pick up the
> `wp_footer` button; other pages keep serving pre-toggle cache. **Fix = force a
> full regenerate + cache purge:** Elementor → Tools → **Regenerate Files & Data**,
> then flush **every** cache layer (WP object/page cache, any caching plugin, and
> the **GoDaddy CDN**). On local this was resolved by reinstalling the plugin,
> which triggered the same regenerate. Plugin version was identical (2.9.1) before
> and after — so on production, do **not** assume you need a different plugin;
> just enable + regenerate + purge all caches.

**Verify:** front-end — scroll >100px, black circle appears bottom-right on
**several** pages (home AND an interior page), click smooth-scrolls to top. DOM —
`.hfe-scroll-to-top-wrap` is a direct child of `<body>` (the Playwright suite
checks exactly this, across all pages).

---

### G3. Elementor webfonts served from a temp/mirror domain — ✅ RESOLVED (2026-07-22)

> **Was a stale-CDN-cache artifact.** During the first re-verification pass the
> font CSS still referenced the temp domain (CORS-blocked) — but that was the old
> cached copy. After the GoDaddy CDN purge propagated, both `roboto.css` and
> `robotoslab.css` now reference `moonbow.co` only, and the per-page
> `no assets loaded from a temp/mirror domain` test is green on all 7 pages. The
> detail below is retained as a reference for if this recurs after a future
> migration (the test is a permanent guard against it).

- **Symptom (when present):** on **every** production page the browser console shows
  `Access to font at 'https://6g0.840.myftpupload.com/.../roboto-*.woff2' from
  origin 'https://moonbow.co' has been blocked by CORS policy`. Roboto / Roboto
  Slab webfonts therefore **fail to load** and fall back to a system font.
- **Root cause:** the generated Elementor font CSS on prod
  (`wp-content/uploads/elementor/google-fonts/css/roboto.css` and
  `robotoslab.css`) is served from `moonbow.co`, but every `@font-face src`
  inside it points at the GoDaddy temp host **`6g0.840.myftpupload.com`**. Those
  cross-origin `.woff2` files have no `Access-Control-Allow-Origin` header, so
  they are blocked. (Same class of temp-domain contamination as the button URLs.)
- **Fix on production (either):**
  1. **Preferred:** Elementor → **Tools → Regenerate Files & Data** — rewrites the
     google-fonts CSS with the current site host. Then flush WP + GoDaddy CDN.
  2. **If regenerate doesn't clear it:** search-replace the temp host in those CSS
     files (they are physical files under `uploads/elementor/google-fonts/css/`):
     replace `6g0.840.myftpupload.com` → `moonbow.co`, then purge caches.
- **Test:** the Playwright `no assets loaded from a temp/mirror domain` test
  (per page) fails while any request hits `*.myftpupload.com`; it goes green once
  the fonts are served from `moonbow.co`.

---

## Home — `/`  ✅ block spacing done & verified

> **Re-reviewed 2026-07-22 (browser DOM measurement, 1440px desktop).** The two
> home pages have since **converged structurally** — same 10 top-level sections,
> in the same order, with the **same Elementor element IDs** (the earlier
> "different page 9628 vs 9172 / missing *for* word / missing hero image" finding
> is now stale; both heros render "The digital media agency *for* RETAIL"). What
> remains are **block height / spacing differences**: production renders several
> blocks **taller** than staging. Staging is the target, so production needs
> these reduced. Measured section heights (prod − staging):

| # | Block (heading) | Section id | staging h | prod h | Δ | Cause |
|---|---|---|---|---|---|---|
| 2 | **Why we exist** ✅ FIXED | `5c33c08` | 577px | ~~877~~ **577px** | 0 (was +300) | Min Height reduced 877→577px on prod — now matches staging |
| 7 | **We are premium platform partners** ✅ FIXED | staging `d53e08c` / prod `692ad68` | 550px | **550px** | 0 (was −208) | Min Height set to 550px on prod — now matches staging. (Earlier 342px reading was stale CDN cache before the edit propagated.) |
| 3 | What we do | `f893361` | 1744px | 1837px | +93 | ⚠️ no single spacing setting differs (min-height/padding/gap all identical; inner widgets are equal-or-smaller on prod) — content/font-render drift, **not** a margin edit |
| 5 | HUMAN (comparison) | `938e332` | 1477px | 1567px | +90 | ⚠️ same as above — no spacing setting differs |

Sections 0, 1 (hero), 4, 6, 8, 9 match to the pixel.

**How to apply on production (Elementor editor):**

1. **Why we exist** — ✅ **DONE (2026-07-22).** Min Height reduced 877→577px on
   prod; now measures 577px = staging. Playwright block-spacing test green.

2. **We are premium platform partners** — ✅ **DONE (2026-07-22).** Min Height set
   to 550px on prod; now measures 550px = staging. Playwright block-spacing test
   green. (The earlier 342px reading was stale CDN cache from before the edit
   propagated — re-checked after purge.)

3. **What we do / HUMAN** — **no spacing action.** The +90px is not from any
   margin/padding/gap/min-height setting (all identical to staging); it is content
   or font rendering. Leave as-is unless a visual side-by-side shows a real gap.

> ℹ️ **Note:** both home-block fixes are now live and verified against staging.
> "What we do" / "HUMAN" remain ~90px taller on prod, but that is content/font
> render (no spacing setting differs) — left as-is by design.

---

## Services — `/services/`

- **Difference:** content is identical; the **hero top spacing is ~80px larger
  on prod**, so the H1 "Know every move your AI makes" sits lower. Staging is
  tighter. (Same page ID on both — a spacer edit made on staging, not synced.)

**How to apply on production (Elementor, no CSS needed):**
1. Edit `/services/` with Elementor.
2. Select the **Spacer widget** at the very top of the hero section (between the
   breadcrumb strip and the H1).
3. Reduce its **height** to match staging (≈ −80px). Update + flush cache.

> Theme-CSS alternative (only if you prefer a global rule over per-page edits):
> add a negative-margin/reduced-padding rule in `custome-style.css` scoped to the
> hero container — but the Spacer edit is cleaner and mirrors how staging did it.

---

## AI Suite — `/ai-suite/`

- **Difference:** identical content; **hero top spacing ~85px larger on prod**
  ("Make your website AI agent ready" sits lower).

**How to apply:** same as Services — Elementor → edit `/ai-suite/` → shorten the
top **Spacer** in the hero to match staging. Update + flush cache.

---

## Our Work — `/our-work/`

- **Difference:** identical content (same "Case studies" + Lego image); **hero
  top spacing ~80px larger on prod**.

**How to apply:** Elementor → edit `/our-work/` → shorten the top **Spacer**
above the "Case studies" H1 to match staging. Update + flush cache.

---

## Careers — `/careers/`

- **Difference:** identical content ("Grow with us" + office image + 3 cards);
  hero content sits **~58px lower on prod** (measured). Same page on both; a top
  spacer inside the hero container is taller on prod.

**How to apply:** Elementor → edit `/careers/` → select the **Spacer** at the top
of the hero container (the one above the "Grow with us" row) → reduce its height
by ~58px to match staging. Update + flush cache.

---

## Contact us — `/contact-us/`

- **Difference:** identical content (form + address + map); **hero top spacing
  ~80px larger on prod** ("Let's work as a team!" sits lower).

**How to apply:** Elementor → edit `/contact-us/` → shorten the top **Spacer**
in the hero to match staging. Update + flush cache.

---

## About us — `/about-us/`

- **No change.** Pixel-identical on staging and production (only the global nav
  casing G1 differs, which is fixed once in the menu). Nothing page-specific to do.

---

## Summary checklist

| Scope | Change | Where to fix | Status |
|---|---|---|---|
| Global | ~~Nav label casing~~ — **no change; prod is correct** (keep lowercase) | — | n/a |
| Global | Enable Scroll-to-Top button | UAE → Widgets + Elementor Site Settings (see G2) | ✅ done & verified |
| Global | ~~Elementor webfonts from temp domain~~ (see G3) — was stale CDN cache | Regenerate Files & Data on prod | ✅ resolved & verified |
| Home `/` | "Why we exist" block: Min Height 877→577px (−300px) | Elementor page edit (section `5c33c08`) | ✅ done & verified |
| Home `/` | "Premium platform partners" block: Min Height set to 550px | Elementor page edit (section `692ad68`) | ✅ done & verified |
| Home `/` | "What we do" / "HUMAN" +90px | — no spacing setting differs; content-render only | ℹ️ no action |
| Services | Reduce hero top spacer (~80px) | Elementor page edit | ✅ done & verified |
| AI Suite | Reduce hero top spacer (~85px) | Elementor page edit | ✅ done & verified |
| Our Work | Reduce hero top spacer (~80px) | Elementor page edit | ✅ done & verified |
| Careers | Reduce hero top spacer (~58px) | Elementor page edit | ✅ done & verified |
| Contact us | Reduce hero top spacer (~80px) | Elementor page edit | ✅ done & verified |
| About us | — none — | — | ✅ n/a |

> **Status (re-verified 2026-07-22 via `html/tests`, desktop 1440px):**
> - ✅ **DONE & GREEN (desktop 1440px):** interior hero spacing (all pages), both
>   home blocks (**"Why we exist"** 577px + **"Premium platform partners"** 550px,
>   = staging), **Scroll-to-Top** (present on all 7 pages), **webfonts served from
>   `moonbow.co`** (temp-domain issue resolved, G3), nav, button/link domains,
>   layout, console errors.
> - ℹ️ Two early false alarms this pass (premium block at 342px, temp-domain fonts)
>   were **stale GoDaddy CDN cache** — both cleared once the purge propagated.
> - ✅ Mobile (390px, iPhone 13) hero margins re-verified 2026-07-22 —
>   `layout matches staging` green on all 7 pages.

**Always finish by flushing the WordPress cache and the GoDaddy CDN** after edits.

> Scope note: this list covers the homepage + all top-nav pages, compared
> visually front-end. It does not include interior/detail pages not linked from
> the main nav (individual service, case-study, or help-centre articles). If
> those were also restyled on staging, compare them the same way before signing off.
