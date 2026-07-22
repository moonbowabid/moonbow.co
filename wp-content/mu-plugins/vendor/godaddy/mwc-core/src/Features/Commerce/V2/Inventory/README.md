# Commerce Inventory v2 Integration

## Resources & Operations

### Locations

Each WooCommerce store/channel is meant to have **one** location (or at least one per "commerce context").

### InventoryCount

This v2 resource represents the quantity of a SKU in a specific state at a specific time and location.

The API schema looks like this:

```
type InventoryCount {
  """The timestamp of when the inventory count was created."""
  createdAt: DateTime!

  """The globally-unique ID of the inventory count object."""
  id: ID!

  """The Location associated with the Inventory Count."""
  location: Location

  """
  The on-hand inventory count (available + committed) for this SKU and location.
  """
  onHand: Int

  """The count of the inventory."""
  quantity: Int

  """The SKU associated with the Inventory Count."""
  sku: SKU

  """
  The type of inventory count, one of `AVAILABLE`, `COMMITTED`, `BACKORDERED`.
  """
  type: String!

  """The timestamp of when the inventory count was updated."""
  updatedAt: DateTime!
}
```

- The **admin** area should always show the "on hand" count (AVAILABLE + COMMITTED).
- The **storefront** should always show the "available" count.

#### Retrieving inventory counts

To get the inventory count for a specific SKU at a specific location, you can use the following GraphQL queries.

**Single SKU Query:**
```graphql
query GetInventoryCountForSingleSKU($skuId: String!, $locationId: String!, $type: String) {
  inventoryCount(skuId: $skuId, locationId: $locationId, type: $type) {
    id
    quantity
    onHand
    type
    createdAt
    updatedAt
    sku {
      id
      backorderLimit
    }
    location {
      id
    }
  }
}
```

**Multiple SKUs Query:**
```graphql
query GetSkusWithInventoryCounts($skuIds: [String!]!) {
  skus(id: { in: $skuIds }) {
    edges {
      node {
        id
        code
        backorderLimit
        inventoryCounts(first: 100) {
          edges {
            node {
              id
              quantity
              onHand
              type
              createdAt
              updatedAt
              location {
                id
              }
            }
          }
        }
      }
    }
  }
}
```

Variables for multiple SKUs:
```json
{
  "skuIds": ["sku-uuid-1", "sku-uuid-2", "sku-uuid-3"]
}
```

**Note**: When using the multiple SKUs approach, you'll need to filter the results client-side by location ID and inventory type, as these cannot be filtered in the GraphQL query itself.

### Updating stock for an order

This is done with the `commitInventory` mutation:

```
  mutation CommitInventoryForOrder($input: MutationCommitInventoryInput!) {
    commitInventory(input: $input) {
      id
      delta
      type
      occurredAt
      sku {
        id
        code
      }
      location {
        id
        label
      }
    }
  }
```

sample variables:

```json
  {
	"input": {
		"skuId": "sku-456",
		"locationId": "location-uuid-123",
		"quantity": 2,
		"allowBackorders": true,
		"references": [
			{
				"origin": "ORDERS",
				"value": "order-12345"
			}
		]
	}
}
```

If the order is then cancelled we have to use `releaseInventory`:

```
  mutation ReleaseInventoryForCancelledOrder($input: MutationReleaseInventoryInput!) {
    releaseInventory(input: $input) {
      id
      delta
      type
      occurredAt
      sku {
        id
        code
      }
      location {
        id
        label
      }
    }
  }
```

sample variables:

```json
  {
	"input": {
		"skuId": "sku-456",
		"locationId": "location-uuid-123",
		"quantity": 2,
		"references": [
			{
				"origin": "ORDERS",
				"value": "order-12345"
			}
		]
	}
}
```

### Manual stock adjustments

When a SKU is first created, we can include the initial inventory count in the mutation:

```
  mutation CreateSkuWithInventory($input: CreateSKUInput!) {
    createSku(input: $input) {
      id
      code
      label
      status
      inventoryCounts {
        edges {
          node {
            id
            quantity
            type
            location {
              id
              label
            }
          }
        }
      }
    }
```

But once a SKU already exists, we can only update the inventory counts via the `createInventoryAdjustment` mutation:

```
  mutation ManualInventoryIncrease($input: CreateInventoryAdjustmentInput!) {
    createInventoryAdjustment(input: $input) {
      id
      delta
      type
      occurredAt
      sku {
        id
        code
        label
      }
      location {
        id
        label
      }
    }
  }
```

The `src/Features/Commerce/V2/Inventory/Builders/CreateInventoryAdjustmentInputBuilder.php` class is responsible for calculating the adjustment we have to make to achieve the desired stock level. The goal is to set a new "on hand" amount (as this is what the admin sees), but the API doesn't allow for setting this directly. So we have to calculate how we need to adjust the `AVAILABLE` number to achieve the target on hand value.
