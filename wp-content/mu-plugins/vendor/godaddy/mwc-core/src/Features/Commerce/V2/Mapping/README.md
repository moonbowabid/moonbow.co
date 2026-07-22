# Commerce Catalog v2 Mapping from v1

This system handles the migration from Commerce Catalog API v1 to v2 by establishing mappings between v1 and v2 resource identifiers. This is a one-time migration process that links existing local WooCommerce resources to both v1 and v2 Commerce API UUIDs.

## Overview

The v1 to v2 migration process addresses the fact that the Commerce Catalog API v2 introduces new resource types with different UUIDs than their v1 counterparts. Since local resources were originally integrated with v1, we need to:

1. **Identify existing v1 mappings** - Find local resources that have v1 Commerce UUID associations
2. **Query v2 API for references** - Use GraphQL to fetch v2 resources that contain v1 UUIDs as references
3. **Create v2 mappings** - Store the new v2 UUIDs in our local mapping tables
4. **Maintain both mappings** - Keep both v1 and v2 mappings for compatibility

## Architecture Components

### 1. Main Feature Class
- **`CommerceCatalogV2Mapping`** - Main feature controller that manages the mapping process
- Tracks completion status via WordPress options: `mwc_v2_category_mapping_completed_at`, `mwc_v2_product_mapping_completed_at`, and `mwc_v2_location_mapping_completed_at`

### 2. Job System
The migration runs as background jobs to handle large datasets efficiently:

#### Abstract Base
- **`AbstractMappingJob`** - Base class for all mapping jobs
  - Implements batch processing with configurable limits
  - Handles exception management and duplicate prevention
  - Manages job completion tracking

#### Concrete Jobs
- **`CategoryMappingJob`** - Maps categories (v1 categories → v2 lists)
- **`ProductMappingJob`** - Maps products (v1 products → v2 SKUs/SKU groups)
  - **Note**: Media assets continue to use V1 mappings since URLs remain unchanged between API versions
- **`LocationMappingJob`** - Maps inventory locations

### 3. Data Objects

#### Core Mapping
- **`ResourceMap`** - Represents a single mapping between local ID, Commerce UUID, and resource type
- **`CategoryReferences`** - Contains v2 list data with embedded v1 references
- **`ProductReferences`** - Contains v2 SKU data with embedded v1 references
- **`LocationReferences`** - Contains v2 location data with embedded v1 references

#### GraphQL Input/Output
- **`SkuReferencesInput/Output`** - For product reference queries
- **`ListReferencesInput/Output`** - For category reference queries
- **`LocationReferencesInput/Output`** - For location reference queries

### 4. Services & Gateways
- **`SkuReferencesService`** - Handles product reference retrieval
- **`ListReferencesService`** - Handles category reference retrieval
- **`LocationReferencesService`** - Handles location reference retrieval
- **`ReferencesGateway`** - Main gateway for GraphQL operations

## How v1 UUIDs are Stored as References in v2

In the v2 Commerce API, each resource contains a `references` collection that includes historical identifiers from previous API versions. The structure looks like:

```graphql
{
  id: "v2-uuid-here"
  references {
    edges {
      node {
        id: "reference-uuid"
        origin: "catalog-api-v1-product"  # or "catalog-api-v1-category"
        value: "v1-uuid-here"
      }
    }
  }
}
```

### Reference Origins
- **Products**: `catalog-api-v1-product` (stored in SKU and SKU Group references)
- **Categories**: `catalog-api-v1-category` (stored in List references)
- **Locations**: `catalog-api-v1-location` (stored in Location references)
- **Media**: No V2 mapping required - media assets continue using V1 mappings as URLs are consistent across API versions

## GraphQL Queries Used

### Product References Query (`SkuReferencesOperation`)
```graphql
query GetSkusDetailsAndReferences($first: Int!, $after: String, $referenceValues: [String!]!) {
  skus(
    first: $first
    after: $after
    referenceValue: { in: $referenceValues }
  ) {
    edges {
      node {
        id
        code
        references {
          edges {
            node {
              id
              origin
              value
            }
          }
        }
        skuGroup {
          id
          references {
            edges {
              node {
                id
                origin
                value
              }
            }
          }
        }
      }
    }
  }
}
```

This query:
- Takes an array of v1 UUIDs as `referenceValues`
- Returns v2 SKU resources that contain those v1 UUIDs in their references
- Includes SKU group data for complete product mapping

### Category References Query
Similar structure but queries `lists` instead of `skus` for category mappings.

### Location References Query
Similar structure but queries `locations` instead of `skus` for location mappings. Takes an array of v1 location UUIDs as `referenceValues` and returns v2 Location resources that contain those v1 UUIDs in their references collection.

