# Phase 18 — PWA, install, push and restricted offline
## Scope
- Installable web app manifest and service worker.
- Offline fallback page and deliberately restricted cache.
- Browser push subscription registration, encrypted credential storage, workspace/user scoping and opt-in preference checks.
- Notification click opens the authenticated in-app notification center.
## Offline privacy
Only the public manifest, offline page, app icon and fingerprinted Vite build assets may be cached. HTML navigation is network-only with a generic offline fallback. Authenticated pages, API responses, notes, messages, finance, attachments, session data and workspace records are never cached. No background sync or queued mutations are enabled.
## Push
Browser subscription endpoints and encryption keys are encrypted at rest using Laravel Crypt; endpoint hashes are used for lookup. Registration requires an authenticated active business workspace and an explicit push preference. A VAPID-based sender and end-to-end push delivery still require a vetted Web Push implementation and VAPID key provisioning; this phase currently establishes subscription storage and browser notification handling, not a claim of end-to-end delivery.
## Acceptance and security
Run PHPUnit, frontend build, Composer audit and NPM audit. Test install prompt fallback, service-worker scope, static-only cache, cross-workspace subscription denial, preference revocation and encrypted-at-rest subscription material. Browser/device install and live push delivery require manual acceptance. No production deployment.
