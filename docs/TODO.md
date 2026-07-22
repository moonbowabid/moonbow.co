# Moonbow — To-Do List

> Serial task list, picked up one by one. Now also a living status log — each
> task records what was done and what's still open. Detailed analysis for a task
> happens when we start it.

**Related docs (in the repo):**
- `html/docs/STAGING-VS-PROD-CHANGES.md` — page-wise staging→prod change list + Elementor/CSS how-to (Tasks 4–5).
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
3. **Homepage** — a *different* Elementor page (staging `9628` vs prod `9172`) and **diverged**: prod home has a script "for" word + hero background image that staging lacks. → deferred; confirm intent before promoting.

**Explicitly NOT changes:**
- **Nav label casing** — production's lowercase `AI suite` / `Our work` is CORRECT; staging's Title Case is the mistake. Do not carry it over.
- **No global restyle** — Elementor kit `post-11.css` (fonts, colours, weights, container widths 1440/1024/767, spacing) and footer `post-17.css` are **identical** on both. Theme CSS/JS byte-identical (both 1.1.8). About us page identical.
- The earlier HTTP-only pass wrongly reported "staging mega-menu vs prod flat 7-item menu" — an HTML-parse artifact. **Both sites have the identical ElementsKit mega-menu** (verified by hovering the dropdowns). There is no menu migration to do.

**Env-only (never deploy):** staging cache-busts assets with a global timestamp vs prod version headers; staging `noindex` vs prod `index`. Prod home also embeds an extra template `9530` (old-homepage section) — not a staging change.

**Cross-finding → Task 6:** the `CPT-Jobs` plugin runs on both live sites but is **missing locally** (so it was absent from the Task 1 push too).

## 5. 🔄 Deploy the listed changes to production — IN PROGRESS
Verified via `html/tests` (desktop 1440px, against the staging baseline):
- ✅ **Hero spacing (margins) — DONE & VERIFIED (2026-07-22):** Services, AI Suite, Our Work, Careers, Contact us all match staging; About us needed no change. `layout matches staging` green on every page. *(Mobile 390px not yet re-verified.)*
- ⏳ **Scroll-to-Top button (G2)** — not enabled on prod yet (its own test is red). Verified enable steps in `STAGING-VS-PROD-CHANGES.md` §G2 (UAE → Widgets → enable extension; Elementor Site Settings → Scroll to Top = Yes; then Regenerate Files & Data + purge all caches — the cache purge is the step that makes it appear on every page, not just the homepage).
- ⏭️ **Homepage (page 9628)** — intentionally deferred.
- ℹ️ **Button URLs** — staging's `1nn.562.myftpupload.com` links were replaced with `staging.moonbow.co` (introduced a `//` double-slash the link-audit test now catches — needs a `moonbow.co//`→`moonbow.co/` cleanup on staging). **Production button-domain audit not yet run.**

## 6. ⏳ Compare staging → local to catch anything missing — NOT STARTED (some findings already)
- **`CPT-Jobs` plugin** present on staging + prod but **missing locally / in git** — pull it down and add to the repo.
- **Local URL fix already done (2026-07-22):** local DB had `1nn.562.myftpupload.com` baked into Elementor links; ran `wp search-replace … → moonbow.local` (2,193 replacements, guid skipped) + cache flush. **Follow-up:** add `1nn.562.myftpupload.com` to `setup-local.sh`'s search-replace so future imports handle it automatically.
- Otherwise verify local isn't missing anything present on staging (files + relevant config).

---

## Notes
- This file and `STAGING-VS-PROD-CHANGES.md` live in **`html/docs/`** and are tracked in the repo.
- Always finish a production change by flushing WP + the GoDaddy CDN cache.