## Background Jobs Process

### Job Scheduling
Jobs are scheduled via `InitiateMappingInterceptor`:
- Runs every 24 hours by default (configurable via `recurringJobDateInterval`)
- Triggered on `admin_init` hook
- Dispatches job chain: `LocationMappingJob` → `CategoryMappingJob` → `ProductMappingJob`

### Job Execution Flow

#### 1. Get Unmapped Local IDs
```php
// Find local resources with v1 mappings but no v2 mappings
$sql = "
SELECT {$resourceMapsTable}.local_id
FROM {$resourceMapsTable}
WHERE {$resourceMapsTable}.resource_type_id = {$v1ResourceTypeId}
    AND {$resourceMapsTable}.local_id NOT IN (
        SELECT local_id FROM {$resourceMapsTable} 
        WHERE resource_type_id = {$v2ResourceTypeId}
    )
LIMIT {$batchSize}
";
```

#### 2. Fetch v1 Mappings
Retrieve existing v1 UUID mappings for the batch of local IDs.

#### 3. Query v2 API
Use the v1 UUIDs to query the v2 GraphQL API for corresponding v2 resources.

#### 4. Build Reference Maps
Extract v1 UUIDs from v2 reference collections and create new `ResourceMap` objects.

#### 5. Persist v2 Mappings
Insert new mappings into the database, linking local IDs to v2 UUIDs.

## Local Mapping Tables

The system uses the existing Commerce mapping table structure:

### Table: `godaddy_mwc_commerce_map_ids`
```sql
CREATE TABLE godaddy_mwc_commerce_map_ids (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    local_id bigint(20) unsigned NOT NULL,
    remote_id varchar(255) NOT NULL,
    resource_type_id int(11) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY (local_id, resource_type_id)
);
```

### Resource Type Mappings
- **v1 Products** → `CommerceResourceTypes::Product`
- **v1 Categories** → `CommerceResourceTypes::ProductCategory`
- **v1 Locations** → `CommerceResourceTypes::InventoryLocation`
- **v2 SKUs** → `CommerceResourceTypes::Sku`
- **v2 SKU Groups** → `CommerceResourceTypes::SkuGroup`
- **v2 Lists** → `CommerceResourceTypes::List`
- **v2 Locations** → `CommerceResourceTypes::Location`

## Repository Classes

### v2 Repositories (Insert new mappings)
- **`SkuMapRepository`** - Manages SKU mappings
- **`SkuGroupMapRepository`** - Manages SKU group mappings  
- **`ListMapRepository`** - Manages list (category) mappings
- **`LocationMapRepository`** - Manages location mappings

### v1 Repositories (Query existing mappings)
- **`ProductMapRepository`** - Queries existing product mappings
- **`CategoryMapRepository`** - Queries existing category mappings
- **`LocationMapRepository`** - Queries existing location mappings

## Configuration

In `configurations/features.php`:
```php
'commerce_catalog_v2_mapping' => [
    'enabled'                  => false, // Feature toggle
    'requiredFeatures'         => [Commerce::class],
    'className'                => CommerceCatalogV2Mapping::class,
    'recurringJobDateInterval' => 'PT24H', // Job frequency
    'jobs'                     => [
    	LocationMappingJob::class,
        CategoryMappingJob::class,
        ProductMappingJob::class,
    ],
],
```

## Usage Example

### Checking Migration Status
```php
// Check if mapping jobs have completed
$isComplete = CommerceCatalogV2Mapping::hasCompletedMappingJobs();

if ($isComplete) {
    // Migration is done - can use v2 APIs
    echo "v2 migration completed";
} else {
    // Still migrating or not started
    echo "Migration in progress or not started";
}
```

### Manual Job Dispatch
```php
// Manually trigger the mapping job chain
$jobs = [LocationMappingJob::class, CategoryMappingJob::class, ProductMappingJob::class];
JobQueue::getNewInstance()->chain($jobs)->dispatch();
```

## Key Benefits

1. **Backward Compatibility** - Maintains both v1 and v2 mappings
2. **Batch Processing** - Handles large datasets without timeouts
3. **Error Resilience** - Continues processing despite individual failures
4. **Duplicate Prevention** - Handles database constraint violations gracefully
5. **Progress Tracking** - Monitors completion status per resource type

## Error Handling

- **Duplicate entries** - Silently skipped (normal during retries)
- **GraphQL errors** - Logged to Sentry, job continues with remaining items
- **Database errors** - Logged to Sentry, individual records skipped
- **Job failures** - Retried automatically via job queue system

## Future Considerations

- The mapping system is designed as a **one-time migration**
- Once complete, new resources should integrate directly with v2
- v1 mappings are preserved for historical reference and potential rollback scenarios
