# Phase 17 — AI and advanced analytics
## Implemented foundations
- WorkspaceAnalytics derives deterministic personal and business reports through existing module access services. Personal insights include tasks, focus time, goals, career skills and knowledge counts. Business reports include authorized team and project metrics, CRM only for permitted staff, infrastructure only for authorized administrators, and finance only where FinanceAccess permits it; finance results remain grouped by ISO currency.
- Daily planning ranks incomplete tasks deterministically, excludes tasks with unresolved dependencies, and returns today's calendar blocks, active goals and career skill gaps.
- LocalAssistant is optional and disabled by default. It targets a fixed loopback Ollama endpoint, uses bounded prompts and responses, disables redirects/proxies and rejects a non-loopback peer. No external provider or paid API is required.
- IntelligenceController exposes scoped analytics, daily planning, deterministic summaries, optional note Q&A and read-only server configuration. AI questions are rate limited and use only matching unarchived notes in the current personal workspace. Email, phone and credential-like values are redacted before inference; workspace access is rechecked after inference.
- Browser settings cannot change model endpoint or activation; operators configure OLLAMA_ENABLED and OLLAMA_MODEL on the server.
## Security boundaries
No AI tool execution, shell, write operations, financial ledger details, attachments or cross-workspace retrieval are provided to the model. Retrieved note content is treated as untrusted context. AI output is informational only and does not perform actions.
## Verification
GitHub Actions must run the full PHPUnit suite, frontend build, Composer audit and NPM audit. Do not report runtime tests as passed until CI evidence exists. Ollama network inference and a formal penetration test have not been performed.
