# Development status
- Phases 0–16: previously implemented; confirm each merged PR and release baseline against Git history before production sign-off.
- Phase 17: merged into develop as PR #17 (squash commit f97d216); CI run 46040715206 passed 174 tests / 1,223 assertions and Composer/NPM dependency audits.
- Phase 18: PR #18 remains an open draft. Latest verified commit f3bfdd0; CI run 46044044982 passed. PWA install/offline boundaries and encrypted subscription storage are implemented. VAPID sender/key provisioning and real-device install/push acceptance remain outstanding. Not merged.
- Phase 19: PR #19 remains a draft stacked on feature/phase-18. CI run 46052095264 passed 181 tests / 1,265 assertions and Composer/NPM audits. Added baseline security headers, cache/HSTS/accessibility regression checks, and batched timezone-aware dashboard habit statistics. Further full application security, accessibility, performance, backup/restore and target-environment reviews remain outstanding.
- Backup/restore staging environment: owner says it will be arranged later; restore drill is blocked until a disposable environment is available.
- Phase 20: not started.
No production deployment has been performed. Owner approval is required for any production deployment after release criteria are met; prior approval does not waive the phase exit criteria.
