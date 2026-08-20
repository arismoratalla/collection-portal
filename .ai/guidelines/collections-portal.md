# Research Collections Portal Guidelines

## Project Purpose

This Laravel application is a public web portal for discovering and viewing
natural-history research collection records.

The initial collections are:

- Fish
- Mollusk
- Non-Mollusk
- Herpetology
- Mammals
- Birds

The architecture must support additional collections later without requiring
separate applications or incompatible database schemas.

## System Boundary

Specify is the authoritative collection-management system.

Laravel is the public discovery and publication layer.

The Laravel application must not be treated as a replacement for Specify.

Do not allow public users to modify authoritative collection records.

Do not connect public HTTP requests directly to the production Specify
database.

Data should be imported or synchronized into a Laravel publication database.

## Shared Data Model

Do not create separate specimen models such as:

- FishSpecimen
- BirdSpecimen
- MammalSpecimen
- MolluskSpecimen
- HerpSpecimen

Use a shared specimen model.

Example relationship:

Collection
    hasMany Specimens

Specimen
    belongsTo Collection

Collections should be represented as database records rather than separate
applications.

Expected collection slugs include:

- fish
- mollusk
- non-mollusk
- herps
- mammals
- birds

## Core Domain Entities

The shared domain should support at minimum:

- Collection
- Specimen
- Taxon
- Determination
- Agent
- Collector
- Geography
- Locality
- CollectingEvent
- Preparation
- Media
- Reference
- ImportBatch

Prefer normalized relational models for shared scientific concepts.

Use JSON only for genuinely collection-specific or rarely queried extension
data.

Do not duplicate common fields across collection-specific tables.

## Taxonomy

Taxonomy must support hierarchical classification.

Expected ranks may include:

- Kingdom
- Phylum
- Class
- Order
- Family
- Genus
- Species
- Subspecies

A specimen can have multiple determinations.

One determination may be considered current/preferred.

Taxonomic data should not be hard-coded into specimen records.

## Geography and Locality

Geography and locality are separate concepts.

Geography may include:

- continent
- country
- state/province
- county
- municipality

Locality may contain:

- verbatim locality
- normalized locality
- latitude
- longitude
- coordinate uncertainty
- datum
- georeference remarks
- georeference source

Multiple specimens may reference the same collecting event or locality where
appropriate.

## Sensitive Locality Data

Never expose sensitive locality information merely because it exists in the
database.

The public representation of coordinates must be explicitly controlled.

Support fields or policy logic for:

- public coordinates
- generalized coordinates
- hidden coordinates
- sensitive locality status

Sensitive coordinates must not enter:

- public API responses
- HTML source
- JavaScript data payloads
- map marker payloads
- search indexes
- exports

Authorization alone is not sufficient if the data has already been serialized
to the browser.

## Search

Initial search should support:

- free-text search
- catalog number
- collection
- scientific name
- taxonomy
- collector
- country
- state/province
- county
- locality
- collecting date
- type status
- preparation
- specimen with images

Start with database-backed search while the schema is being developed.

Meilisearch or another dedicated search engine can be introduced after the
data model and import process are stable.

Search indexing must use public-safe data only.

## Collection Filtering

All major discovery pages should allow filtering by collection.

Do not duplicate search code for every collection.

Prefer:

SearchQuery
    -> collection filter
    -> shared filters
    -> result set

Collection-specific fields can be exposed conditionally when necessary.

## URLs

Prefer stable human-readable routes.

Examples:

/
 /collections
 /collections/fish
 /collections/mammals
 /search
 /specimens/{specimen}
 /taxa/{taxon}
 /api/v1/specimens

Do not expose internal database IDs unnecessarily in public-facing identifiers
when stable specimen identifiers are available.

## Specimen Detail Page

A public specimen page should be capable of displaying:

- institution
- collection
- catalog number
- occurrence identifier
- scientific name
- identification history
- type status
- collectors
- collecting event
- collecting date
- geography
- public locality
- public coordinates
- preparations
- specimen count
- media
- remarks where appropriate
- citations or references
- external identifiers

Not every collection will populate every field.

Templates must handle missing values gracefully.

## Media

Media should be modeled independently from specimen records.

Support:

- images
- documents
- audio
- video

Media records should include:

- media type
- URI/path
- title
- description
- creator
- rights
- license
- attribution
- sort order

Do not assume all images are stored locally.

## Imports

Imports should be repeatable and auditable.

Every import should be associated with an ImportBatch.

Record:

- source
- collection
- start time
- completion time
- imported rows
- updated rows
- skipped rows
- errors
- status

Prefer queued import processing for large datasets.

Do not perform large imports inside HTTP requests.

Import code must be idempotent where practical.

## Specify Integration

Specify-specific field mappings should live outside generic domain logic.

Prefer a mapping/import layer such as:

app/
    Services/
        Imports/
            Specify/

or an equivalent architecture consistent with project conventions.

Do not scatter Specify table names throughout controllers and models.

Use dedicated mapping or transformation classes.

The public schema should remain understandable without knowledge of Specify's
internal database structure.

## Controllers

Controllers should remain thin.

Controllers should primarily:

- validate/receive requests
- invoke actions/services/query objects
- return responses

Do not put complex import, search, taxonomy, or transformation logic directly
inside controllers.

## Validation

Use Laravel Form Request classes for non-trivial request validation.

Never trust query parameters or uploaded import files.

## Authorization

Public specimen discovery does not require authentication.

Administrative capabilities must use explicit authorization policies or gates.

Do not rely solely on hiding UI elements.

## Performance

Avoid N+1 database queries.

Use eager loading where appropriate.

Add database indexes for commonly filtered fields.

Expected indexed fields include combinations involving:

- collection_id
- catalog_number
- occurrence_id
- taxon_id
- collecting_event_id
- country/state/county foreign keys
- event dates
- type status

Do not add indexes blindly; verify query patterns.

## Laravel Practices

Follow Laravel 13 conventions.

Prefer framework functionality over unnecessary custom abstractions.

Use:

- Eloquent relationships
- Form Requests
- Policies
- API Resources
- Jobs
- Queues
- Events where useful
- Cache where useful
- Config files instead of hard-coded environment values

Do not call env() directly outside configuration files.

## Frontend

Prefer server-rendered Laravel views.

Use Blade and Livewire where interactivity is required.

Use Alpine.js for small client-side behaviors.

Avoid introducing a full SPA unless a concrete requirement justifies it.

Use Tailwind CSS consistently.

## Maps

Use Leaflet for public geographic visualization unless another library is
explicitly selected.

Map rendering must use only public-safe coordinates.

The map should eventually support:

- marker display
- result synchronization
- bounds filtering
- clustering when appropriate

Do not expose hidden coordinates through map APIs.

## Testing

New domain behavior should have automated tests.

Prefer feature tests for user-visible behavior.

Use unit tests for isolated domain transformations.

Important areas requiring tests include:

- collection filtering
- specimen visibility
- sensitive coordinates
- imports
- search filters
- API output
- taxonomy relationships

Sensitive-locality tests are mandatory before public deployment.

## Coding Agent Behavior

Before modifying code:

1. Inspect the existing project.
2. Check Laravel Boost guidance.
3. Follow established project conventions.
4. Search Laravel documentation through Boost when framework behavior is
   uncertain.
5. Make the smallest coherent change.
6. Add or update tests.
7. Run the relevant test suite.
8. Run Laravel Pint before considering work complete.

Do not generate large numbers of unrelated files in one step.

Do not redesign working architecture without explaining why.

Do not introduce dependencies when Laravel or an existing dependency already
provides the required functionality.