# Move Worldpay Federation Partner ID Detection to mwc-core

## Why

The Worldpay federation partner ID detection currently lives in `woosaas-system-plugin`'s `WooSaaSPlatformRepository`, reading directly from the Pagely hosting config. This creates cross-repo coupling and ties the Worldpay feature to a platform-specific data source. Moving the detection to `mwc-core` with a new data source (Shopper API via MWC API) makes the Worldpay feature self-contained and decoupled from the hosting platform.

## What Changes

### mwc-api (new endpoint + Shopper API integration)
- **Add `ShopperAuth` data object** to hold the `federationPartnerId` from the Shopper API's auth data group
- **Add `auth` property** to the existing `Shopper` data object (type `?ShopperAuth`)
- **Update Shopper API calls** to include `ShopperDataGroupEnum::Auth` in included data groups when federation partner data is needed
- **Create `GET /v1/customers/{customerId}/federation-partner` endpoint** that returns `{ federationPartnerId: string }` (a raw UUID) by fetching the shopper's auth data from the Shopper API

### mwc-core (new API client + caching + UUID-to-WORLDPAY conversion)
- **Create a request class** to call the new MWC API endpoint (`/v1/customers/{customerId}/federation-partner`)
- **Create a cache class** with 30-day TTL to store the raw federation partner UUID aggressively
- **Create a repository class** to orchestrate the cached API call and return the raw UUID
- **Update `ManagedWordPressPlatformRepository`** to fetch the federation partner UUID from the MWC API (with cache), compare it against the configured Worldpay FPID, and return `'WORLDPAY'` on match — the UUID-to-`'WORLDPAY'` conversion is a mwc-core concern
- **Move FPID configuration** (`features.worldpay.fpid`) from `woosaas-system-plugin` to `mwc-core` — this configured UUID is compared against the UUID returned by the API to identify Worldpay customers

### woosaas-system-plugin (config cleanup)
- **Keep `WooSaaSPlatformRepository::getFederationPartnerId()` as-is** — the existing Pagely-based detection is faster (file read vs API call) and continues to work correctly for woosaas sites. The mwc-core changes provide FPID detection for sites that don't run woosaas-system-plugin.
- **Remove `features.worldpay.fpid`** from `configurations/features.php` — now inherited from mwc-core

## Capabilities Affected

- **Worldpay feature activation** (`Worldpay::shouldLoad()`) - detection will be sourced from the Shopper API (via MWC API) instead of Pagely hosting config; the raw UUID is compared against the configured FPID in mwc-core to produce `'WORLDPAY'`
- **Federation partner identification** - the `GoDaddyCustomer::getFederationPartnerId()` value, exposed via the `/account` REST API endpoint in `AccountController`
- **Payment gateway configuration** - downstream: which gateways are filtered, which UI overrides apply

## Impact

### Code Changes

| Repository | Files affected | Change type |
|---|---|---|
| mwc-api | `app/DataSource/Commerce/DataObjects/ShopperAuth.php` | New: data object for auth group |
| mwc-api | `app/DataSource/Commerce/DataObjects/Shopper.php` | Modified: add `?ShopperAuth $auth` property |
| mwc-api | `app/Http/Controllers/v1/Customers/FederationPartnerController.php` | New: endpoint controller |
| mwc-api | `routes/api.php` | Modified: register new route |
| mwc-api | tests | New: tests for new endpoint and data objects |
| mwc-core | New request class (e.g. `FederationPartnerRequest`) | New: MWC API client |
| mwc-core | New cache class (e.g. `FederationPartnerCache`) | New: 30-day TTL cache for raw UUID |
| mwc-core | New repository class (e.g. `FederationPartnerRepository`) | New: orchestrates cache + request, returns raw UUID |
| mwc-core | Platform repository | Modified: fetch UUID from MWC API, compare against configured FPID, return `'WORLDPAY'` on match |
| mwc-core | `configurations/features.php` | Modified: add `fpid` key to worldpay config (used for UUID comparison) |
| mwc-core | tests | New/modified: tests for new integration |
| woosaas-system-plugin | `src/App/Repositories/WooSaaSPlatformRepository.php` | No changes: existing Pagely-based detection kept as-is |
| woosaas-system-plugin | `configurations/features.php` | Modified: remove `worldpay.fpid` entry (inherited from mwc-core) |
| woosaas-system-plugin | tests | No changes |

### Dependencies

- **Shopper API** - new dependency; the MWC API will call it with `auth` data group to retrieve the `federationPartnerId` UUID
- **mwc-common** - defines the contracts (`PlatformRepositoryContract`, `GoDaddyCustomerContract`) - no changes expected
- **mwc-api** - new endpoint must be deployed before mwc-core starts consuming it

### Risks

- **Deployment ordering**: mwc-api must be deployed with the new endpoint before mwc-core is updated to consume it
- **Shopper API availability**: If the Shopper API is down, the 30-day cache mitigates impact after initial population, but first-time requests will fail
- **Cache cold start**: New sites or cache clears will require a live API call before the Worldpay feature can activate

## Out of Scope (Future Work)

### ConfigurationLoader (Poynt Credential Loading)

The `woosaas-system-plugin` also contains a `ConfigurationLoader` at `src/App/Configuration/Worldpay/ConfigurationLoader.php` that loads Poynt payment credentials from the Pagely hosting config. This component is **not in scope** for this change but should be considered for a future move to mwc-core.

**What it does:**
- Implements `ConditionalComponentContract`, only loads when `Worldpay::shouldLoad()` returns true
- Reads from `HostConfigRepository::getPagelyApiConfig()`:
  - `appIdentifiers.purchasingBusinessId` -> `payments.poynt.applicationId` (as `urn:app:{id}`), `payments.poynt.businessId`
  - `appIdentifiers.purchasingStoreId` -> `payments.poynt.siteStoreId`
  - `businessCredentials.{businessId}.appId` -> `payments.poynt.appId`
  - `businessCredentials.{businessId}.privateKey` -> `payments.poynt.privateKey`
  - `businessCredentials.{businessId}.publicKey` -> `payments.poynt.publicKey`
- Gracefully handles missing/partial config (returns early without setting incomplete values)
- Registered as a component in `App::$componentClasses`

**Source files:**
- Implementation: `woosaas-system-plugin/src/App/Configuration/Worldpay/ConfigurationLoader.php`
- Tests: `woosaas-system-plugin/tests/Unit/App/Configuration/Worldpay/ConfigurationLoaderTest.php`
- Registration: loaded as a component in `woosaas-system-plugin/src/App/App.php`

Moving this to mwc-core will face the same hosting-platform dependency challenge — credentials would need to come from the MWC API or another abstracted source.
