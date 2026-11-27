# Phase 20 — Final release and deployment readiness
## Objective
Prepare Softorio Workspace for an owner-approved production release. This phase must not deploy automatically. Production deployment is a separate, explicit approval gate after the owner has reviewed the target host, backups, restore evidence, DNS/TLS, and rollback plan.
## Dependency and branch strategy
This branch is stacked on `feature/phase-19`. PR #19 depends on PR #18, and PR #18 currently targets `develop`. Merge prerequisites in order: PR #18, then PR #19, then PR #20. Do not retarget or merge around those dependencies.
## Release workstreams
1. Target environment inventory: host/OS, PHP 8.3 extensions, Composer 2, Node 22 build process, MySQL 8, web server/PHP-FPM, writable directories, DNS and domain ownership.
2. Configuration: production APP_ENV, APP_DEBUG=false, HTTPS APP_URL, secure session cookies, APP_KEY handling, trusted proxy settings, mail delivery, queue database, cache/session drivers, private local storage, log rotation and secrets outside Git.
3. Database release: fresh encrypted backup, migration status, maintenance window, migration execution, post-migration smoke tests and documented rollback constraints. Destructive down-migrations are not an automatic rollback strategy.
4. Scheduler and queues: configure Laravel scheduler once per minute; run a supervised queue worker with restart/timeout/retry policy; monitor failed jobs and avoid duplicate cron entries.
5. TLS and HTTP: valid certificate chain, automatic renewal, HTTP-to-HTTPS redirect, HSTS only after HTTPS is confirmed, secure cookies, health endpoint and external certificate-expiry monitoring.
6. Backups: encrypted database plus private-file backup, off-host copy, access separation, retention policy, integrity verification, restore evidence and documented RPO/RTO. Restore acceptance remains blocked until owner provides a disposable staging host.
7. Monitoring: uptime/health, TLS expiry, disk space, queue depth/failed jobs, scheduler heartbeat, application errors and database availability. Do not send secrets or private records to third-party monitoring.
8. Rollback: previous release artifact, compatible database backup, maintenance mode, code/config rollback, migration compatibility and post-rollback smoke tests. Never promise rollback where a destructive migration has no tested reverse path.
9. Owner approval: show target host, change window, backup location, restore evidence, downtime estimate, DNS/TLS plan and rollback plan; obtain explicit approval before deployment.
## Safety constraints
- Never deploy from pull_request or push CI.
- Never place production secrets in repository files, Actions logs or artifacts.
- A successful CI run is not proof of production readiness.
- No production deploy until the owner explicitly approves the exact target and deployment window.
- No paid APIs/services may be introduced as a requirement without separate owner approval.
## Exit criteria
- Automated preflight checks pass against the actual target environment.
- CI and dependency audits pass on the final release candidate.
- Backup is encrypted, copied off-host, integrity checked, and restored in an isolated staging environment.
- Scheduler, queue worker, mail, health checks and TLS renewal are verified on target.
- Accessibility and performance acceptance evidence from Phase 19 is attached.
- Rollback runbook and owner sign-off are recorded.
