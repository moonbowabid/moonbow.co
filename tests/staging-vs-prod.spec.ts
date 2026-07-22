import { test, expect } from '@playwright/test';
import { TARGET, BASELINE } from './playwright.config';
import {
  PAGES,
  EXPECTED_NAV,
  navLabels,
  heroTop,
  hasScrollToTop,
  settle,
  isOurFailure,
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

      // G2 — scroll-to-top button present
      expect(await hasScrollToTop(page), 'HFE scroll-to-top button present').toBeTruthy();

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

    test('no console errors or broken assets', async ({ page }) => {
      const consoleErrors: string[] = [];
      const badResponses: string[] = [];
      page.on('console', (m) => {
        if (m.type() === 'error' && isOurFailure(m.location().url || TARGET)) {
          consoleErrors.push(m.text());
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
