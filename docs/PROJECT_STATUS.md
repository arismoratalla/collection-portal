# Project Status

## Completed

- Laravel setup
- MySQL setup
- collection model
- core specimen domains
- Fish CSV discovery
- Fish profiling
- Darwin Core mapping
- persistence mapper
- rollback persistence
- idempotency test
- public collection browsing
- public specimen search
- specimen detail pages
- controlled sample import command
- advanced public faceted collection search
- collection-specific search columns and facets
- Leaflet public-coordinate map with marker clustering

## Validated Dataset

- Fish / Ichthyology
- 116,830 rows

## Known Fish Data Observations

- 0 duplicate occurrence IDs
- 0 duplicate catalog numbers
- 1 invalid latitude
- 3 latitude-only rows
- sparse typeStatus
- empty countryCode

## Public Search

The collection search page supports URL-backed multi-select facets, dynamic
facet counts, progressive facet option loading, global text search, whitelisted
sorting, configurable pagination, and collection-specific display columns.

The map uses Leaflet and `leaflet.markercluster`. Its collection-scoped map
endpoint returns only valid public, non-sensitive coordinate pairs and caps the
response at 1,000 markers. Tile URL and attribution are configured through
`PORTAL_MAP_TILE_URL` and `PORTAL_MAP_ATTRIBUTION`.

## Current Priority

Profile representative faceted and map queries with MySQL `EXPLAIN`, then add
server-side clustering before production map traffic or larger marker limits.
