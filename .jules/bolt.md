## 2026-10-31 - Zabbix API History Semantics and Redundant Item Operations

**Learning:** Zabbix `history.get` applies the `limit` parameter across the entire query result set rather than per individual item ID when multiple `itemids` are queried simultaneously. Combining history queries across multiple ports with `limit` results in truncation of data points for some interfaces, corrupting sparklines and trend charts. In contrast, item metadata retrieval (`API::Item()->get`) with `'filter' => ['key_' => $keys]` safely supports arbitrary lists of item keys, making key consolidation across multiple queries (such as primary and fallback interface speed keys) 100% semantics-preserving while saving round-trips.

**Action:** Avoid multi-item batching in `history.get` when per-item point limits are required unless individual lookups or limit-free window queries are performed. Focus instead on consolidating metadata and latest-value `item.get` requests by unioning item key sets into single batch requests.

## 2026-11-01 - Consolidating Host Access Checks with Host Metadata Queries

**Learning:** In Zabbix frontend controllers, all internal `API::Host()->get()` calls enforce the active user's permissions. Performing a separate `API::Host()->get(['output' => ['hostid'], 'hostids' => [$hostid], 'limit' => 1])` access check alongside `loadHostMeta()` is completely redundant. If the user lacks access or the host does not exist, `loadHostMeta()` already returns an empty array.

**Action:** Rely on `loadHostMeta()` returning an empty array to detect access denial or non-existent hosts, avoiding a redundant `API::Host()->get` network round-trip on every dashboard widget render.
