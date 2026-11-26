# Softorio Workspace

Self-hosted personal productivity, professional development and team management software. Private personal workspaces and shared business workspaces; invitation-only initial release. No mandatory paid integrations.

## Development setup
Requires PHP 8.3 (Composer 2 and Laravel-required extensions), Node.js 22, npm and MySQL 8. Create a dedicated empty database and user. From repository root:
```sh
cp .env.example .env
composer install
npm install
php artisan key:generate
# Configure DB_* in .env before migrating.
php artisan migrate
npm run build
php artisan serve
```
Run tests only against a disposable database (RefreshDatabase may reset it). Set DB_* to the dedicated test database before running php artisan test. For local development, use npm run dev and php artisan serve in separate terminals. Livewire supplies Alpine.js. No default user or public registration. Phase 3 adds identity and workspace controls. Do not expose this foundation to production.

CI initially resolves missing lockfiles and uploads them as artifacts; commit verified locks before Phase 2 is complete. Once present, Composer install and npm ci use committed locks.

## Documentation
- [Project overview](docs/PROJECT_OVERVIEW.md)
- [Functional scope](docs/FEATURES.md)
- [Architecture](docs/ARCHITECTURE.md)
- [Database design](docs/DATABASE.md)
- [Roadmap](docs/ROADMAP.md)
- [Security](docs/SECURITY.md)
- [Development status](docs/DEVELOPMENT_STATUS.md)
- [Development instructions](AGENTS.md)

Never commit secrets or real private records. Use develop and feature branches; main is stable.
