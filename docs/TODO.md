# Moonbow — To-Do List

> Serial task list, picked up one by one. Now also a living status log — each
> task records what was done and what's still open. Detailed analysis for a task
> happens when we start it.

**Related docs (in the repo):**
- `html/docs/STAGING-VS-PROD-CHANGES.md` — staging→prod change status record (Tasks 4–5, 7).
- `html/docs/PENDING-CHANGES-BY-PAGE.md` — page-wise task ledger (all items complete, incl. LOCAL home sync).
- `html/tests/` — committed Playwright suite that verifies prod against staging.
- `DEPLOY-mega-menu.md` (project root) — earlier manual deploy notes (reference only).

---

## 1. ✅ Initialize git & push code — excluding `moonbow-zahid` — DONE (2026-07-22)
- Repo initialized **inside `html/`** on branch `main`.
- Scope: **`wp-content` only** (themes `moonbow` + `hello-elementor`, all plugins, `mu-plugins`). WP core, uploads, backups, `.env`, `wp-config.php`, and `moonbow-zahid` are gitignored.
- `.gitignore` updated: added WP-core exclusions + `moonbow-zahid`; removed blanket `vendor/`/`dist/`/`build/` ignores (they are shipped runtime code in bundled plugins).
- First commit `4226f47` (16,221 files) pushed → https://github.com/moonbowabid/moonbow.co.git

## 2. ✅ Compare & merge `moonbow-zahid` → `moonbow` — DONE (2026-07-22)
- `moonbow-zahid` was an older v1.0 snapshot; `moonbow` already superseded it in `custome-style.css`, `index.js`, and `functions.php`.
- Only genuine change folded in: footer subscribe submit button (`#gform_submit_button_3`) border purple→white in `style.css`. Version bumped 1.1.8→1.1.9.
- Commit `e59e558e` pushed. `moonbow-zahid` folder kept on disk (git-ignored) per request.

## 3. ⏳ Set up the collaboration + deployment pipeline — NOT STARTED
- Branching/PR workflow for working with the other developer.
- Automated (or documented) deploy path to **staging** and **production**.
- *Decide when we start:* how we currently reach staging/prod (SFTP / SSH / host git integration) and what tooling to use.

## 4. ✅ Compare staging vs production → list staging-only changes — DONE (2026-07-22)
Full page-wise comparison in `STAGING-VS-PROD-CHANGES.md`; verified visually in-browser and codified in `html/tests` (commit `466d466b`, extended in `eeb3107e`).

