# GoDaddy Launch — Release Process

## Overview

GoDaddy Launch is a WordPress plugin that ships as a Composer dependency of
[wp-paas-system-plugin](https://github.com/gdcorp-wordpress/wp-paas-system-plugin),
the central plugin package for GoDaddy's WordPress hosting platform.

Releasing a new version means tagging this repo and then updating
wp-paas-system-plugin to pull the new tag. Most of this is automated.

### End-to-End Flow

```
Developer                    GitHub                      wp-paas-system-plugin
─────────                    ──────                      ─────────────────────
npm version patch/minor/major
  ├─ update-version.js
  │   updates 3 version files
  ├─ wp-scripts build
  │   compiles JS/CSS into build/
  ├─ rm -rf vendor
  ├─ composer dump-autoload --optimize
  ├─ git add -A
  └─ (npm creates tag, no v prefix)

postversion hook
  └─ git push && git push --tags

                             Create GitHub Release ──────► release.yml triggers
                             (manual, from tag)            ├─ checkout wp-paas-system-plugin:develop
                                                           ├─ update composer.json ref
                                                           ├─ composer update
                                                           ├─ create branch release-godaddy-launch-{version}
                                                           └─ open PR → wpex team reviews
```

---

## Prerequisites

Before cutting a release you need:

- **Push access** to the `master` branch of this repo
- **Node.js 18** (see `.nvmrc`)
- **PHP 8.0+** with Composer installed
- **npm and Composer dependencies installed** (`npm install` and `composer install`)
- **GitHub Release permissions** on this repo (for the manual step that triggers the workflow)
- **Familiarity with semver** — when to use patch vs minor vs major

The `GDCORP_WORDPRESS_TOKEN` secret used by the release workflow is already
configured in the repo's GitHub Actions settings. Individual developers do not
need it locally.

---

## Version Management

The version number lives in three places:

| File | Location |
|------|----------|
| `package.json` | `"version": "X.Y.Z"` |
| `godaddy-launch.php` | Plugin header `Version: X.Y.Z` |
| `includes/Application.php` | `const VERSION = 'X.Y.Z';` |

### How versions stay in sync

Running `npm version [major|minor|patch]` triggers the following chain:

1. npm bumps the version in `package.json` (built-in npm behavior)
2. The `version` lifecycle script fires (defined in `package.json`):
   - `.dev/bin/update-version.js` reads the new version from `package.json` and
     writes it into `godaddy-launch.php` and `Application.php` via regex replace
   - `wp-scripts build` compiles frontend assets
   - `rm -rf vendor` removes the vendor directory
   - `composer dump-autoload --optimize` regenerates an optimized autoloader
   - `git add -A` stages all changes
3. npm creates the git tag — with **no `v` prefix** (configured via
   `tag-version-prefix=""` in `.npmrc`). Tags are `2.15.0`, not `v2.15.0`.
4. The `postversion` hook runs `git push && git push --tags`

---

## Step-by-Step Release Guide

1. **Ensure you are on `master`** with a clean working tree

2. **Run the version bump:**
   ```bash
   npm version patch   # or minor, or major
   ```
   This automatically updates version files, builds, commits, tags, and pushes.

3. **Create a GitHub Release:**
   - Go to [Releases → New Release](../../releases/new)
   - Select the tag that was just pushed (e.g. `2.15.0`)
   - Add release notes describing what changed
   - Click **Publish release**

4. **The `release.yml` workflow fires automatically** and creates a PR in
   wp-paas-system-plugin (see next section for details).

5. **Monitor the PR** — the `@gdcorp-wordpress/wpex` team is auto-assigned as
   reviewers.

6. **PR is reviewed and merged** into `wp-paas-system-plugin:develop`.

---

## Automated Workflow Deep Dive

The release automation lives in `.github/workflows/release.yml`.

**Trigger:** `release: types: [published]` — fires when a GitHub Release is
published. Pushing a tag alone does not trigger it.

**What the workflow does:**

1. Checks out `gdcorp-wordpress/wp-paas-system-plugin` on the `develop` branch
   using `GDCORP_WORDPRESS_TOKEN`
2. Configures Composer auth with the same token (for private package access)
3. Updates `composer.json` in wp-paas-system-plugin to reference the new
   godaddy-launch tag
4. Runs `composer update --ignore-platform-reqs`
5. Creates a new branch: `release-godaddy-launch-{version}`
6. Commits with message: `Update godaddy-launch to {version}`
7. Pushes the branch and opens a PR targeting `develop`
8. Assigns `@gdcorp-wordpress/wpex` as reviewers

**Secrets:**

- `GDCORP_WORDPRESS_TOKEN` — a GitHub PAT with access to
  `gdcorp-wordpress/wp-paas-system-plugin`. Configured in this repo's Actions
  secrets. Individual developers do not need it locally.

---

## Build Artifacts & Dependencies

### `build/` directory is committed to git

The `build/` directory contains compiled JS and CSS:

- `build/live-site-control.js` / `build/live-site-control.css`
- `build/publish-guide.js` / `build/publish-guide.css`

This is intentional. wp-paas-system-plugin pulls this repo via Composer, which
does not run `npm build`. The compiled assets must already be present in the
repository.

### Mozart dependency bundling

`composer run mozart-compose` bundles third-party PHP dependencies into
`includes/Dependencies/`, namespacing them to avoid conflicts with other
WordPress plugins that may use the same libraries at different versions.

Currently bundled:
- Illuminate Container
- Psr Container
- GoDaddy Styles

### Vendor directory

`vendor/` is **not** committed (it's in `.gitignore`). During the version
script, vendor is removed and the autoloader is re-optimized with
`composer dump-autoload --optimize`. wp-paas-system-plugin runs its own
`composer install` which resolves godaddy-launch as a dependency.

### Translation files

Translation templates (`.pot`) and language-specific JSON files live in
`languages/`. Generated via `npm run makepot`. Supported locales are defined in
`manifest.xml`.

---

## CI/CD Pipeline

### On every PR to `master`

| Workflow | What it does | Details |
|----------|-------------|---------|
| `test-php-unit.yml` | PHPUnit tests | Matrix: PHP 8.0, 8.1, 8.2, 8.3 |
| `test-javascript.yml` | Jest unit tests | Node 18, via `npm run test:js` |
| `validate-coding-standards.yml` | Linting | PHP (WPCS), CSS, JS |
| `test-e2e.yml` | Cypress E2E tests | Chrome, tests publish guide + live site control |

### On release publish

| Workflow | What it does |
|----------|-------------|
| `release.yml` | Creates PR to wp-paas-system-plugin |

CI checks run on PRs but do not formally gate the release tag itself. The
release is cut from `master`, so the assumption is that code merged to `master`
has already passed all checks via its PR.

---

## Troubleshooting

### `npm version` fails mid-way

If the build step fails, the version in `package.json` may already be bumped
but the PHP files and tag may not be in sync. Check each of the 3 version
files, fix manually if needed, and re-run the build steps from the `version`
script by hand.

### Tag was pushed but GitHub Release wasn't created

The workflow only triggers on `release: types: [published]`. Pushing a tag alone
does nothing. Go to GitHub Releases and create a release for the existing tag.

### Release workflow fails

Check the Actions tab for the `release.yml` run. Most likely causes:

- **`GDCORP_WORDPRESS_TOKEN` expired or lacks permissions** — needs a repo
  admin to rotate it
- **Branch name conflict** — a `release-godaddy-launch-{version}` branch
  already exists from a previous failed run. Delete it in wp-paas-system-plugin
  and re-run the workflow.
- **Composer dependency resolution failure** — check if the tag is accessible
  via Composer auth

### Build artifacts out of date

If `build/` contains stale assets, the `npm version` script should rebuild
them. If you suspect staleness, run `npm run build` manually and verify the
output matches what's committed.

### PR to wp-paas-system-plugin not appearing

Verify the workflow ran successfully in the Actions tab. Check if the branch was
created in wp-paas-system-plugin — the PR creation step may have failed
independently of the branch push.

---

## Legacy: Manual Release Script

`.dev/bin/prepare-release.sh` is a legacy script that performs the entire
release process locally — version bumping, building, tagging, pushing, and
creating the branch in wp-paas-system-plugin. It predates the GitHub Actions
workflow and is no longer the recommended path. The automated workflow
(`release.yml`) replaces most of what this script does. It remains in the repo
for historical reference.

---

## Improvement Opportunities

- **No formal release gating** — the tag is cut from `master` without a
  required check that CI passed on the latest commit. A branch protection rule
  requiring status checks before tagging could prevent releasing broken code.

- **Manual GitHub Release step** — the only manual step in the pipeline. Could
  be automated with a workflow triggered on tag push instead of release publish,
  removing the need to visit the GitHub UI.

- **No changelog automation** — release notes are written manually. A tool like
  conventional commits with auto-generated changelogs could reduce effort and
  improve consistency.

- **Token rotation risk** — `GDCORP_WORDPRESS_TOKEN` is a single point of
  failure. If it expires, releases silently stop working. A periodic check or
  GitHub App auth could be more robust.

- **Legacy script cleanup** — `prepare-release.sh` could be removed if the team
  confirms no one uses it, reducing confusion about which path to follow.

- **Version file fragility** — three files holding the same version is a
  maintenance risk. The `update-version.js` script handles it, but if someone
  bumps a version manually, they might miss one.
