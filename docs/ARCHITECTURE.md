# Architecture
## Stack and boundaries
PHP 8.3 / Laravel 12 / MySQL 8; Blade + Livewire + Alpine.js + Tailwind; PHPUnit, Vite, GitHub Actions. Candidate packages: Spatie Permission (workspace-aware configuration if adopted), Lucide, Chart.js, FullCalendar, TipTap. Verify versions, licenses and support lifecycle in Phase 2. Use database queue/cache initially; Reverb only if necessary. Modular monolith; no microservices.
## Structure
Use conventional app/Models, app/Policies, app/Http, app/Livewire, app/Actions and app/Services with domain subdirectories where useful; resources/views, routes, database/migrations, tests/Feature and tests/Unit. Controllers/components delegate business rules to actions and services. Domain transactions preserve consistency. Shared services: identity, workspace context, files, audit, notifications, search, scheduler, analytics.
## Request flow
Browser -> auth/session middleware -> workspace membership middleware -> request validation -> policy -> action/service -> scoped Eloquent queries -> MySQL/private storage. Never trust workspace IDs from clients. Jobs carry explicit workspace and actor context and reauthorize as applicable. Integration tokens are encrypted and scoped.
## Interface
Responsive sidebar and workspace selector, My Day, private personal/professional/knowledge sections, shared business/team/technology sections; light/dark themes, accessible forms, loading/empty/error states. Primary indigo #6366F1, secondary violet #8B5CF6; verify contrast.
## Operations
Store UTC timestamps, display configurable timezone (default Asia/Dhaka). Use scheduler for idempotent recurring tasks and reminders. Private file disks and authorized download endpoints. SQL analytics before optional local AI. Use webhook signature verification where supported, replay protection, retry/backoff and bounded queues. No unofficial WhatsApp automation. CI: install locked deps, build frontend, run migrations/test suite, dependency audits; production deployment gated by review and backup/rollback plan.
