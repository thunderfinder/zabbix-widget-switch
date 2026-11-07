## 2026-11-07 - Consolidate Summary Items API Retrieval

**Learning:** `loadSummaryItems()` in `actions/WidgetView.php` previously issued individual `API::Item()->get` queries sequentially for each configured summary item pattern (software, vlans, cpu, fan, uptime, serial). In standard switch configurations, 5 of the 6 summary items are exact scalar OIDs / keys (e.g., `sysDescr.0`, `dot1qNumVlans.0`, `system.hw.fans[status]`, `sysUpTime.0`, `entPhysicalSerialNum.1`), generating up to 6 separate API calls per widget render. By separating exact keys from wildcard patterns and querying all exact keys in a single batch `API::Item()->get` call with `'filter' => ['key_' => $exact_keys]`, summary item API calls drop from 6 down to 2 (a 66.7% reduction in round trips) while preserving identical metric values and wildcard behavior.

**Action:** Whenever collecting multiple latest item values across discrete metrics in dashboard widgets, partition exact keys from wildcard search patterns and batch all exact keys in a single filter request.

## 2026-11-06 - Batch 24-Hour Counter Deltas History Retrieval by Value Type

**Learning:** `loadCounterDeltas24h()` previously performed sequential `API::History()->get` requests per counter item (in/out errors and in/out discards), resulting in up to 192 API requests for 48-port switches and 384 requests for 96-port switches. By batching item IDs by numeric `value_type` (0 and 3) in a single time-bounded request without a global query `limit`, and grouping points by item in PHP memory up to `self::COUNTER_POINTS` (240 points), history API requests drop by 95.8% to 99.5% (down to at most 2 requests) while maintaining identical delta and bucket calculations.

**Action:** Consolidate multi-item history requests by `value_type` across all interface telemetry collectors that process time-bounded windows, enforcing per-item point caps in application memory.

## 2026-11-03 - Batch History Retrieval by Value Type in Narrow Time Windows

**Learning:** When retrieving traffic history over a bounded time window (such as 30 minutes), issuing individual per-item queries via `API::History()->get` creates an N+1 API round-trip bottleneck (e.g., 96 to 192 sequential API calls for 48-port and 96-port layouts). While querying multiple items with a global `limit` truncates points across interfaces, querying narrow time windows without a query `limit` allows batching by `value_type` (types 0 and 3). The widget can then collect and slice the desired data points (e.g., 24 points) per item in PHP memory, achieving over 90-99% reduction in API calls without data corruption or heavy memory overhead.

**Action:** Group item IDs by `value_type` and batch history retrieval within short lookback windows, enforcing point limits per interface in application memory rather than using sequential per-item API requests.

## 2026-11-01 - Consolidate Host Access and Metadata Queries

**Learning:** `API::Host()->get` internally validates user read permissions and returns an empty result set if a host does not exist or if the current user lacks access. Performing a separate `hasHostAccess` check with `'output' => ['hostid']` prior to or alongside `loadHostMeta` results in duplicate round-trips to the Zabbix API on every widget dashboard render.

**Action:** Determine host accessibility directly from `loadHostMeta`'s returned dataset (`$host_meta !== []`) rather than issuing an auxiliary permission verification API call.

## 2026-10-31 - Zabbix API History Semantics and Redundant Item Operations

**Learning:** Zabbix `history.get` applies the `limit` parameter across the entire query result set rather than per individual item ID when multiple `itemids` are queried simultaneously. Combining history queries across multiple ports with `limit` results in truncation of data points for some interfaces, corrupting sparklines and trend charts. In contrast, item metadata retrieval (`API::Item()->get`) with `'filter' => ['key_' => $keys]` safely supports arbitrary lists of item keys, making key consolidation across multiple queries (such as primary and fallback interface speed keys) 100% semantics-preserving while saving round-trips.

**Action:** Avoid multi-item batching in `history.get` when per-item point limits are required unless individual lookups or limit-free window queries are performed. Focus instead on consolidating metadata and latest-value `item.get` requests by unioning item key sets into single batch requests.
