# Functional scope
All modules require workspace authorization, validation, accessible responsive UI, appropriate audit history, pagination and tests. Features below are planned, not implemented.
| Phase | Module | Requirements |
|---|---|---|
| 3 | Identity | Invitations, login/logout, password reset, 2FA, profiles, session management, workspace switching, owner/admin/manager/member roles |
| 4 | Shell | Dashboard widgets, sidebar, themes, global search, quick actions, notifications, keyboard navigation |
| 5 | Tasks | Inbox, My Day, upcoming/overdue, subtasks, priorities, tags, recurring tasks, dependencies, attachments, list/Kanban, reminders, history |
| 6 | Calendar | Day/week/month, time blocks, meetings, repeating events, drag/drop, task scheduling, templates, timezone |
| 7 | Goals | Year/quarter/month goals, milestones, measurable results, linked tasks, habits, streaks, review, vision board |
| 8 | Time | Pomodoro, deep work, breaks, manual time logs, task/project links, billable time, reports |
| 9 | Knowledge | Rich text/Markdown, folders, tags, notes, backlinks, bookmarks, ideas, snippets, prompts, journal, decisions, research, attachments, revision history, export |
| 10 | Career | Roadmaps, skill matrix, courses, certifications, jobs, interviews, CVs, portfolio, IELTS, labs, contacts, skill gaps |
| 11 | Team | Profiles, departments, responsibilities, attendance, leave approvals, daily reports, onboarding, employee documents and announcements |
| 12 | Projects | Members, templates, assigned tasks, Kanban, milestones, Gantt, dependencies, approvals, discussion, budget, history, archives |
| 13 | CRM | Contacts, companies, leads, pipeline, deals, follow-ups, proposals, quotations, notes, segmentation, sales reporting |
| 14 | Finance | Personal and business ledgers, BDT/USD, budgets, savings, recurring transactions, receipts, salaries, bonuses, deductions, invoices, exports, profitability |
| 15 | Technology | Repositories, products, bugs, releases, deployments, servers, domains, SSL, renewals, uptime/resource metrics, incidents, security/backup checklists |
| 16 | Communication | Chat, mentions, announcements, email, push, Telegram, manual WhatsApp share links, optional Gmail/Calendar/Drive/GitHub/WHMCS/RSS/webhooks |
| 17 | Intelligence | SQL-based analytics, optional Ollama summaries, planning, task prioritization, knowledge Q&A and goal insights; permission-aware retrieval |
| 18 | Mobile | PWA install, mobile navigation, supported push, limited offline features and safe synchronization |
## Acceptance examples
- A member cannot read or mutate another workspace's tasks by guessing IDs.
- A recurring task produces at most one instance per scheduled occurrence.
- A completed focus session records actual elapsed time and can be corrected with history.
- A private note does not appear in shared search or AI retrieval.
- A WhatsApp action generates a draft; sending requires a user action.
- Finance totals are calculated separately by currency unless explicit exchange rates are stored.
