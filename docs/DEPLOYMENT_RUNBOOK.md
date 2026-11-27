# Production deployment runbook (owner approval required)
This document is an operator checklist, not authorization to deploy. Do not execute production changes until the owner approves the exact host, release commit, maintenance window and rollback plan in writing.
## 1. Before touching the host
- Confirm the host is controlled by the owner and record OS, PHP 8.3 plus required extensions, Composer 2, Node 22 (build only), MySQL 8, web server/PHP-FPM versions, domain/DNS, disk capacity and outbound mail path.
- Confirm repository PRs #18, #19 and #20 are merged in dependency order and identify the exact release SHA. Never deploy a feature branch or draft PR.
- Confirm a disposable staging restore has passed. If no staging host is available, stop: production release acceptance is blocked.
- Confirm an encrypted off-host database and private-file backup, integrity check, retention and restore evidence. Keep encryption keys separate from backup storage.
- Confirm maintenance window, expected downtime, owner contact, rollback artifact and database compatibility.
## 2. Build release artifact
- Build from the approved immutable commit in a clean environment.
- Run `composer install --no-dev --classmap-authoritative --no-interaction` using the committed lock file.
- Run `npm ci` and `npm run build`; deploy only the built public assets, not node_modules.
- Do not copy .env, storage private files, local database dumps, logs or test fixtures into the release artifact.
- Record artifact checksum and commit SHA.
## 3. Configure production outside Git
Required minimum:
```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://YOUR-APP-HOST
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
```
- Set a unique APP_KEY once and protect it; do not regenerate it during ordinary deployments.
- Configure database credentials, mail transport, log channel, cache/session drivers and any explicitly approved integration credentials in the host's secret store or protected environment file.
- Set .env permissions to owner-readable only; never place it under the public document root.
- Ensure the web server document root points only to the Laravel `public/` directory.
- Ensure `storage/app/private` is not web accessible. Grant the PHP service only the required storage and bootstrap/cache write permissions.
## 4. TLS and web server
- Point DNS to the approved host and issue a certificate using the host's supported ACME client.
- Verify the full certificate chain, hostname, renewal timer and HTTP-to-HTTPS redirect before enabling HSTS.
- Enable TLS 1.2+ (prefer TLS 1.3), secure cookies and appropriate proxy headers only for known trusted proxies.
- Verify `https://YOUR-APP-HOST/up` returns healthy and that invalid certificates fail verification.
## 5. Database and release activation
- Enter maintenance mode only during the approved window.
- Take a fresh encrypted backup immediately before migration.
- Run `php artisan down`, then `php artisan migrate --force`; inspect output and stop on any error.
- Run `php artisan config:cache`, `php artisan route:cache` only if compatible with the application's route definitions, and `php artisan view:cache`.
- Run `php artisan storage:link` only if the release intentionally uses a public storage disk; private attachments must remain on the private local disk.
- Run `php artisan up` after smoke tests pass.
- Do not use `migrate:rollback` blindly as a release rollback. Restore/forward-fix strategy must be based on the specific migration's reversibility and tested backup.
## 6. Scheduler and queue worker
Configure exactly one scheduler entry for the app user:
```cron
* * * * * cd /path/to/current && php artisan schedule:run >> /dev/null 2>&1
```
Run a supervised database queue worker (example command; adapt Supervisor paths/user):
```sh
php artisan queue:work database --sleep=3 --tries=3 --timeout=90
```
- Configure Supervisor/systemd to restart the worker after exit and on deploy; run `php artisan queue:restart` after activating a new release.
- Monitor `failed_jobs`, queue depth, worker heartbeat and scheduler execution. Do not run duplicate cron entries or unsupervised foreground workers.
## 7. Preflight and smoke tests
From the release directory after configuration and migrations:
```sh
php scripts/production-preflight.php
php artisan about
php artisan schedule:list
php artisan queue:failed
curl --fail --show-error https://YOUR-APP-HOST/up
```
The preflight is read-only. It verifies production mode, debug, HTTPS URL, app key, secure cookies, persistent queue, DB connection, queue tables, writable storage, migration status and scheduler enumeration. It does not prove DNS/TLS renewal, actual queue processing, mail delivery, backup restoration or accessibility/performance.
- Test login, 2FA, workspace switch, representative personal and business screens, private attachment authorization, notification delivery, and logout/session revocation with synthetic test accounts.
- Verify no private records, stack traces or secrets appear in public responses or logs.
## 8. Backup, monitoring and retention
- Back up database and `storage/app/private` together. Encrypt before transfer; keep keys in a separate secret store and restrict restore access.
- Store a copy off-host. Set owner-approved retention and deletion windows; verify checksums after transfer.
- Monitor uptime, TLS expiry, disk capacity, database connectivity, scheduler heartbeat, queue depth/failed jobs and application errors.
- Never send private business/personal records or secrets to a monitoring vendor. Use health/status metadata only.
- Restore both database and private files into a disposable isolated environment, verify row/file counts and checksums, boot the app, and test authorization. Record restore duration and recovery point.
## 9. Rollback
- If smoke tests fail, stop traffic or remain in maintenance mode and notify the owner.
- Repoint to the previous immutable release artifact and restore its compatible configuration.
- If migrations changed schema/data, use the tested migration-specific rollback or restore the pre-release backup; do not assume code rollback alone is safe.
- Re-run health and authorization smoke tests. Record incident, cause and recovery.
## 10. Explicit approval record
Before deployment, record: approved commit SHA, target hostname/provider, maintenance window/timezone, expected downtime, backup identifier/checksum, successful staging restore evidence, TLS/DNS owner, rollback artifact and the owner's explicit approval. No approval record means no production deployment.
