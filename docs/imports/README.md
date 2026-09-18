# Collection Import Workflow

This portal uses a staged import workflow for Research Collections data.

## Current workflow

Collection Managers
- Maintain source data in MS Access collection databases
- Export through `ipt-exporter`
- Produce Darwin Core CSV files
- Publish to IPT / online aggregators

Research Collections Portal
- Receives Darwin Core CSV files in Laravel
- Profiles and previews incoming files
- Will later import into MySQL
- Serves the public portal

## Directory structure

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

## Commands

- `php artisan portal:imports`
- `php artisan portal:imports fish`
- `php artisan portal:profile fish`
- `php artisan portal:preview fish --limit=5`
- `php artisan portal:persist-preview fish --limit=100`
- `php artisan portal:persist-preview fish --limit=1000 --benchmark`
- `php artisan portal:import fish --limit=10 --yes`

## Directory meanings

- `incoming` = newly generated dataset ready for inspection
- `archive` = reserved for successfully processed datasets later
- `failed` = reserved for failed imports later

## Current status

The following are not implemented yet:

- file movement from `incoming` to `archive` or `failed`
- CSV parsing into database records
- database import logic
- scheduled imports
- UI
- APIs

The current code only discovers, profiles, and previews incoming CSV files.
