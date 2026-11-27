# Phase 19 — Final security, QA and production readiness
## Work branch and dependency
Phase 19 is based on `feature/phase-18` because Phase 18 remains an open draft PR. PR #19 is stacked on Phase 18; do not merge it directly into `develop` until Phase 18 is completed and merged.
## Completed in this branch so far
- Baseline browser security headers: content-type sniffing disabled, framing denied, existing stricter Referrer-Policy preserved, restrictive Permissions-Policy, same-origin opener/resource policies, and HSTS only on HTTPS.
- Regression tests for security headers, no-store/private cache behavior, HSTS transport condition, skip navigation, main landmark, visible focus, and reduced-motion support.
- Dashboard habit statistics changed from one existence query per active habit to one aggregate query for all habits. The aggregate keeps each habit's configured timezone by matching its own local date; a regression test asserts correct cross-timezone counts and one habit-entry query.
- Existing dashboard and workspace security tests continue to cover private workspace names, current-workspace fallback, activity scoping, and escaped workspace labels.
## Remaining application security review
- Review authorization/IDOR and cross-workspace boundaries for every controller/resource, including files, finance, team, projects, CRM, infrastructure, communication/webhooks, AI retrieval and PWA subscriptions.
- Review upload content/size/type limits, export formula injection, webhook replay protection, SSRF DNS rebinding, secret handling, rate limits and log redaction.
- Do not add a blanket Content-Security-Policy until inline script/style usage has been inventoried and a nonce/hash strategy has been tested.
## Accessibility and responsive QA
- Existing shell includes skip navigation, a main landmark, labelled navigation and controls, visible focus indicators and reduced-motion handling.
- Automated checks cover these baseline affordances. Full screen-reader, keyboard-only, contrast, zoom/reflow and real-device checks remain manual acceptance items.
## Performance
- Dashboard habit check-ins now use a bounded aggregate query rather than N+1 per-habit existence checks. Regression test verifies one habit-entry query and per-habit timezone correctness.
- Remaining work: capture representative query counts/latencies, inspect all dashboard widgets and expensive reports, validate pagination bounds and measure production-like frontend bundle and interaction performance. No unmeasured performance claim should be made.
## Backup and restore acceptance
- No staging server is currently available; the owner plans to arrange one later.
- Before release, implement/confirm encrypted database and private-file backup, retention and off-site copy. Then restore both into an isolated disposable environment, verify counts/checksums, application boot and file authorization, and record restore duration. Do not use production credentials or copy real records into CI.
- Until a disposable restore is performed, backup/restore acceptance remains BLOCKED.
## Production readiness
- Verify APP_DEBUG=false, HTTPS, secure cookies, queue/scheduler, logging/monitoring, secrets, database migration plan and rollback procedure against the actual target environment.
- No production deployment is performed by CI. Production deployment requires separate explicit owner approval after all exit criteria pass.
## Status
Partial implementation only. CI passing proves the checked code and automated tests pass; it does not complete the full manual security, accessibility, performance, backup/restore or production-environment review.
