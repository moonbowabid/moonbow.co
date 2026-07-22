# Tasks: Federation Partner ID Detection in mwc-core

## Prerequisites

- [ ] MWC API endpoint `GET /v1/customers/{customerId}/federation-partner` is built and deployed (separate effort)

## Implementation Tasks

### Task 1: Create `FederationPartnerRequest`

**Files:**
- Create `src/Features/Worldpay/Http/Requests/FederationPartnerRequest.php`
- Create `tests/Unit/Features/Worldpay/Http/Requests/FederationPartnerRequestTest.php`

**What to build:**
- [x] Request class extending `GoDaddyRequest` with `CanGetNewInstanceTrait`
- [x] Override `send()` to auto-set URL from `ManagedWooCommerceRepository::getApiUrl()`
- [x] Test: verifies URL auto-population when not set
- [x] Test: verifies pre-set URL is preserved
- [ ] Run phpunit and phpstan

**Pattern reference:** `src/Features/Marketplaces/Http/Requests/ChannelRequest.php`

---

### Task 2: Create `FederationPartnerCache`

**Files:**
- Create `src/Features/Worldpay/Cache/Types/FederationPartnerCache.php`
- Create `tests/Unit/Features/Worldpay/Cache/Types/FederationPartnerCacheTest.php`

**What to build:**
- [x] Cache class extending `Cache implements CacheableContract` with `CanGetNewInstanceTrait`
- [x] Set `$expires = 30 * DAY_IN_SECONDS`
- [x] Constructor takes `string $customerId`, sets type and key
- [x] Add `@method static static getNewInstance(string $customerId)` docblock
- [x] Test: verifies 30-day TTL expiration
- [x] Test: verifies type is `federation_partner` and key format is `federation_partner_{customerId}`
- [ ] Run phpunit and phpstan

**Pattern reference:** `src/Channels/Cache/Types/ChannelCache.php`

---

### Task 3: Create `FederationPartnerRepository`

**Files:**
- Create `src/Features/Worldpay/Repositories/FederationPartnerRepository.php`
- Create `tests/Unit/Features/Worldpay/Repositories/FederationPartnerRepositoryTest.php`

**What to build:**
- [x] Repository class with `CanGetNewInstanceTrait`
- [x] `getFederationPartnerId(string $customerId) : ?string` method
  - Uses cache `remember()` pattern with `FederationPartnerCache`
  - Calls `FederationPartnerRequest::withAuth()->setPath(...)→send()` inside callback
  - Extracts `federationPartnerId` (a raw UUID) from response body via `ArrayHelper::get()` + `TypeHelper::string()`
  - Catches exceptions silently, returns `null` from callback
  - Returns `null` if cached value is null/empty
  - Note: this returns the raw UUID — the UUID-to-`'WORLDPAY'` conversion happens in the platform repository
- [x] Test: cache hit returns UUID without making API call
- [x] Test: cache miss triggers API call, stores and returns UUID
- [x] Test: API exception returns null
- [x] Test: unsuccessful response returns null
- [ ] Run phpunit and phpstan

**Pattern reference:** `src/Channels/Repositories/ChannelRepository.php`

---

### Task 4: Update `ManagedWordPressPlatformRepository`

**Files:**
- Modify `src/Repositories/ManagedWordPressPlatformRepository.php`
- Modify `tests/Unit/Repositories/ManagedWordPressPlatformRepositoryTest.php`

**What to change:**
- [x] Add protected method `getFederationPartnerId() : string`
  - Returns `''` if `getGoDaddyCustomerId()` is empty
  - Fetches raw UUID via `FederationPartnerRepository::getNewInstance()->getFederationPartnerId($customerId)`
  - Compares UUID against `Configuration::get('features.worldpay.fpid')`
  - Returns `'WORLDPAY'` if UUID is non-null, configured FPID is non-empty, and they match; `''` otherwise
- [x] Update `getGoDaddyCustomer()` to pass `'federationPartnerId' => $this->getFederationPartnerId()` to `GoDaddyCustomer::seed()`
- [x] Update existing `testCanGetGoDaddyCustomer` to expect `federationPartnerId` populated from `getFederationPartnerId()`
- [x] Add test: returns `'WORLDPAY'` when UUID matches configured FPID
- [x] Add test: returns `''` when UUID does not match configured FPID
- [x] Add test: returns `''` when customer ID is empty (no API call)
- [ ] Run phpunit and phpstan

---

### Task 5: Add FPID configuration

**Files:**
- Modify `configurations/features.php`

**What to change:**
- [x] Add `'fpid' => defined('MWC_FPID') ? MWC_FPID : '4045db9e-1f29-11ed-bad5-fa163e69abfc'` to the `worldpay` config section
- [ ] Run phpunit and phpstan

---

## Verification

- [ ] All tests pass: `vendor/bin/phpunit` (requires `composer install`)
- [ ] Static analysis passes: `vendor/bin/phpstan analyse --memory-limit=2G` (requires `composer install`)
- [ ] Code style passes: `pre-commit run php-cs-fixer --from-ref origin/HEAD --to-ref HEAD`
