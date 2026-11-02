## 2026-11-01 - Consolidate Host Access and Metadata Queries

**Learning:** `API::Host()->get` internally validates user read permissions and returns an empty result set if a host does not exist or if the current user lacks access. Performing a separate `hasHostAccess` check with `'output' => ['hostid']` prior to or alongside `loadHostMeta` results in duplicate round-trips to the Zabbix API on every widget dashboard render.

**Action:** Determine host accessibility directly from `loadHostMeta`'s returned dataset (`$host_meta !== []`) rather than issuing an auxiliary permission verification API call.

## 2026-10-31 - Zabbix API History Semantics and Redundant Item Operations

**Learning:** Zabbix `history.get` applies the `limit` parameter across the entire query result set rather than per individual item ID when multiple `itemids` are queried simultaneously. Combining history queries across multiple ports with `limit` results in truncation of data points for some interfaces, corrupting sparklines and trend charts. In contrast, item metadata retrieval (`API::Item()->get`) with `'filter' => ['key_' => $keys]` safely supports arbitrary lists of item keys, making key consolidation across multiple queries (such as primary and fallback interface speed keys) 100% semantics-preserving while saving round-trips.

**Action:** Avoid multi-item batching in `history.get` when per-item point limits are required unless individual lookups or limit-free window queries are performed. Focus instead on consolidating metadata and latest-value `item.get` requests by unioning item key sets into single batch requests.
