# Phase 18 — PWA, install, push and restricted offline

## VAPID push setup
1. Serve the application over HTTPS with PHP OpenSSL and cURL enabled.
2. Run php artisan webpush:generate-keys on a trusted server terminal.
3. Configure COMM_PUSH_ENABLED=true, VAPID_SUBJECT, VAPID_PUBLIC_KEY and VAPID_PRIVATE_KEY in the server environment. The private key is base64-encoded PEM; never commit it or expose it to the browser.
4. Run php artisan config:clear and php artisan migrate --force.
5. Run Laravel's scheduler every minute. communication:tick now delivers opted-in email and push notifications.
6. In a business workspace, users enable push preference and then enable each browser/device. Disabling push removes saved subscriptions for that workspace and cancels pending deliveries.

## Privacy and acceptance
Subscription endpoints and browser keys are encrypted at rest. Push endpoint hosts are allow-listed. Payloads contain generic text and a link to the authenticated notification center only; message bodies and workspace data are never sent. Expired endpoints (404/410) are removed. Membership and explicit opt-in are rechecked before delivery. Push uses VAPID ES256 and RFC 8291 aes128gcm. Run PHPUnit, frontend build, Composer audit and NPM audit. Real browser/device delivery still requires manual acceptance on the deployed HTTPS origin against actual browser push services. No production deployment is performed by this phase.

## Offline privacy
Only the public manifest, offline page, app icon and fingerprinted Vite assets may be cached. Navigation is network-only with a generic offline fallback. Authenticated pages, API responses and workspace records are never cached. No background sync or queued mutations are enabled.
