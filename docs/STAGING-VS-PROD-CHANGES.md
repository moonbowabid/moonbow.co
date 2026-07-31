# Staging → Production: Page-wise Design Changes

> **Goal:** bring production (`moonbow.co`) in line with staging (the design target).
> **All listed production changes are deployed & verified** — desktop (1440px) and
> mobile (390px) `html/tests` Playwright suites green.
>
> Companion ledger with the per-element values: `PENDING-CHANGES-BY-PAGE.md`.
> The only remaining item is the **LOCAL home sync** (tracked in that ledger).

---

## Status — all production changes done

| Scope            | Change                                                                                             | Status                                                                                                                                                                                |
| ---------------- | -------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Global           | Scroll-to-Top button enabled site-wide (G2)                                                        | ✅ done & verified                                                                                                                                                                    |
| Global           | Elementor webfonts served from`moonbow.co` — temp-domain/CORS cleared (G3)                      | ✅ done & verified                                                                                                                                                                    |
| Global           | Nav label casing — prod lowercase (`AI suite`, `Our work`) is correct                         | ✅ no change                                                                                                                                                                          |
| Global           | Header mega menu — identical ElementsKit mega-menu on both                                        | ✅ no change                                                                                                                                                                          |
| Home             | "Why we exist" min-height →**577px**                                                        | ✅ done & verified                                                                                                                                                                    |
| Home             | "Premium platform partners" min-height →**550px**                                           | ✅ done & verified                                                                                                                                                                    |
| Home             | "01 Plan for retail growth" margin-top →**90px**                                            | ✅ done & verified                                                                                                                                                                    |
| Home             | "Who we work with" min-height →**144px**                                                    | ✅ done & verified                                                                                                                                                                    |
| Home             | "HUMAN + AI" margin-top →**−64px**                                                         | ✅ done & verified                                                                                                                                                                    |
| Home             | "AI" text colour / stroke fix (theme CSS)                                                          | ✅ done & verified                                                                                                                                                                    |
| Home             | "What we do" / "HUMAN" block ~+90px taller on prod                                                 | ✅ resolved — not a spacing setting (font/content render); the block's vertical alignment is already handled by the "HUMAN + AI" margin-top −64px above. Verified matching staging. |
| Services         | Hero top spacing matched to staging                                                                | ✅ done & verified                                                                                                                                                                    |
| AI Suite         | Hero top spacing matched to staging                                                                | ✅ done & verified                                                                                                                                                                    |
| Our Work         | Hero top spacing matched to staging                                                                | ✅ done & verified                                                                                                                                                                    |
| Careers          | Hero top spacing matched to staging                                                                | ✅ done & verified                                                                                                                                                                    |
| Contact us       | Hero top spacing matched to staging                                                                | ✅ done & verified                                                                                                                                                                    |
| Contact us       | Extra space above "Let's work as a team!" — inner hero margin-top →**0** (no negative CSS) | ✅ done & verified                                                                                                                                                                    |
| About us         | Compared identical to staging                                                                      | ✅ no change                                                                                                                                                                          |
| Interior 6 pages | Deep pixel audit (Task 7) — no further page-body diffs                                            | ✅ done                                                                                                                                                                               |

---

## Notes (non-obvious re-deploy details)

- **G2 · Scroll-to-Top** — an HFE **extension**, not a footer widget. Re-deploy: WP Admin
  → UAE → Widgets → "Scroll to Top" **ON**, then Elementor → Site Settings → Scroll to
  Top → **Enable = Yes** (default styling), then Regenerate Files & Data + purge all
  caches. Guarded by the `scroll-to-top button present` test.
- **G3 · Webfonts** — the generated google-fonts CSS once pointed `@font-face src` at a
  GoDaddy temp host (CORS-blocked). Fix: Regenerate Files & Data (or search-replace the
  temp host → `moonbow.co`) + purge. Guarded by `no assets loaded from a temp/mirror domain`.
- **Mega-menu panels** — the audit's cross-page "structural" noise is the hidden
  ElementsKit dropdown panels (Services / AI suite / Our work); non-visual. Per-environment
  panel IDs are in `PENDING-CHANGES-BY-PAGE.md`.

---

## Verification & scope

- **Desktop** (1440px) `html/tests` suite: green. **Mobile** (390px, iPhone 13): green
  (hero margins, scroll-to-top, button/link domains, console/asset checks, off-canvas menu).
- Covers the homepage + all top-nav pages. Interior/detail pages (individual service,
  case-study, help-centre) were **not** audited — compare separately if restyled on staging.

> Always flush the WordPress cache + GoDaddy/Cloudflare CDN after any production edit.
