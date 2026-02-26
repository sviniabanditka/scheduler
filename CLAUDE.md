# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

University SaaS scheduling system — a multi-tenant Laravel 10 application with a Filament 3 admin panel and a separate Go solver service for schedule optimization. The UI language is Ukrainian; code comments and specs may be in Russian/Ukrainian.

## Docker Development Environment

All commands run inside Docker containers via Make targets:

```bash
make build          # Build containers
make up             # Start services (app, nginx, postgres, redis, solver)
make down           # Stop services
make install        # composer install + generate app key
make migrate        # Run migrations
make fresh          # migrate:fresh --seed
make seed           # Run seeders
make test           # Run PHPUnit tests
make shell          # bash into app container
make cache-clear    # Clear all Laravel caches
```

App runs at http://localhost:8080. To run artisan commands: `docker-compose exec app php artisan <command>`.

To run a single test: `docker-compose exec app php artisan test --filter=TestClassName`.

## Architecture

### Laravel App (PHP 8.2)

- **Multi-tenancy**: All tenant-scoped models use the `TenantScope` trait (`app/Models/Traits/TenantScope.php`) which auto-filters queries and auto-sets `tenant_id` on creation. `TenantManager` service resolves tenant from subdomain/domain/auth. `TenantMiddleware` sets tenant context per request.
- **Filament Admin Panel**: Primary UI. Resources in `app/Filament/Resources/`, custom pages in `app/Filament/Pages/` (Dashboard, ScheduleGenerationPage, ScheduleManagement), widgets in `app/Filament/Widgets/`.
- **Schedule Generation Flow**: `ScheduleGenerationPage` → `ScheduleGenerationService` → HTTP call to Go solver at `SOLVER_URL` (default `http://solver:8081`) → results stored as `ScheduleVersion` with `ScheduleAssignment` records.
- **Key Services**: `TenantManager` (tenant resolution), `ScheduleGenerationService` (solver orchestration).
- **Public Schedule**: Unauthenticated schedule viewer at `/s/{slug}` via `PublicScheduleController`.
- **User Roles**: owner, admin, planner, teacher, viewer.

### Go Solver Service (`solver/`)

Greedy optimization algorithm for schedule generation. Runs as HTTP server on port 8081.

- Entry point: `solver/cmd/server/`
- Core logic: `solver/internal/solver/` (scheduler), `solver/internal/db/` (Postgres queries), `solver/internal/api/` (HTTP handlers)
- Types: `solver/pkg/types/`
- Connects directly to Postgres (not via Laravel)
- API: `POST /api/v1/generate` with tenant_id, calendar_id, schedule_id, weights, timeout

### Database

PostgreSQL 16 (despite README mentioning MySQL — `docker-compose.yml` uses Postgres). Migrations in `database/migrations/`. The main SaaS migration is `2026_02_26_000001_create_tenants_and_all_tables.php`.

### Key Models

Core domain: `Tenant`, `Calendar`, `TimeSlot`, `Activity`, `Room`, `Teacher`, `Group`, `Subject`, `Course`, `ScheduleVersion`, `ScheduleAssignment`, `SoftWeight`, `Violation`.

### Adding a New Entity

1. Create migration with `tenant_id`
2. Create model with `TenantScope` trait
3. Create Filament Resource
4. Register in `FilamentServiceProvider`
5. Add seeder if needed

## Tech Stack

- **Backend**: Laravel 10, PHP 8.2, Filament 3
- **Frontend**: Filament (Alpine.js + Tailwind), Blade views for public pages
- **Database**: PostgreSQL 16
- **Cache/Queue**: Redis 7
- **Solver**: Go 1.21 with pgx/v5
- **Linting**: Laravel Pint (`vendor/bin/pint`)
- **Testing**: PHPUnit 10
