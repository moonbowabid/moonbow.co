# Changes — Page by Page

> Task ledger for the staging → production design-parity work. Staging is the design
> target. Companion to `STAGING-VS-PROD-CHANGES.md` and `TODO.md`.
> Last verified 2026-07-31 — full **desktop** Playwright suite green; mobile suite
> previously green. **All staging→prod parity items are done & verified**, and the
> **LOCAL home sync is now done** too (see below). No open items remain.

**Legend:** `PROD` = production · `LOCAL` = `moonbow.local`. ✅ done · ⏳ open.

---

## Completed

### LOCAL — home (post 9172) + footer (post 17) synced to prod ✅ (2026-07-31)
**Oracle correction (important):** the sync must be verified against **prod's LIVE
generated CSS**, *not* the dump. The fresh dump `prod_db_31-07/db_dom880016.sql` (exported
~16:46) was taken **before** a batch of small Elementor edits was saved on prod, so a
`_elementor_data` diff vs the dump wrongly reads "byte-identical / done". Comparing local's
regenerated `post-9172.css` / `post-17.css` against prod's live cache-busted CSS
(`css-diff` rule-by-rule, order-insensitive) is the correct check.

The 5 main homepage container edits (below) were already on local and match prod:

| Section | Element | Setting | Value |
| --- | --- | --- | --- |
| "Why we exist" | `5c33c08` | min-height | 577px ✅ |
| "Premium platform partners" | `692ad68` | min-height | 550px ✅ |
| "01 Plan for retail growth" | `4e70c1a` | margin-top | 90px ✅ |
| "Who we work with" | `b5638ff` | min-height | 144px ✅ |
| "HUMAN + AI" | `d08a64f` | margin-top | −64px ✅ |

**Post-dump prod edits that WERE missing on local** (present in prod-live CSS, absent from
the dump → applied to local `_elementor_data` and regenerated):

| Post | Element | Setting | Applied |
| --- | --- | --- | --- |
| 9172 | Subheading "Any platform…" `b99a11b` | margin-top | 0 → **44px** (bottom kept 20px) |
| 9172 | Premium-partner logo-row `820eb01` | margin-top | unset → **20px** |
| 9172 | Swarovski logo `a555845` | padding-top | unset → **30px** |
| 9172 | AkzoNobel logo `0936422` | padding-top | unset → **30px** |
| 9172 | Euronics logo `ff698b3` | padding-top | unset → **26px** |
| 9172 | LEGO logo `e6958b0` | padding-top | unset → **20px** |
| 9172 | Smyths logo `9dfda87` | padding-top | unset → **20px** |
| 9172 | "Who we work with" logo row `0daee97` | align-items | center → **stretch** |
| 9172 | Logo-row wrapper `9e52f53` | justify-content | unset → **center** |
| 17 | Footer LinkedIn icon `3890cb0` | margin-top | unset → **−9px** |

(Lacoste `293ed26` = 0 top padding on both, no change.) These were **already live on
production** — nothing to deploy there; the gap was local-only (prod's DB was ahead of both
the dump and local). The footer template (post 17) was byte-identical staging↔prod already;
local's copy was an older import missing the −9px.

**How:** patched each `_elementor_data` via `wp eval-file`
(`update_post_meta($pid, …, wp_slash($json))`), then `wp elementor flush-css` +
`wp cache flush`, then rendered the home page to regenerate CSS. **Verified:** local's
regenerated `post-9172.css` and `post-17.css` now match prod-live with **zero real diffs**
(only residual: the `moonbow.local` vs `moonbow.co` domain in a background-image URL, and a
no-op Elementor `--order:99999` generator var). (The `.Ai-text` colour fix ships in git.)

---

## Completed (staging → prod)

### Global
| Task | Status |
| --- | --- |
| Scroll-to-Top button enabled site-wide (all 7 pages) | ✅ done & verified |
| Elementor webfonts served from `moonbow.co` (temp-domain / CORS issue cleared) | ✅ done & verified |
| Nav label casing — production's lowercase is correct; no change | ✅ n/a |
| Mega-menu dropdown panels flagged by the audit — identified as the hidden ElementsKit panels (Services/AI suite/Our work), non-visual | ✅ no action |

