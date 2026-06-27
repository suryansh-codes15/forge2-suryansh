# Sprint 02 — Frontend: React SPA, Auth, Dashboard, Tickets, Comments

**Goal:** Build the complete React + Vite frontend — auth pages, protected routes, dashboard, tickets CRUD, ticket detail with comment/activity feed.

**Models:** Hermes (component design + orchestration), OpenClaw (code generation)
**Duration:** Sprint 02 (2026-06-27 PM)

---

## Issues / Tasks

- [x] #1 — Vite + React project scaffold (`npm create vite@latest`)
- [x] #2 — `index.css` — Premium dark design system: HSL palette, Inter font, glassmorphism cards, badge variants, spinner, scrollbar
- [x] #3 — `api.js` — Axios client with Bearer token interceptor, all 14 API helpers
- [x] #4 — `AuthContext.jsx` — React context: user + organization state, signIn/signOut
- [x] #5 — `App.jsx` — BrowserRouter, sidebar navigation, ProtectedRoute, AppLayout
- [x] #6 — `LoginPage.jsx` — Email/password login form, error handling
- [x] #7 — `RegisterPage.jsx` — Company + name + email + password, creates org in one step
- [x] #8 — `DashboardPage.jsx` — Stat cards (by status, by priority), agent workload table, recent tickets
- [x] #9 — `TicketsPage.jsx` — Filterable/searchable ticket list, paginated table with requester + assignee columns
- [x] #10 — `NewTicketPage.jsx` — Customer selector (requester_id), subject, priority, description form
- [x] #11 — `TicketDetailPage.jsx` — Ticket body, reply/note toggle form, comments feed, activity log, meta panel (status/priority/assignee dropdowns)
- [x] #12 — CSS: `.badge-pending`, `.comment-item.note/.reply`, `.activity-item`, `.ticket-detail` grid layout
- [x] #13 — Smoke test: Login → Dashboard shows 12 tickets by status/priority → Tickets list loads 12 records → Ticket detail shows requester name + description + activity log

## Outcome

**Shipped:**
- Complete React SPA with 6 pages, protected routes, premium dark UI
- Live at `http://localhost:5173` (Vite dev server)
- All frontend pages connect to backend at `http://localhost:8000/api`
- Ticket list shows real requester names (not raw strings)
- Status enum uses `pending` (not `in_progress`) matching backend spec
- Ticket detail: inline status/priority/assignee dropdowns update immediately via PATCH

**Slipped / moved to backlog:**
- Pagination next/prev controls (currently shows page info only)
- Customer-facing portal (agent-facing only for now)

**PRs:** #2 — Frontend SPA (merged by human)
