# Sprint 01 — Backend: Migrations, Models, API, Auth

**Goal:** Build the complete Laravel 12 backend — all 6 models, migrations, middleware, 14 API routes, seeder, and smoke-tested endpoints.

**Models:** Hermes (planning + orchestration), OpenClaw (code generation via local inference)
**Duration:** Sprint 01 (2026-06-27 AM)

---

## Issues / Tasks

- [x] #1 — Database setup: `pulsedesk` schema on MySQL 8
- [x] #2 — Migrations: organizations, users, tickets, comments, activity_logs, sla_policies, personal_access_tokens
- [x] #3 — Models: Organization, User, Ticket, Comment, ActivityLog, SlaPolicy (exact names as per spec)
- [x] #4 — `OrganizationScope` middleware — derives tenant from `auth()->user()->organization_id`
- [x] #5 — `EnsureOrganizationContext` middleware — binds `app('organization_id')` after auth
- [x] #6 — AuthController: register (creates org + admin user), login, logout, me
- [x] #7 — TicketController: CRUD with org-scoped authorization
- [x] #8 — CommentController: replies + internal notes per ticket
- [x] #9 — DashboardController: stats, agents, customers endpoints
- [x] #10 — `routes/api.php`: all 14 routes registered
- [x] #11 — DatabaseSeeder: 1 org, 5 users, 12 tickets, 18 comments, 25 activity logs, 4 SLA policies
- [x] #12 — Smoke test: `POST /api/auth/login` → 200 with token, org, user
- [x] #13 — Smoke test: `GET /api/tickets` → 12 tickets with requester + assignee relations

## Outcome

**Shipped:**
- Full Laravel 12 API backend, all 14 routes, 6 models matching exact spec naming
- Multi-tenancy enforced at controller level via `authorizeTicket()` — 403 on cross-org access
- `php artisan migrate:fresh --seed` completes in < 5s from clean state
- All API smoke tests pass (login, tickets list, ticket detail with relations)

**Slipped / moved to Sprint 02:**
- React frontend (planned for Sprint 02)

**PRs:** #1 — Backend foundation (merged by human)
