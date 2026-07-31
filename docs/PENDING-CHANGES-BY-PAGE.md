# Changes — Page by Page

> Task ledger for the staging → production design-parity work. Staging is the design
> target. Companion to `STAGING-VS-PROD-CHANGES.md` and `TODO.md`.
> Last verified 2026-07-31 — full **desktop** Playwright suite green; mobile suite
> previously green. **All staging→prod parity items are done & verified**; the only
> remaining open item is the **LOCAL sync** (out of scope for the staging→prod pass).

**Legend:** `PROD` = production · `LOCAL` = `moonbow.local`. ✅ done · ⏳ open.

---

## Open tasks

### LOCAL — sync the 5 homepage edits to `moonbow.local` ⏳
The homepage Elementor edits were made on prod's **database**, not in git, so
`moonbow.local` still renders the old pre-change values (measured 2026-07-31):

| Section | Setting | Target | Local now |
| --- | --- | --- | --- |
| "Why we exist" | min-height | 577px | 877 |
| "Premium platform partners" | min-height | 550px | unset |
| "01 Plan for retail growth" | margin-top | 90px | 180 |
| "Who we work with" | min-height | 144px | 90 |
| "HUMAN + AI" | margin-top | −64px | 0 |

**How:** either re-import the prod DB via `setup-local.sh` (cleanest — pulls all edits at
once), or re-apply the values in Elementor on local, then regenerate + flush local cache.
(The `.Ai-text` colour fix is already on local — it ships in git.) _Note:_ local now also
lags the two partner-logos margins below (verified 2026-07-31: subheading margin-top 0px on
local vs 44px on prod), so a fresh prod→local DB import is the cleanest catch-up.

---

## Completed

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
