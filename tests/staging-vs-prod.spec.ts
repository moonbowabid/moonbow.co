import { test, expect } from '@playwright/test';
import { TARGET, BASELINE } from './playwright.config';
import {
  PAGES,
  EXPECTED_NAV,
  HOME_BLOCKS,
  HOME_ELEMENT_PARITY,
  navLabels,
  heroTop,
  sectionHeight,
  computedProps,
  hasScrollToTop,
  settle,
  isOurFailure,
  mentionsThirdParty,
  MIRROR_HOST,
  auditLinks,
} from './pages';

/**
 * Verifies the TARGET site (production, after a deploy) against the BASELINE
 * (staging, the design target) — page by page, at desktop (1440px) and mobile
 * (iPhone 13 / 390px). See STAGING-VS-PROD-CHANGES.md for the changes checked.
 *
 * Before the deploy these tests are expected to FAIL (production still shows the
 * old state). After you apply the changes + flush cache, they should go green.
 */
for (const spec of PAGES) {
  test.describe(`${spec.name}  (${spec.path})`, () => {
    // Hero spacing (the margin/spacer edit) + nav presence. Kept independent of
    // the scroll-to-top check below so a margin verdict is never masked by it.
    test('layout matches staging', async ({ page }) => {
      await page.goto(TARGET + spec.path, { waitUntil: 'commit' });
      await settle(page);

      // Nav items present — label CASING is intentionally ignored (production's
      // lowercase "AI suite" / "Our work" is the correct version; staging's
      // Title Case is the mistake, so we must NOT enforce it).
      const labels = (await navLabels(page)).map((l) => l.toLowerCase());
      for (const want of EXPECTED_NAV) {
        expect(labels, `nav labels rendered: [${labels.join(' | ')}]`).toContain(want.toLowerCase());
      }

      // Hero vertical position must match staging (the spacer edit)
      if (spec.heroText && !spec.skipHeroOffset) {
        const targetTop = await heroTop(page, spec.heroText.source);
        expect(targetTop, `hero "${spec.heroText.source}" located on target`).toBeGreaterThan(0);

        const basePage = await page.context().newPage(); // inherits desktop/mobile emulation
        await basePage.goto(BASELINE + spec.path, { waitUntil: 'commit' });
        await settle(basePage);
        const baseTop = await heroTop(basePage, spec.heroText.source);
        await basePage.close();

        expect(baseTop, 'hero located on staging baseline').toBeGreaterThan(0);
        expect(
          Math.abs(targetTop - baseTop),
          `hero top: target=${targetTop}px vs staging=${baseTop}px (should match)`,
        ).toBeLessThanOrEqual(12);
      }
    });

    // G2 — scroll-to-top button (separate so it doesn't mask the layout verdict)
    test('scroll-to-top button present', async ({ page }) => {
      await page.goto(TARGET + spec.path, { waitUntil: 'commit' });
      await settle(page);
      expect(await hasScrollToTop(page), 'HFE scroll-to-top button present').toBeTruthy();
    });

    test('buttons/links use the main site domain', async ({ page }) => {
      await page.goto(TARGET + spec.path, { waitUntil: 'commit' });
      await settle(page);
      const audit = await auditLinks(page);

      // (a) no link anywhere may point at a GoDaddy temp/mirror domain
      expect(
        audit.mirror,
        `links to a temp/mirror domain (must be 0): ${audit.mirror.map((m) => `"${m.text}"→${m.host}`).join(' | ') || 'none'}`,
      ).toHaveLength(0);

      // (b) every Elementor button must resolve to the site's own domain
      //     (audit.loc) — relative links and allowed externals are fine
      expect(
        audit.offDomainButtons,
        `buttons not on ${audit.loc} (must be 0): ${audit.offDomainButtons.map((m) => `"${m.text}"→${m.host}`).join(' | ') || 'none'}`,
      ).toHaveLength(0);

      // (c) no on-site link may have a malformed // path (URL-replace artifact)
      expect(
        audit.malformed,
        `on-site links with a malformed "//" path (must be 0): ${audit.malformed.map((m) => m.href).join(' | ') || 'none'}`,
      ).toHaveLength(0);
    });

    test('no console errors or broken assets', async ({ page }) => {
      const consoleErrors: string[] = [];
      const badResponses: string[] = [];
      page.on('console', (m) => {
        // genuine first-party JS errors only. Excluded:
        //  - third-party / mirror-domain noise (e.g. CORS font blocks) → mirror-asset test
        //  - generic "Failed to load resource" (no URL, and often a transient
        //    cross-origin / CDN-warm 404) → the response-status check below catches
        //    real on-domain broken assets, with the actual URL.
        const text = m.text();
        if (
          m.type() === 'error' &&
          isOurFailure(m.location().url || TARGET) &&
          !mentionsThirdParty(text) &&
          !/failed to load resource/i.test(text)
        ) {
          consoleErrors.push(text);
        }
      });
      page.on('response', (r) => {
        if (r.status() >= 400 && isOurFailure(r.url())) {
          badResponses.push(`${r.status()} ${r.url()}`);
        }
      });

      await page.goto(TARGET + spec.path, { waitUntil: 'commit' });
      await settle(page);

      expect(consoleErrors, `console errors: ${consoleErrors.join(' ; ') || 'none'}`).toHaveLength(0);
      expect(badResponses, `broken requests: ${badResponses.join(' ; ') || 'none'}`).toHaveLength(0);
    });

    // No production asset (font, css, image, script) may reference a GoDaddy
    // temp/mirror domain. Elementor bakes the temp host into generated font CSS,
    // which then CORS-fails on the real domain — regenerate Files & Data to fix.
    // Two signals: (a) any request observed to a mirror host, and (b) a mirror URL
    // baked into a same-origin stylesheet (deterministic — not cache-dependent).
    test('no assets loaded from a temp/mirror domain', async ({ page }) => {
      const requested = new Set<string>();
      page.on('request', (req) => {
        if (MIRROR_HOST.test(req.url())) requested.add(req.url());
      });

      await page.goto(TARGET + spec.path, { waitUntil: 'commit' });
      await settle(page);

      // Scan the content of every same-origin stylesheet (e.g. Elementor's
      // google-fonts CSS) for a baked-in mirror-domain URL. This catches the
      // font-CORS issue even when the .woff2 was served from cache.
      const cssRefs: string[] = await page.evaluate(async () => {
        const links = Array.from(
          document.querySelectorAll('link[rel="stylesheet"]'),
        ) as HTMLLinkElement[];
        const sameOrigin = links.filter((l) => {
          try { return new URL(l.href).host === location.host; } catch { return false; }
        });
        const hits: string[] = [];
        for (const l of sameOrigin) {
          try {
            const css = await fetch(l.href).then((r) => r.text());
            const m = css.match(/https?:\/\/[^/"')]*myftpupload\.com[^"')]*/gi);
            if (m) hits.push(`${l.href} → ${[...new Set(m)].join(', ')}`);
          } catch { /* ignore unreadable sheets */ }
        }
        return hits;
      });

      const offenders = [...new Set([...requested, ...cssRefs])];
      expect(
        offenders,
        `temp/mirror-domain references (must be 0): ${offenders.join(' ; ') || 'none'}`,
      ).toHaveLength(0);
    });

    test('capture screenshots @screenshot', async ({ page }, testInfo) => {
      for (const [tag, base] of [['target', TARGET], ['staging', BASELINE]] as const) {
        await page.goto(base + spec.path, { waitUntil: 'commit' });
        await settle(page);
        const file = testInfo.outputPath(`${spec.name}-${tag}-${testInfo.project.name}.png`);
        await page.screenshot({ path: file, fullPage: true });
        await testInfo.attach(`${spec.name} · ${tag} · ${testInfo.project.name}`, {
          path: file,
          contentType: 'image/png',
        });
      }
    });
  });
}

/**
 * Home-page block spacing — production must match staging on the deliberate
 * per-block heights (STAGING-VS-PROD-CHANGES.md § Home). The main loop skips the
 * hero-offset check for Home (skipHeroOffset), so these blocks are checked here.
 * Desktop only: the measurements were taken at 1440px and mobile min-heights
 * reflow (add mobile once measured).
 */
test.describe('Home block spacing  (/)', () => {
  for (const blk of HOME_BLOCKS) {
    test(`"${blk.label}" block height matches staging`, async ({ page }, testInfo) => {
      test.skip(testInfo.project.name !== 'desktop', 'measured at desktop 1440px only');

      await page.goto(TARGET + '/', { waitUntil: 'commit' });
      await settle(page);
      const targetH = await sectionHeight(page, blk.heading);
      expect(targetH, `"${blk.label}" section located on target`).toBeGreaterThan(0);

      const basePage = await page.context().newPage(); // inherits desktop emulation
      await basePage.goto(BASELINE + '/', { waitUntil: 'commit' });
      await settle(basePage);
      const baseH = await sectionHeight(basePage, blk.heading);
      await basePage.close();

      expect(baseH, `"${blk.label}" located on staging baseline`).toBeGreaterThan(0);
      expect(
        Math.abs(targetH - baseH),
        `"${blk.label}" height: target=${targetH}px vs staging=${baseH}px (should match)`,
      ).toBeLessThanOrEqual(12);
    });
  }
});

/**
 * Home element-level pixel parity — the deep-audit differences (container margins /
 * min-height) that block-height checks miss. Compares each element's live computed
 * style on production against staging (the design target). Fails until the three
 * Elementor edits are deployed, then guards against regression.
 * Desktop only: the values were measured at 1440px.
 */
test.describe('Home element parity  (deep pixel audit)', () => {
  for (const el of HOME_ELEMENT_PARITY) {
    test(`${el.label} matches staging`, async ({ page }, testInfo) => {
      test.skip(testInfo.project.name !== 'desktop', 'measured at desktop 1440px only');

      await page.goto(TARGET + '/', { waitUntil: 'commit' });
      await settle(page);
      const targetV = await computedProps(page, el.hash, el.props);
      expect(targetV, `element ${el.hash} found on target`).not.toBeNull();

      const basePage = await page.context().newPage(); // inherits desktop emulation
      await basePage.goto(BASELINE + '/', { waitUntil: 'commit' });
      await settle(basePage);
      const baseV = await computedProps(basePage, el.hash, el.props);
      await basePage.close();

      expect(baseV, `element ${el.hash} found on staging`).not.toBeNull();
      const num = (s: string) => parseFloat(s) || 0;
      for (const p of el.props) {
        expect(
          Math.abs(num(targetV![p]) - num(baseV![p])),
          `${el.label} · ${p}: target=${targetV![p]} vs staging=${baseV![p]} (should match)`,
        ).toBeLessThanOrEqual(1);
      }
    });
  }
});

/** Mobile-only smoke: the off-canvas menu still opens (guards the mobile work). */
test.describe('Mobile off-canvas menu', () => {
  test('hamburger opens the menu', async ({ page }, testInfo) => {
    test.skip(testInfo.project.name !== 'mobile', 'mobile project only');
    await page.goto(TARGET + '/', { waitUntil: 'commit' });
    await settle(page);
    const toggle = page.locator('.elementskit-menu-hamburger, .elementskit-menu-toggler').first();
    await expect(toggle, 'hamburger toggle visible').toBeVisible();
    await toggle.click();
    await expect(
      page.locator('.elementskit-navbar-nav-default.elementskit-menu-container').first(),
      'off-canvas panel opens',
    ).toBeVisible();
  });
});
