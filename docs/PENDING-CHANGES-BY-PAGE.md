# Changes — Page by Page

> Task ledger for the staging → production design-parity work. Staging is the design
> target. Companion to `STAGING-VS-PROD-CHANGES.md` and `TODO.md`.
> Last verified 2026-07-31 — full **desktop** Playwright suite green; mobile suite
> previously green.

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
once), or re-apply the 5 values in Elementor on local, then regenerate + flush local
cache. (The `.Ai-text` colour fix is already on local — it ships in git.)

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

### Deep pixel audit (Task 7) & tooling
| Task | Status |
| --- | --- |
| Full per-element CSS audit of all 6 interior pages (`tools/pixel-diff/css-diff.py`) | ✅ done — no page-body diffs |
| Playwright `Interior element parity` guard (Careers + Contact us elements) | ✅ added, green |

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
