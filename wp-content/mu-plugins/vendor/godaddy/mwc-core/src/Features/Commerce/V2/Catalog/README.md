# Commerce Catalog v2 Integration

## Overview

This document illustrates key concepts of the Commerce Catalog v2 API resources and how they map to WooCommerce data structures.

## Table of Contents

1. [API Schema](#api-schema)
   - [Core Entities](#core-entities)
   - [Lists](#lists)
   - [Products](#products)
     - [SKUGroup (Product Parent)](#skugroup-product-parent)
     - [SKU (Individual Product/Variant)](#sku-individual-productvariant)
2. [WooCommerce => API Mapping](#woocommerce--api-mapping)
   - [Categories => Lists](#categories--lists)
     - [Category Hierarchies](#category-hierarchies)
   - [Products => SkuGroups & Skus](#products--skugroups--skus)
     - [Simple Products](#simple-products)
     - [Variable Products](#variable-products)
3. [v1 to v2 Migration](#v1-to-v2-migration)

## API Schema

The full API schema is available in the [`gdcorp-commerce/catalog-api-v2` repository](https://github.com/gdcorp-commerce/catalog-api-v2/blob/main/apis/graphql/schema.graphql).

### Core Entities
- **Lists**: Product categories with rich metadata and media support
- **List Trees & Nodes**: Hierarchical category structures with positioning
- **SKU Groups**: Product parents that group related variants/SKUs
- **SKUs**: Individual purchasable items with inventory, pricing, and attributes
- **Attributes & Values**: Product characteristics (color, size, etc.)
- **Options**: Add-ons and customizations for products

### Lists

A List is a group of SKU groups organized by similar characteristics (equivalent to WooCommerce categories).

#### Core Fields
- `id`: Globally-unique ID (String)
- `name`: Unique name for the List (String, required)
- `label`: Display label (String)
- `description`: Single-line textual description (String)
- `htmlDescription`: HTML description for rich text content (String)
- `status`: Status of the list - `ACTIVE`, `DRAFT`, or `ARCHIVED` (String)
- `createdAt`: Creation timestamp (DateTime)
- `updatedAt`: Last update timestamp (DateTime)

#### Relationships
- `skuGroups`: Related SKU groups in this list (connection)
- `mediaObjects`: Images, videos, or files associated with the list (connection)
- `metafields`: Custom data key-value pairs (connection)
- `references`: External service integrations (connection)

### Products

#### SKUGroup (Product Parent)

A SKUGroup is a grouping of related SKUs that provides information about all SKUs sold together on storefronts.

##### Core Fields
- `id`: Globally-unique ID (String)
- `name`: Unique human-friendly identifier (String, required)
- `label`: Display label (String)
- `description`: Merchant defined description (String)
- `htmlDescription`: HTML description for rich text content (String)
- `type`: Type of SKU Group - typically `PHYSICAL` or `DIGITAL` (String, required)
- `status`: Status - `DRAFT`, `ACTIVE`, or `ARCHIVED` (String)
- `shortLabel`: Short label for limited space (String, deprecated)
- `createdAt`: Creation timestamp (DateTime)
- `updatedAt`: Last update timestamp (DateTime)
- `activatedAt`: Activation timestamp (DateTime)
- `archivedAt`: Archive timestamp (DateTime)

##### Relationships & Computed Fields
- `skus`: Related SKUs in this group (connection)
- `lists`: Lists containing this SKU group (connection)
- `attributes`: Properties/characteristics (color, size, etc.) (connection)
- `options`: Add-ons that can be applied to SKUs (connection)
- `channels`: Sales channels/platforms (connection)
- `mediaObjects`: Images, videos, files (connection)
- `metafields`: Custom data key-value pairs (connection)
- `references`: External service integrations (connection)
- `priceRange`: Min/max price range across all SKUs (computed)
- `compareAtPriceRange`: Min/max compare-at price range (computed)
- `skuCount`: Total number of associated SKUs (computed)

#### SKU (Individual Product/Variant)

A SKU represents a stock keeping unit - an individual purchasable item.

##### Core Fields
- `id`: Globally-unique ID (String)
- `name`: Unique name (String, required)
- `label`: Display label (String)
- `code`: Unique SKU code (String, required)
- `description`: Description text (String)
- `htmlDescription`: HTML description for rich text (String)
- `status`: Status - `DRAFT`, `ACTIVE`, or `ARCHIVED` (String)
- `shortLabel`: Short label for limited space (String, deprecated)
- `createdAt`: Creation timestamp (DateTime)
- `updatedAt`: Last update timestamp (DateTime)
- `archivedAt`: Archive timestamp (DateTime)

##### Product Codes & Identifiers
- `eanCode`: European Article Number code (String)
- `gtinCode`: Global Trade Item Number code (String)
- `isbnCode`: International Standard Book Number code (String)
- `upcCode`: Universal Product Code (String)

##### Pricing & Cost
- `unitCost`: Cost per unit with currency (SimpleMoney)
- `cost`: Legacy cost field (Int, deprecated)
- `prices`: All prices the SKU can be sold at (connection)

##### Physical Properties
- `weight`: Weight of the SKU (Float)
- `unitOfWeight`: Unit of weight - `KG`, `GR`, `LB`, or `OZ` (String)

##### Inventory & Fulfillment
- `backorderLimit`: Number of backorders allowed (Int, null = unlimited)
- `disableInventoryTracking`: Whether to track stock (Boolean)
- `disablePriceOverrides`: Whether custom prices are allowed (Boolean)
- `disableShipping`: Whether the SKU should be shipped (Boolean)

##### Relationships
- `skuGroup`: Parent SKU group (reference)
- `attributeValues`: Specific attribute values (color: red, size: large) (connection)
- `attributes`: All attributes from parent group (connection)
- `locations`: Locations where SKU is available (connection)
- `inventoryCounts`: Inventory quantities by location and type (connection)
- `inventoryAdjustments`: Inventory change history (connection)
- `mediaObjects`: Images, videos, files (connection)
- `metafields`: Custom data key-value pairs (connection)
- `references`: External service integrations (connection)

## WooCommerce => API Mapping

### Categories => Lists

WooCommerce "Product Categories" map 1:1 to "Lists" in the API. The mapping table will have an entry similar to:

- local_id: The local ID of the WooCommerce product category.
- remote_id: The corresponding remote "list" ID in the API.
- resource_type: 'list'

#### Category Hierarchies

WooCommerce categories can have parent-child relationships. In the API, hierarchies are represented through **List Trees** and **List Tree Nodes**.

**Key Concepts:**
- **List Tree**: The root container for a hierarchy (e.g., "Product Categories")
- **List Tree Node**: Individual nodes in the tree that reference specific Lists and define parent-child relationships
- **List**: The actual category data (name, description, etc.)

**Structure:**
1. Each WooCommerce category becomes a **List** (with category data)
2. A **List Tree** represents the entire category hierarchy
3. **List Tree Nodes** define the hierarchical relationships and positions

**Example Mapping:**

WooCommerce category hierarchy:
```
- Clothes (ID: 1)
    - Dresses (ID: 2, parent: 1)
    - Pants (ID: 3, parent: 1)
    - Shoes (ID: 4, parent: 1)
        - Formal (ID: 5, parent: 4)
        - Sneakers (ID: 6, parent: 4)
- Accessories (ID: 7)
    - Watches (ID: 8, parent: 7)
```

API representation:
```
List Tree: "Product Categories" (listTreeId: abc123)
├── List Tree Node (nodeId: node1, listId: list1, parentNodeId: null, position: 1)
│   └── References List: "Clothes" (listId: list1)
│   ├── List Tree Node (nodeId: node2, listId: list2, parentNodeId: node1, position: 1)
│   │   └── References List: "Dresses" (listId: list2)
│   ├── List Tree Node (nodeId: node3, listId: list3, parentNodeId: node1, position: 2)
│   │   └── References List: "Pants" (listId: list3)
│   └── List Tree Node (nodeId: node4, listId: list4, parentNodeId: node1, position: 3)
│       └── References List: "Shoes" (listId: list4)
│       ├── List Tree Node (nodeId: node5, listId: list5, parentNodeId: node4, position: 1)
│       │   └── References List: "Formal" (listId: list5)
│       └── List Tree Node (nodeId: node6, listId: list6, parentNodeId: node4, position: 2)
│           └── References List: "Sneakers" (listId: list6)
└── List Tree Node (nodeId: node7, listId: list7, parentNodeId: null, position: 2)
    └── References List: "Accessories" (listId: list7)
    └── List Tree Node (nodeId: node8, listId: list8, parentNodeId: node7, position: 1)
        └── References List: "Watches" (listId: list8)
```

### Products => SkuGroups & Skus

WooCommerce products have a more complicated mapping schema, which differs based on the type of product.

#### Simple Products

A single simple product in WooCommerce will have **two** entries in the mapping table:

1. **SkuGroup Entry**:
   - local_id: The local ID of the WooCommerce product.
   - remote_id: The corresponding remote "sku group" ID in the API.
   - resource_type: 'sku_group'
2. **Sku Entry**:
   - local_id: The local ID of the WooCommerce product.
   - remote_id: The corresponding remote "sku" ID in the API.
   - resource_type: 'sku'

Some of the WooCommerce table maps to the SkuGroup, while others map to the Sku:

**WooCommerce Product Data => SKU Group Fields:**
- `post_title` => `label`
- `post_name` => `name` (slug)
- `post_content` => `htmlDescription`
- `post_excerpt` => `description`
- `post_status` => `status` (publish=ACTIVE, draft=DRAFT, trash=ARCHIVED)
- `post_date` => `createdAt`
- `post_modified` => `updatedAt`
- Product type => `type` (simple/variable=PHYSICAL, virtual/downloadable=DIGITAL)

**WooCommerce Product Data => SKU Fields:**
- `post_title` => `label` (for simple products, same as parent)
- `_sku` meta => `code`
- `post_content` => `htmlDescription` (for simple products)
- `post_excerpt` => `description` (for simple products)
- `_regular_price` meta => `prices` (main price)
- `_sale_price` meta => `prices` (compare-at price)
- `_weight` meta => `weight`
- `_length`, `_width`, `_height` meta => calculated or stored in metafields
- `_stock` meta => inventory counts
- `_manage_stock` meta => `disableInventoryTracking` (inverted)
- `_backorders` meta => `backorderLimit`
- `_virtual` meta => `disableShipping` 
- Product images => `mediaObjects`
- Custom fields => `metafields`
- Categories => associated Lists via SKU Group

**Variation-Specific Mapping:**
- `attribute_*` meta => `attributeValues` (e.g., `attribute_pa_color=red`)
- Variation `post_title` => `label`
- Variation `_sku` => `code` 
- Variation pricing/inventory => individual SKU fields

#### Variable Products

A single variable product in WooCommerce will have **one** SkuGroup entry. Each variation of that product will have its own Sku entry. In this scenario, the WooCommerce schema actually matches the API schema fairly closely.

In WooCommerce the "variable product" is considered the parent, and the parent has variants (or children).

In the API it's the same: the SkuGroup is the parent, and the Sku is the child.

For example, let's say there's a variable product "T-Shirt" (local ID 1) with two variations: "Size M" (local ID 2) and "Size L" (local ID 3). The mapping table will have:

1. **SkuGroup Entry** for "T-Shirt":
   - local_id: 1
   - remote_id: Corresponding remote "sku group" ID in the API.
   - resource_type: 'sku_group'
2. **Sku Entry** for "Size M":
   - local_id: 2
   - remote_id: Corresponding remote "sku" ID in the API.
   - resource_type: 'sku'
3. **Sku Entry** for "Size L":
   - local_id: 3
   - remote_id: Corresponding remote "sku" ID in the API.
   - resource_type: 'sku'

## v1 to v2 Migration

When the Commerce Catalog API migrated from v1 to v2, entity UUIDs changed completely. To maintain compatibility and enable migration, v1 UUIDs are preserved as **references** within v2 resources.

### Migration Process

1. **Reference Preservation**: Each v2 resource contains a `references` collection that includes the original v1 UUID
2. **Background Jobs**: Automated jobs periodically query v2 resources to find and map v1 references
3. **Local Mapping Update**: The WooCommerce mapping table is updated with both v1 and v2 associations

### Reference Structure

V2 resources include references like this:
```graphql
{
  references {
    origin: "catalog-api-v1-product"  # Indicates v1 source
    value: "old-v1-uuid-here"         # Original v1 UUID
  }
}
```

### Implementation

The migration system is implemented in `src/Features/Commerce/V2/Mapping/` and includes:

- **GraphQL Operations**: Query v2 resources by v1 reference UUIDs
- **Background Jobs**: Batch process migrations with error handling  
- **Mapping Persistence**: Update local database with v1↔v2 associations

For detailed technical documentation, see [Mapping/README.md](../Mapping/README.md).
