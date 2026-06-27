# Sprint 03 — Depth: SLA Policies, Timers, Notifications, Claiming & Role policies

**Goal:** Implement remaining SHOULD features: SLA policies & timers, notifications bell/drawer, Claim Ticket action, activity logging on comments, and role-based policies.

**Models:** Hermes (orchestration), OpenClaw (code generation)
**Duration:** Sprint 03 (2026-06-27 PM)

---

## Issues / Tasks

- [x] #1 — Database schema additions: SLA fields (response_due_at, resolution_due_at, responded_at, resolved_at) and notifications table.
- [x] #2 — Notifications model (`Notification.php`) and controller (`NotificationController.php`) with multi-tenancy.
- [x] #3 — Automatic SLA target resolver inside `Ticket.php` Eloquent events.
- [x] #4 — Refactor Comment creation to log comment activities, trigger SLA responded_at, and dispatch user notifications (replies, note mentions).
- [x] #5 — Refactor ticket updates to trigger resolved_at, assignment notifications, and priority-based SLA recalibration.
- [x] #6 — Database-agnostic metrics calculations for average response time, SLA breach rate, and ticket volume per day.
- [x] #7 — Custom visual bar chart and new statistics cards on the frontend Dashboard page.
- [x] #8 — Claim Ticket button in header of TicketDetailPage.jsx.
- [x] #9 — Live SLA remaining countdown/breach warning badges.
- [x] #10 — In-app notifications panel with unread indicators and "Mark all read" controls in App.jsx.
- [x] #11 — Policies folder and `TicketPolicy.php` enforcing role and tenant boundaries (Customer vs Agent vs Admin).
- [x] #12 — API routes refactoring to delegate authorization checks to standard Gate policies.
- [x] #13 — Feature tests verification (`SlaAndNotificationTest.php` asserting SLA dates, notifications, and policy rules).

## Outcome

**Shipped:**
- A robust multi-tenant SLA engine automatically enforcing targets by organization rules.
- Sleek, live-updating SLA badges (OK, Warning, Breached) and instant Ticket Claiming.
- Interactive, multi-tenant in-app notification center.
- Enhanced secure architecture utilizing Laravel Policies instead of controller-level code.
- Staggered demo seeder database for realistic dashboard analytics.
- All 11 tests passing successfully.

**PRs:** #3 — Depth features & Policy authorization (merged by human)
