<laravel-boost-guidelines>
=== .ai/collections-portal rules ===

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

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.3. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

</laravel-boost-guidelines>
