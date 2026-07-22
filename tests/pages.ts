import type { Page } from '@playwright/test';

/** A page to verify, and which checks apply to it. */
export interface PageSpec {
  name: string;
  path: string;
  /** Hero heading text — used to measure vertical position vs the baseline. */
  heroText?: RegExp;
  /** Skip the hero-offset check (e.g. the redesigned home page). */
  skipHeroOffset?: boolean;
}

export const PAGES: PageSpec[] = [
  { name: 'Home',       path: '/',            skipHeroOffset: true },
  { name: 'Services',   path: '/services/',   heroText: /Know every move your AI makes/i },
  { name: 'AI Suite',   path: '/ai-suite/',   heroText: /Make your website AI agent ready/i },
  { name: 'Our Work',   path: '/our-work/',   heroText: /Case studies/i },
  { name: 'Careers',    path: '/careers/',    heroText: /Grow with us/i },
  { name: 'Contact us', path: '/contact-us/', heroText: /Let.?s work as a team/i },
  { name: 'About us',   path: '/about-us/',   heroText: /More than an agency/i },
];

/** Top-level nav sections that must be present (compared case-INSENSITIVELY —
 *  label casing is not enforced; production's lowercase is the correct version). */
export const EXPECTED_NAV = ['Services', 'AI suite', 'Our work', 'About us'];

/** Distinct top-level nav labels currently rendered. */
export async function navLabels(page: Page): Promise<string[]> {
  return page.evaluate(() => {
    const links = Array.from(
      document.querySelectorAll('.elementskit-navbar-nav > li > a'),
    ) as HTMLElement[];
    // first text node only, so we don't pick up dropdown children
    const labels = links
      .map((a) => (a.childNodes[0]?.textContent ?? a.textContent ?? '').trim())
      .filter(Boolean);
    return [...new Set(labels)];
  });
}

/** Vertical position (px from top of document) of the hero heading, or -1. */
export async function heroTop(page: Page, source: string): Promise<number> {
  return page.evaluate((src) => {
    const rx = new RegExp(src, 'i');
    const els = Array.from(
      document.querySelectorAll('h1, h2, .elementor-heading-title'),
    ) as HTMLElement[];
    // collapse whitespace so multi-line headings still match a single-space regex
    const norm = (s: string) => s.replace(/\s+/g, ' ').trim();
    const h = els.find((e) => rx.test(norm(e.textContent ?? '')));
    if (!h) return -1;
    return Math.round(h.getBoundingClientRect().top + window.scrollY);
  }, source);
}

/** True if the Header-Footer-Elementor scroll-to-top element is present (G2). */
export async function hasScrollToTop(page: Page): Promise<boolean> {
  return (await page.locator('.hfe-scroll-to-top-wrap').count()) > 0;
}

/** Wait for the page + Elementor lazy sections to settle. */
export async function settle(page: Page): Promise<void> {
  await page.waitForLoadState('domcontentloaded');
  await page.waitForLoadState('networkidle').catch(() => {});
  await page.waitForTimeout(1200); // ElementsKit / lazy sections
}

/**
 * Ignore third-party console/network noise we don't control (maps, recaptcha,
 * analytics, fonts, etc.) so a real regression on the site's own assets isn't
 * masked. Only failures involving moonbow.co are treated as ours.
 */
const THIRD_PARTY = /google|gstatic|recaptcha|gtag|facebook|doubleclick|hotjar|clarity|maps|fonts|cookieyes|myftpupload/i;

export function isOurFailure(url: string): boolean {
  return /moonbow\.co/i.test(url) && !THIRD_PARTY.test(url);
}
