# Phase 19 — Security review notes
Review branch: `feature/phase-19`
Review date: 2026-11-28
Scope reviewed in source: workspace identity/context, finance access, personal knowledge access, AI retrieval/response, communication membership and webhook verification, integration outbound transport, infrastructure monitor DNS/IP pinning, task attachments, team hierarchy, project membership, PWA subscription storage, route middleware and current feature security tests.
## Findings and changes
### AI note metadata disclosure — fixed
The AI Q&A controller redacted note titles and bodies before sending context to the local model, but returned original note titles in the response's `sources` array. This could disclose email addresses or credential-like strings embedded in a title to the authenticated user and downstream UI. The response now applies the same redaction function to source titles. A regression test verifies both the model context and returned source metadata contain redacted email/password values.
### Workspace and resource authorization — reviewed, automated coverage exists
- Personal knowledge access checks both personal workspace type and owner identity; note reads/updates/deletes use workspace matching and policy authorization; folder/link/attachment operations are scoped.
- AI retrieval is limited to the authenticated user's personal workspace and bounded to five notes; deterministic business analytics are permission-filtered and omit finance for ordinary members; membership is rechecked after inference.
- Finance operations use FinanceAccess and workspace-scoped table queries; business finance is restricted to owner/admin and cross-workspace foreign keys are checked through scoped row lookups.
- Communication conversations are membership-scoped; mentions require active conversation participants; integration settings require owner/admin; webhook payloads are bounded, HMAC verified, time-window checked and idempotency keyed.
- Integration HTTP transport restricts HTTPS, rejects credentials/ports/fragments, validates public DNS answers, pins the resolved IP and disables redirects/proxies.
- Infrastructure monitor validates public hostnames and IPs, rejects non-public DNS answers and pins the selected address.
- Team and project tests cover role hierarchy, project membership and cross-workspace denial; private attachments use local storage and parent-resource checks.
- PWA subscription records are encrypted and scoped to user/workspace; the current phase has a subscription foundation, not an outbound push sender.
These are source-level reviews plus targeted tests, not a third-party penetration test or proof against every possible vulnerability.
## Outstanding checks
- Complete route-by-route and model-by-model authorization matrix for all controllers/resources.
- Execute automated static analysis and dependency audits in CI; dependency audits are already part of CI.
- Accessibility manual acceptance: keyboard-only, screen reader, contrast and zoom/reflow.
- Performance profiling against representative production-like data and measured browser bundle/interaction timings.
- Verify backup encryption, retention, off-site handling and restore in a disposable staging environment. Owner has not yet arranged staging; restore acceptance is blocked.
- Review actual production environment configuration, secret provisioning, queue/scheduler, TLS, logs/monitoring and rollback procedure before release.
## Release decision
Do not mark Phase 19 complete or deploy based only on CI. Keep PR #19 draft and stacked on Phase 18 until Phase 18 is merged and all Phase 19 exit criteria are evidenced.
