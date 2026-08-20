# Research Collections Portal

Laravel 13 application for a public Research Collections Portal that serves multiple natural-history collections from one shared codebase.

## Overview

This project is the public discovery and publication layer for collections data. It is designed to support additional collections over time without splitting the application into separate projects.

Initial collections:

- Fish
- Mollusk
- Non-Mollusk
- Herpetology
- Mammals
- Birds

## Tech Stack

- PHP 8.3
- Laravel 13
- MySQL for local/runtime development
- SQLite for automated tests
- Laravel Boost

## Project Structure

- `app/Models` - Eloquent models
- `database/migrations` - database schema
- `database/factories` - model factories
- `database/seeders` - database seeders
- `resources/views` - Blade views
- `resources/js` - frontend assets
- `routes` - route definitions
- `tests` - PHPUnit tests

## Initial Domain

The first domain entity is `Collection`.

Fields:

- `id`
- `name`
- `slug`
- `description`
- `is_active`
- `created_at`
- `updated_at`

Seeded collection slugs:

- `fish`
- `mollusk`
- `non-mollusk`
- `herps`
- `mammals`
- `birds`

## Setup

1. Install dependencies:

```bash
composer install
npm install
```

2. Configure environment:

```bash
cp .env.example .env
php artisan key:generate
```

3. Run migrations and seed data:

```bash
php artisan migrate --seed
```

4. Build frontend assets:

```bash
npm run build
```

## Development

```bash
composer run dev
```

This runs the Laravel app, queue listener, log tailing, and Vite concurrently.

## Testing

Run the test suite:

```bash
composer test
```

You can also run the collection-specific tests directly:

```bash
php artisan test tests/Feature/CollectionTest.php
```

## Code Style

Run Laravel Pint before committing changes:

```bash
vendor/bin/pint
```

## Notes

- The application is intended to grow around shared collection data, not collection-specific apps.
- Public collection data should remain read-only unless a feature explicitly defines otherwise.
