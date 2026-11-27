# Phase 19 — Final security, QA and production readiness
## Work branch and dependency
Phase 19 is based on `feature/phase-18` because Phase 18 is still an open draft PR. PR #19 is therefore stacked on Phase 18; it must not be merged directly into develop until Phase 18 has been completed and merged.
## Scope
- Review authentication, workspace/resource authorization, uploads, exports, integrations, webhooks, AI retrieval and PWA boundaries.
- Add regression coverage for IDOR, role escalation, private data exposure, CSRF/XSS, SSRF, secrets and security headers.
- Audit accessible labels, keyboard operation, focus states, color contrast and responsive layouts.
- Profile database query counts, pagination, expensive dashboard queries and frontend bundles.
- Verify schema constraints, migration rollback/replay and data consistency.
- Define encrypted backup schedule, retention, off-site copy, restore drill, RPO/RTO and incident runbook; perform an actual restore in a disposable environment before claiming completion.
- Verify production configuration, debug disabled, HTTPS, secure cookies, queue/scheduler, logs, monitoring and rollback plan.
## Initial security headers
The web middleware adds nosniff, frame denial, restrictive referrer and permissions policies, same-origin opener/resource policies, and HSTS only when the request is HTTPS. A full CSP is deferred until inline script/style use has been inventoried and nonces/hashes are implemented; do not ship a breaking blanket policy.
## Backup/restore acceptance
No production backup or restore is performed by CI. Before release, take an encrypted database and private-file backup, restore both into an isolated disposable environment, verify counts and representative records, test application boot and file authorization, and record checksums plus duration. Never use production credentials or copy real records into CI.
## Status
Initial security-header middleware and regression tests added. Full application audit, accessibility/performance checks, encrypted backup implementation and restore drill remain outstanding. CI evidence is required; no production deployment.
