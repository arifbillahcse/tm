# Security and privacy
## Threat model
Unauthorized workspace access, account takeover, leaked notes/finance, malicious uploads, XSS, injection, CSRF, credential exposure, webhook spoofing, dependency compromise, SSRF through monitoring/integrations and AI prompt injection.
## Required controls
- Invitation-only registration, password hashing, rate limits, email verification where configured, 2FA, secure session cookies and session revocation.
- Explicit policies for each resource and action; membership-scoped queries for list/search/export, background jobs and notifications. Deny by default; test IDOR and privilege escalation.
- Form validation, parameterized queries, escaped templates, sanitized rich text, CSRF protection, restrictive security headers and HTTPS. Production APP_DEBUG=false.
- Upload size/type validation, random storage names, private storage, authorized downloads; prohibit executable files.
- Encrypt integration credentials; keep APP_KEY, tokens and .env out of Git; avoid secrets in logs. Least-privilege tokens and rotation. Verify webhook signatures and timestamps where available. Restrict outbound requests; block private/link-local endpoints unless explicitly configured for approved internal monitoring.
- AI retrieval must enforce workspace policies before and after retrieval, treat external content as untrusted, require confirmation for consequential actions, and avoid sending private data to external providers by default.
- Limit offline caching of private records; clear private local state on logout. Audit sensitive mutations without recording secrets. Provide retention/deletion/export controls.
- Encrypted backups, restore tests, dependency audits, CI regression tests, review before production deploy. No covert employee tracking.
## Repository
Repository was public at initial planning. Private visibility recommended before sensitive internal details are added; never commit actual personal data regardless of visibility. No credentials or real records in fixtures.
