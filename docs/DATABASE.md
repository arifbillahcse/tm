# Logical database design
Proposed schema; not migrations. Refine field types and constraints during each phase. Use unsigned bigint primary keys, foreign keys, timestamps, workspace_id on tenant-owned records, indexes on workspace/status/due_at and unique membership/occurrence constraints. Store dates in UTC except date-only values. Enforce workspace consistency through composite references where practical plus application policies. Use decimal amounts and ISO currency codes; never floats for money.
| Domain | Proposed tables and key relations |
|---|---|
| Identity | users, workspaces(owner_id,type), workspace_memberships(workspace_id,user_id,role), invitations, sessions; unique workspace/user |
| Tasks | tasks(workspace_id,creator_id,assignee_id,project_id,parent_id,status,priority,due_at), task_dependencies, tags, task_tags, recurrence_rules, task_occurrences, reminders |
| Planning | calendar_events, time_blocks, planning_templates |
| Goals/habits | goals, goal_milestones, goal_tasks, habits, habit_entries; unique habit/date |
| Time | focus_sessions, time_entries(task_id,project_id,user_id,start_at,end_at,billable) |
| Knowledge | folders, notes, note_revisions, note_links, bookmarks, snippets, journal_entries, ideas, attachments |
| Career | career_roadmaps, skills, user_skills, courses, learning_sessions, certifications, job_applications, interview_questions, portfolio_items, language_practice, professional_contacts |
| Team | employee_profiles, departments, attendance_entries, leave_requests, daily_reports, performance_reviews |
| Projects | projects, project_members, milestones, project_comments, approvals, project_templates |
| CRM | crm_companies, contacts, leads, deals, pipeline_stages, followups, proposals |
| Finance | ledger_accounts, transactions, transaction_categories, budgets, savings_goals, payroll_records, invoices, invoice_items, exchange_rates |
| Technology | software_products, repositories, issues, releases, deployments, servers, domains, monitoring_checks, monitoring_samples, incidents |
| Communication | conversations, conversation_members, messages, announcements, notifications, push_subscriptions |
| Integrations | integrations, webhook_events, sync_cursors, ai_requests (minimal metadata only) |
| Shared | audit_events, attachment_links, jobs, failed_jobs, cache |
## Access and lifecycle
Personal workspace is private to its owner; shared membership roles are workspace-specific. Do not grant global owner bypass by default. Scope relations and polymorphic references; validate attachments' owning workspace. Store only encrypted tokens, not plaintext secrets. Use soft deletion selectively; define retention and deletion rules, avoid silently cascading financial/audit history. Index search fields; paginate results. Design migrations in dependency order with rollback tests. Seed synthetic records only. Use constraints and transactions to avoid duplicate recurring events, attendance overlap and inconsistent ledger entries.