**Verified front-end differences (staging vs prod):**
1. **Hero top spacer** — interior pages (Services, AI Suite, Our Work, Careers, Contact us) had a ~58–90px larger top spacer on prod. → deploy (see Task 5, now done).
2. **Scroll-to-Top button** — enabled on staging only (HFE/UAE extension). → deploy (Task 5, pending).
3. **Homepage block spacing** — the two home pages have **converged in structure** (same 10 sections, same Elementor IDs; the earlier "different page 9628/9172 + missing *for* word" note is stale). But prod renders two blocks **taller** than staging: **"Why we exist"** (section min-height 877px vs 577px, **+300px**) and **"We are premium platform partners"** (staging pins 550px min-height, prod grows to 635px, **+85px**). "What we do"/"HUMAN" are ~+90px but from content-render, not a spacing setting — **resolved** (the block's vertical alignment is handled by the "HUMAN + AI" margin-top −64px; verified matching staging). → all deployed (Task 5); see `STAGING-VS-PROD-CHANGES.md` § Home.

**Explicitly NOT changes:**
- **Nav label casing** — production's lowercase `AI suite` / `Our work` is CORRECT; staging's Title Case is the mistake. Do not carry it over.
- **No global restyle** — Elementor kit `post-11.css` (fonts, colours, weights, container widths 1440/1024/767, spacing) and footer `post-17.css` are **identical** on both. Theme CSS/JS byte-identical (both 1.1.8). About us page identical.
- The earlier HTTP-only pass wrongly reported "staging mega-menu vs prod flat 7-item menu" — an HTML-parse artifact. **Both sites have the identical ElementsKit mega-menu** (verified by hovering the dropdowns). There is no menu migration to do.

**Env-only (never deploy):** staging cache-busts assets with a global timestamp vs prod version headers; staging `noindex` vs prod `index`. The extra template docs loaded on every page (prod `9530/9535/9539`, staging `9780/9784/9788`) are the hidden **ElementsKit mega-menu panels** (Services/AI suite/Our work) — non-visual, not a staging change.

**Cross-finding → Task 6:** the `CPT-Jobs` plugin runs on both live sites but is **missing locally** (so it was absent from the Task 1 push too).

## 5. ✅ Deploy the listed changes to production — DONE & VERIFIED (desktop + mobile green)
Verified via `html/tests` (desktop 1440px + mobile 390px, against the staging baseline):
- ✅ **Hero spacing (margins) — DONE & VERIFIED (2026-07-22):** Services, AI Suite, Our Work, Careers, Contact us all match staging; About us needed no change. `layout matches staging` green on every page — **desktop (1440px) and mobile (390px, iPhone 13)**. Full mobile suite also green (scroll-to-top, button domains, console/asset checks, off-canvas menu).
- ✅ **Scroll-to-Top button (G2) — DONE & VERIFIED (2026-07-22):** now present on all 7 prod pages (`scroll-to-top button present` test green). Enabled via UAE → Widgets + Elementor Site Settings, per §G2.
- ✅ **Homepage block spacing — DONE & VERIFIED (2026-07-22):**
  - **"Why we exist"** — Min Height reduced 877→577px on prod; now = staging.
  - **"Premium platform partners"** — Min Height set to 550px on prod; now = staging. (An earlier 342px reading was stale CDN cache before the edit propagated.)
  - Both block-spacing tests green. ℹ️ "What we do"/"HUMAN" +90px is content-render, no action.
- ✅ **Elementor webfonts (G3) — RESOLVED & VERIFIED (2026-07-22):** briefly appeared to reference `6g0.840.myftpupload.com` (CORS-blocked), but that was **stale GoDaddy CDN cache**; after the purge propagated both `roboto.css`/`robotoslab.css` reference `moonbow.co` only. New `no assets loaded from a temp/mirror domain` test green on all 7 pages (kept as a permanent regression guard).
- ℹ️ **Button URLs** — staging's `1nn.562.myftpupload.com` links were replaced with `staging.moonbow.co` (introduced a `//` double-slash the link-audit test now catches — needs a `moonbow.co//`→`moonbow.co/` cleanup on staging). Production button-domain audit **now run** — passes (no off-domain/mirror button links on prod).

## 6. ✅ Compare staging → local to catch anything missing — DONE (2026-07-22)
Compared staging's plugin list (WP admin) against the local filesystem and git.
- ✅ **Custom code parity:** local now has everything staging has, incl. `CPT-Jobs` — the old "missing locally" note was stale; it's present with full content.
- ✅ **Git gap closed:** 3 plugins existed on staging + local but were **untracked** in the repo (added after the initial commit, not gitignored). Committed in `0f6642f6`:
  - `CPT-Jobs` (Career Jobs, custom CPT)
  - `CPT-case-studies` (Case Studies, custom CPT)
  - `clear-cache-for-widgets` (Clear Cache For Me v2.5)
- ℹ️ **Staging-only:** `wordpress-importer` (standard WP.org import utility) is on staging but not local — not custom code, not worth pulling down.
- ℹ️ **Local-only (expected):** `copy-delete-posts` and `wordpress-mcp` are dev/utility plugins, correctly absent from staging.
- ✅ **`setup-local.sh` already handles the temp domain:** `PRODUCTION_URL="https://1nn.562.myftpupload.com"` and the search-replace step (incl. the `http://` variant) rewrites it to the local URL on import. The earlier manual `wp search-replace … → moonbow.local` (2,193 replacements) was a one-off; future imports are covered automatically.

## 7. ✅ Deep pixel audit — remaining 6 pages — DONE (2026-07-31)
Ran `css-diff.py` on all 6 interior pages. **No prod deploys needed** — the pages already
match staging. One real fix surfaced: **Contact us** had extra space above "Let's work as
a team!" (inner hero container carried a −76px top margin on staging); resolved on staging
by standardising it to **0** (no negative CSS), so both now match. Added an
`Interior element parity` guard (desktop, green). The cross-page STRUCTURAL hashes are the
hidden **ElementsKit mega-menu panels** (Services/AI suite/Our work) — non-visual.
Logged in `STAGING-VS-PROD-CHANGES.md` + `PENDING-CHANGES-BY-PAGE.md`.

**LOCAL sync — DONE (2026-07-31).** `moonbow.local` home (post 9172) + footer (post 17) now
match **prod-live CSS** with zero real diffs. ⚠️ Lesson: verify local against prod's **live**
generated CSS, not the dump — the fresh dump (`prod_db_31-07`) was exported *before* a batch
of small prod Elementor edits, so a dump-vs-local `_elementor_data` diff falsely read
"byte-identical". Post-dump edits synced onto local: partner-logo margins (`b99a11b` mt 44,
`820eb01` mt 20), 5 real-company logo top-paddings (Swarovski/AkzoNobel 30, Euronics 26,
LEGO/Smyths 20), logo-row `0daee97` align-items→stretch + wrapper `9e52f53` justify→center,
and footer LinkedIn `3890cb0` margin-top −9px. All were already live on prod (nothing to
deploy there). Applied via `wp eval-file` + Elementor CSS regenerate. See
`PENDING-CHANGES-BY-PAGE.md` for the full element table.

<details><summary>Original plan (reference)</summary>

Home is **done**: fully audited with `tools/pixel-diff/`, 3 container diffs deployed to
prod (`4e70c1a` margin-top 90px, `b5638ff` min-height 144px, `d08a64f` margin top −64px
only), verified green by Playwright `deep pixel audit` + cache-busted `css-diff.py`
(commits `a8c378cd`, `c69562bd`). Premium-partners section is rebuilt but renders
identically — no action.

**Next: run the same audit on the other 6 pages** (staging = design target):
```bash
python3 html/tools/pixel-diff/css-diff.py /services/ /ai-suite/ /our-work/ /careers/ /contact-us/ /about-us/
```
For each page:
1. Read the **REAL** (deploy-worthy) and **VAR-only** (usually real spacing) buckets;
   ignore no-effect vars (`--min-height:0` vs unset, `--border-radius:0 0 0 0` vs unset).
2. **STRUCTURAL** hashes = a rebuilt/added/removed element → verify with
   `tools/pixel-diff/extract.js` (`pdSection`, `pdForceLoadImages`) before assuming a diff.
3. Log findings per page in `STAGING-VS-PROD-CHANGES.md`, apply on prod (Elementor),
   add a parity test (`HOME_ELEMENT_PARITY` pattern in `tests/`), flush WP + Cloudflare.

**Reminders (see [[pixel-diff-tooling]] memory):** prod is behind **Cloudflare at apex
`moonbow.co`** (www 301s to apex); always cache-bust; watch the lazy-load 0×0 image trap;
after any prod edit, cache propagation can take a minute (Playwright may read stale edge
while `css-diff.py` reads fresh — re-run after flush).

</details>

---

## Notes
- This file and `STAGING-VS-PROD-CHANGES.md` live in **`html/docs/`** and are tracked in the repo.
- Always finish a production change by flushing WP + the GoDaddy CDN cache.
- `html/tools/pixel-diff/` — reusable staging↔prod styling diff (Task 4–7). See its README.
