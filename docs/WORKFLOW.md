# Research Collections Portal Workflow

## Architecture

Collection Manager
-> MS Access
-> IPT Exporter
-> Darwin Core CSV
-> Collection Portal
-> Laravel
-> MySQL
-> Public Discovery

## Source Data Workflow

1. A collection manager updates the authoritative MS Access database.
2. The latest exporter CSV is placed in `storage/app/private/collection-imports/{collection}/incoming/`.
3. The portal discovers incoming files with `php artisan portal:imports`.
4. The portal profiles the CSV with `php artisan portal:profile {collection}`.
5. The portal previews mappings with `php artisan portal:preview {collection} --limit=5`.
6. The portal performs a rollback-only persistence preview with `php artisan portal:persist-preview {collection} --limit=100`.
7. The portal benchmarks the rollback path with `php artisan portal:persist-preview {collection} --limit=1000 --benchmark`.
8. A controlled sample import is executed manually with `php artisan portal:import {collection} --limit=10 --yes`.
9. The imported sample is inspected with `php artisan portal:inspect-import {collection} --limit=10`.
10. The same sample import is repeated manually to confirm idempotency.

## Collection Import Directories

```text
storage/app/private/collection-imports/
├── fish/
│   ├── incoming/
│   ├── archive/
│   └── failed/
├── mollusk/
├── non-mollusk/
├── herps/
├── mammals/
└── birds/
```

## Existing Portal Commands

- `php artisan portal:imports`
- `php artisan portal:imports fish`
- `php artisan portal:profile fish`
- `php artisan portal:preview fish --limit=5`
- `php artisan portal:persist-preview fish --limit=100`
- `php artisan portal:persist-preview fish --limit=1000 --benchmark`
- `php artisan portal:import fish --limit=10 --yes`
- `php artisan portal:inspect-import fish --limit=10`
- `php artisan portal:status`

## Data Validation Process

- Verify the incoming file is in the correct collection directory.
- Profile the CSV before any persistence work.
- Inspect the preview mapping for unexpected column behavior.
- Use the rollback-only persistence preview to validate database behavior.
- Do not move beyond the controlled 10-record sample until it has been manually reviewed.

## Persistence Preview

The current persistence preview writes through the real persistence layer and then rolls the database transaction back.

This allows the importer to be measured without permanently changing data.

## Current Project Status

- Laravel setup complete
- MySQL setup complete
- collection model complete
- core specimen domains complete
- Fish CSV discovery complete
- Fish profiling complete
- Darwin Core mapping complete
- persistence mapper complete
- rollback persistence preview complete
- idempotency checks complete
- advanced public faceted search and public-coordinate map complete

Validated dataset:

- Fish / Ichthyology
- 116,830 rows

Known Fish data observations:

- 0 duplicate occurrence IDs
- 0 duplicate catalog numbers
- 1 invalid latitude
- 3 latitude-only rows
- sparse typeStatus
- empty countryCode

## Public Collection Search

`/collections/{slug}/search` provides collection-scoped faceted search with
bookmarkable URL state. Multiple values in a facet are combined with OR;
different facets are combined with AND. Facet counts apply all other active
filters while excluding their own facet selection, which keeps alternative
values useful.

- Fish result columns and available facets are configured in `config/collection-search.php`.
- Facet options are loaded five at a time initially for common filters, and
  lazily fetched for other collapsed filters. Facet search and subsequent
  option pages use collection-scoped JSON endpoints.
- The result table supports whitelisted sorting and 25, 50, or 100-record
  pages. The default is 50.
- The Leaflet map reads bounded, filtered results from a dedicated endpoint
  and clusters markers in the browser. Map data is capped at 1,000 markers;
  server-side clustering is required before increasing that limit materially.
- Only localities with `coordinates_public = true`, `sensitive = false`, and
  valid latitude/longitude pairs can be queried or returned by the map.

Map tiles are configured with `PORTAL_MAP_TILE_URL` and
`PORTAL_MAP_ATTRIBUTION`, with an OpenStreetMap-compatible development default
in `config/portal-map.php`. Public OpenStreetMap community tile servers should
not be assumed suitable for high-volume production traffic; choose production
tile infrastructure or a provider before deployment.

Current blocker/priority:

- production-scale map clustering and search query profiling against MySQL

## Returning After a Break

Run:

```bash
git status
composer install
npm install
php artisan migrate:status
php artisan portal:status
php artisan test
npm run build
```

Then review:

- this file
- `http://collection-portal.test/status`
- `http://collection-portal.test/workflow`

## Next Development Milestones

1. Optimize and validate 1,000-row rollback persistence preview.
2. Run the MySQL 1,000-row rollback benchmark if Codex cannot.
3. Manually execute `php artisan portal:import fish --limit=10 --yes`.
4. Inspect `/collections/fish/search`.
5. Open several specimen detail pages.
6. Record database counts.
7. Run the same import command again.
8. Confirm counts do not increase unexpectedly.
9. Stop and review before expanding to 100.
10. Expand committed test to 100 records.
11. Implement ImportBatch/provenance ledger.
12. Add importer logging/error reporting.
13. Implement completed-file archive workflow.
14. Prepare first full Fish import.
15. Validate full Fish counts and relationships.
16. Profile representative faceted and bounded-map searches with MySQL `EXPLAIN`.
17. Add server-side map clustering before allowing more than 1,000 markers.
18. Implement Media later when actual media requirements are available.
19. Add additional collections one at a time using their real exporter datasets.
