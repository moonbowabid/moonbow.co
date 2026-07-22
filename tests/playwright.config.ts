import { defineConfig, devices } from '@playwright/test';

/**
 * Verifies PRODUCTION against STAGING (the design target) after a deploy.
 *
 *   TARGET  = the site under test        (default: production)
 *   BASELINE = the design target to match (default: staging)
 *
 * Override either with env vars, e.g. to dry-run staging against itself:
 *   TARGET=https://staging.moonbow.co npm test
 */
export const TARGET = process.env.TARGET ?? 'https://www.moonbow.co';
export const BASELINE = process.env.BASELINE ?? 'https://staging.moonbow.co';

export default defineConfig({
  testDir: '.',
  fullyParallel: false,        // stay gentle on the live sites
  workers: 1,
  retries: 1,                  // absorb transient network blips
  reporter: [['list'], ['html', { open: 'never' }]],
  timeout: 45_000,
  expect: { timeout: 10_000 },
  use: {
    ignoreHTTPSErrors: true,
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
    actionTimeout: 15_000,
  },
  projects: [
    {
      name: 'desktop',
      use: { ...devices['Desktop Chrome'], viewport: { width: 1440, height: 900 } },
    },
    {
      name: 'mobile',
      use: { ...devices['iPhone 13'] }, // 390px — exercises the off-canvas menu
    },
  ],
});
