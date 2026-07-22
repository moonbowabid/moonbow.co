# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

GoDaddy Launch is a WordPress plugin (PHP 8.0+, WP 5.9+) that provides a guided launch experience for new WordPress sites on the GoDaddy hosting platform. It has two main features: a **Publish Guide** (multi-step onboarding) and **Live Site Control** (site launch workflow).

## Commands

### Development Setup
```bash
composer install && npm install
nvm use                    # Node 18 (from .nvmrc)
wp-env start               # Start WordPress dev environment
npm run start:wp           # Webpack dev server (watch mode)
npm run build              # Production build
```

### Testing
```bash
npm run test:js                         # Jest unit tests
npm run test:js -- --testPathPattern="site-content"  # Single test file
npm run test:e2e                        # Cypress E2E (headless Chrome)
npm run test:e2e:headed                 # Cypress E2E (headed)
npx cypress run --spec "**/__tests__/publish-guide.cypress.js"  # Single E2E spec
npm run test:unit:php                   # PHPUnit (starts wp-env with xdebug)
npm run test:php                        # PHP lint + PHPUnit
```

### Linting
```bash
npm run lint               # All linters (CSS, JS, PHP)
npm run lint:js            # ESLint only
npm run lint:css           # Stylelint only
npm run lint:php           # PHPCS (runs inside wp-env container)
npm run lint:js:fix        # ESLint autofix
npm run lint:css:fix       # Stylelint autofix
```

### State Reset (wp-env)
```bash
npm run reset              # Reset all plugin options to defaults
```

### Release
```bash
npm version [major|minor|patch]   # Bumps version in 3 files, builds, commits, tags, pushes
```

## Architecture

### Dual-Stack: PHP Backend + React Frontend

**PHP backend** (`includes/`): Service provider pattern using Illuminate Container (IoC). Main entry point is `godaddy-launch.php` which bootstraps `Application` (extends Container) and registers four service providers:
- `GoDaddyStylesServiceProvider` - GoDaddy brand styles
- `LiveSiteControl\LiveSiteControlProvider` - Launch workflow logic
- `PublishGuide\PublishGuideServiceProvider` - Guide step management
- `PageMetaServiceProvider` - Page metadata

Namespace: `GoDaddy\WordPress\Plugins\Launch\`

**React frontend** (`src/`): Two independent Webpack entry points that register as WordPress editor plugins:
- `src/publish-guide/` - Multi-step guide with 6 items (SiteInfo, SiteMedia, SiteContent, SiteDesign, AddDomain, SEO)
- `src/live-site-control/` - Site launch modals and confirmation flow
- `src/common/` - Shared components and utilities

### State Management

Uses `@wordpress/data` (Redux wrapper). Store name: `godaddy-launch/publish-guide`. Store files in `src/publish-guide/store/` (actions, reducer, selectors, constants).

Entity state (site title, logo, etc.) is managed via `@wordpress/core-data` with `useEntityProp()` hooks and debounced saves (`debouncedSaveEntityRecord` at 1000ms).

### Key Patterns

- **Query-param actions**: Deep linking via `?gdl_action=` params (e.g., `add-site-logo`, `edit-site-title`, `launch-now`, `share-on-social`)
- **Milestone API**: Custom REST endpoints at `gdl/v1/milestone/{name}` for tracking user progress
- **React 17/18 compat**: Conditionally uses `createRoot` vs `render` based on `shouldUseReact18Syntax` flag
- **Instrumentation**: `EidWrapper` component and `_expDataLayer` global for analytics (page, interaction, impression events)
- **Cypress reset**: Tests use `?gdl_cypress_reset=true` query param to reset state

### Build & Deployment

- Webpack config extends `@wordpress/scripts` with two entry points
- The `build/` directory is committed to git (required for system plugin deployment)
- Composer `vendor/` dependencies use Mozart for namespace isolation (`Dependencies/` prefix)
- Deployed as a Composer dependency in `wp-paas-system-plugin`
- Version lives in 3 places: `package.json`, `godaddy-launch.php`, `includes/Application.php` (managed by `.dev/bin/update-version.js`)

### Testing Structure

- **Jest**: `@wordpress/jest-preset-default`, config at `.dev/tests/jest/jest.config.js`, tests in `**/__tests__/*.test.js`
- **Cypress**: Config at `cypress.config.js`, support commands at `.dev/tests/cypress/support/commands.js`, specs in `**/__tests__/*.cypress.js`
- **PHPUnit**: Config at `phpunit.xml.dist`, runs inside wp-env container

### Linting Config

- ESLint: `@godaddy-wordpress/eslint-config` with cypress plugin
- Stylelint: `@godaddy-wordpress/stylelint-config`
- PHPCS: WordPress-Extra + WordPress-Docs standards, text domain `godaddy-launch`

### Styling

SCSS with WordPress base-styles mixins. Color palette uses GoDaddy Everyday Blue (`$godaddy-everyday-blue-600: #09757a`). Scoped under `#gdl-publish-guide`.

## Pull Request Format

PRs should include: Description, Jira Ticket (usually matches branch name, e.g., AIWD-504), How to Test, Screenshots (if applicable), Checklist (tested, tests updated, docs updated), and Release Notes (bullets starting with Added/Removed/Fixed/Changed).

## Working Style

Be direct and honest. Challenge incorrect assumptions. Verify requests by inspecting code before acting. Prioritize accuracy over agreeableness.
