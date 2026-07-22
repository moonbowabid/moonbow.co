# Staging → Production: Page-wise Design Changes

> **Goal:** bring **production** (`www.moonbow.co`) in line with **staging**
> (`staging.moonbow.co`). Each section lists the visible difference and exactly
> how to apply it on production using the **Elementor editor** and **theme CSS**
> where required.
>
> **Method:** live visual comparison in Chrome at 1440px desktop width, page by
> page, plus computed-style measurement of the DOM. Date: 2026-07-22.
>
> ⚠️ **Important — the two sites have diverged in BOTH directions.** Staging is
> not simply "ahead". On the **homepage**, production currently has polish that
> staging lacks (see Home below). So this is not a blind "push staging over
> prod" — apply changes item by item and confirm intent on the flagged ones.
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

### G2. "Scroll to Top" button  — VERIFIED (HFE / UAE Lite 2.9.1)

- **Staging:** enabled. **Prod:** absent.
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

## Home — `/`  ⚠️ diverged, confirm intent

- Staging front page = Elementor page **`9628`**; production = **`9172`**.
  These are **different pages** (a homepage redesign on staging), so this is a
  page swap, not element tweaks.
- **Visible differences (prod has these, staging does NOT):**
  - The script **"for"** accent word in the hero ("The digital media agency
    *for* RETAIL") — present on prod, **missing on staging**.
  - A **hero background image** (faded product/office photo) behind the purple —
    present on prod, staging hero is flat purple.

**How to apply on production:**
- **Option A (recommended if staging's redesign is final):**
  1. On staging: Elementor → open page `9628` → **⋮ menu → Save as Template**
     (or **Export Template** to a JSON file).
  2. On production: Elementor → **Templates → Saved Templates → Import** the JSON.
  3. Create/replace the home page from that template, then **Settings → Reading →
     Your homepage displays → set the new page as the static Front page**.
  4. Re-check the hero: if the "for" word and hero background image were dropped
     unintentionally, re-add them (Heading widget in Caveat script font for "for";
     Section/Container → Style → Background → Image).
- **Option B:** manually edit prod's `9172` in Elementor to match staging.

> 🔴 **Decide before deploying:** are the missing "for" word and hero background
> image intentional removals in the new design, or an incomplete draft? Confirm
> with the design owner, otherwise production loses existing polish.

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
| Global | Enable Scroll-to-Top button | UAE → Widgets + Elementor Site Settings (see G2) | ⏳ pending |
| Home `/` | Promote staging redesign (page 9628) ⚠️ | Elementor template import + Reading settings | ⏭️ skipped (deferred) |
| Services | Reduce hero top spacer (~80px) | Elementor page edit | ✅ done & verified |
| AI Suite | Reduce hero top spacer (~85px) | Elementor page edit | ✅ done & verified |
| Our Work | Reduce hero top spacer (~80px) | Elementor page edit | ✅ done & verified |
| Careers | Reduce hero top spacer (~58px) | Elementor page edit | ✅ done & verified |
| Contact us | Reduce hero top spacer (~80px) | Elementor page edit | ✅ done & verified |
| About us | — none — | — | ✅ n/a |

> **Margin/hero-spacing changes: DONE (2026-07-22)** — verified on production at
> desktop (1440px) via `html/tests` (`layout matches staging`, all pages green).
> Mobile (390px) not yet re-verified. Still outstanding: **scroll-to-top** (G2)
> and the **homepage** promotion (intentionally deferred).

**Always finish by flushing the WordPress cache and the GoDaddy CDN** after edits.

> Scope note: this list covers the homepage + all top-nav pages, compared
> visually front-end. It does not include interior/detail pages not linked from
> the main nav (individual service, case-study, or help-centre articles). If
> those were also restyled on staging, compare them the same way before signing off.