### Home — `/` (PROD)
| Task | Status |
| --- | --- |
| "Why we exist" min-height → 577px | ✅ done & verified |
| "Premium platform partners" min-height → 550px | ✅ done & verified |
| "01 Plan for retail growth" margin-top → 90px | ✅ done & verified |
| "Who we work with" min-height → 144px | ✅ done & verified |
| "HUMAN + AI" margin-top → −64px | ✅ done & verified |
| "AI" text colour / stroke fix (theme CSS) | ✅ done & verified |
| "Premium platform partners" vertical spacing — subheading margin-top **44px** (`b99a11b`) + logo-row container margin-top **20px** (`820eb01`) | ✅ done & verified (2026-07-31: content block 326px, gap-below 113px = staging) |

### Interior pages (PROD)
| Page | Task | Status |
| --- | --- | --- |
| Services | Hero top spacing matched to staging | ✅ done & verified |
| AI Suite | Hero top spacing matched to staging | ✅ done & verified |
| Our Work | Hero top spacing matched to staging | ✅ done & verified |
| Careers | Hero top spacing matched to staging | ✅ done & verified |
| Contact us | Hero top spacing matched to staging | ✅ done & verified |
| Contact us | Removed extra space above "Let's work as a team!" — inner hero container margin-top standardised to 0 (no negative CSS) | ✅ done & verified |
| About us | Compared identical to staging | ✅ no change |
| Services (all 11 `/service/*`) | Hero **H1 spacing** matched to staging — `services.css` `.services-title__heading` font-size 66→**45px**, margin-top 152→**40px**, margin-bottom 60→**50px** (removed the 112px extra top gap); version bumped 1.0→1.1 | ✅ done & verified (git `ddc278df`, deployed to prod) |
| AI Suite (all `/ai-suite/*`) | Detail-page margin fix | ✅ done (git `087e6721`) |
| Our Work (all `/case_study/*`) | Detail-page margin fix | ✅ done (git `97da8b2d`) |

### Deep pixel audit (Task 7) & tooling
| Task | Status |
| --- | --- |
| Full per-element CSS audit of all 6 interior pages (`tools/pixel-diff/css-diff.py`) | ✅ done — no page-body diffs |
| Playwright `Interior element parity` guard (Careers + Contact us elements) | ✅ added, green |

### Detail/CPT pages & home-section audit (2026-07-31)
| Page/section | Finding | Status |
| --- | --- | --- |
| `/service/*`, `/ai-suite/*`, `/case_study/*` (all detail pages) | Template-driven (not Elementor docs); `css-diff.py` blind here. Rendered class-skeletons **match** staging — only diff is the shared header mega-menu. Bodies identical. Hero heading spacing now fixed (see Services row above). | ✅ verified match |
| Service hero **video** — `loop` | Staging loops; prod does not (prod = committed template, which dropped `loop`). `poster` differs by ACF content only — prod already has it (desired). | ℹ️ open **decision** (not a bug): add `loop` back to `single-service.php` only if the hero should loop |
| Home — "Who we work with" (real companies) | **Pixel-identical** to staging: same section hash `95cb4e2`; headings `06e3d11` (mTop 140/mBot 20), `b45b699` (mBot 20), `f06bfa8` (0); **logo row `b5638ff`: flex nowrap, justify space-between, column-gap 20px, margin-top 15px, width 1280**; logo sizes Lacoste 123×60, Swarovski 202×28, AkzoNobel 176×26, Euronics 183×32, LEGO 91×91, Smyths 139×46 (max-w 150px); section 572px. | ✅ no action — matches |
| Home — "Let's work as a team!" (Contact us) | 120px gap above heading on **both**; headings top-aligned (the section standardised earlier). | ✅ no action — matches |
| Global footer (LinkedIn + Contact us) | Footer `post-17` byte-identical staging↔prod (Task 4). Any alignment is shared, not a parity gap. | ✅ no action — global/identical |

---

## Reference

```bash
# full desktop suite
cd html/tests && npx playwright test --project=desktop
# re-run the CSS audit
python3 html/tools/pixel-diff/css-diff.py /services/ /ai-suite/ /our-work/ /careers/ /contact-us/ /about-us/
```

**Note:** prod is behind Cloudflare at apex `moonbow.co` — after any edit, purge
Cloudflare + WP/GoDaddy cache and allow ~1 min to propagate before re-checking.
