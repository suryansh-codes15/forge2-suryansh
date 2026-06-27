# Architecture — PulseDesk

## Overview

PulseDesk is a multi-tenant SaaS help-desk platform built on **Laravel 12** (API) and **React + Vite** (SPA). Every piece of data belongs to an **Organization** (the tenant). Cross-tenant leakage is structurally impossible because the tenant identifier is always derived from the **authenticated user's session** — never from a client-supplied header or query parameter.

---

## Multi-tenancy Approach

| Layer | Mechanism |
|---|---|
| **Middleware** | `EnsureOrganizationContext` — runs after `auth:sanctum`, reads `$request->user()->organization_id` and binds it as `app('organization_id')` |
| **Controllers** | Ticket queries always filter by `$request->user()->organization_id`. Route-model-bound `{ticket}` is authorized via `authorizeTicket()` which compares `ticket->organization_id` with `user->organization_id` and aborts 403 on mismatch |
| **No Global Scopes** | Intentionally avoided in favour of explicit controller-level scoping for predictability and debuggability |

**Tenant derivation**: On login, Laravel Sanctum issues a personal access token bound to a `User` row. That `User` row carries `organization_id`. Every subsequent request resolves the organization from `auth()->user()->organization_id`.

---

## Data Model

```
Organization
  id, name, slug, plan, timestamps

User
  id, organization_id (FK), name, email, password, role[admin|agent|customer], timestamps

Ticket
  id, organization_id (FK), requester_id (FK→users), assigned_to (FK→users nullable)
  subject, description, status[open|pending|resolved|closed]
  priority[low|medium|high|urgent], timestamps

Comment
  id, ticket_id (FK), user_id (FK)
  type[reply|note], body, timestamps

SlaPolicy
  id, organization_id (FK)
  priority[low|medium|high|urgent]
  response_time_hours, resolution_time_hours, timestamps

ActivityLog
  id, ticket_id (FK), user_id (FK)
  action, meta (JSON nullable), timestamps
```

---

## API Routes

All routes under `/api`. Auth routes are public; everything else requires `auth:sanctum` + `EnsureOrganizationContext`.

| Method | Path | Auth | Notes |
|--------|------|------|-------|
| POST | /api/auth/register | Public | Creates org + admin user |
| POST | /api/auth/login | Public | Returns Sanctum token + user + org |
| GET | /api/auth/me | ✅ | Current user + org |
| POST | /api/auth/logout | ✅ | Revokes current token |
| GET | /api/tickets | ✅ | Paginated, filterable by status/priority/search |
| POST | /api/tickets | ✅ | Create ticket (org-scoped) |
| GET | /api/tickets/{id} | ✅ | Detail + comments + activity log |
| PATCH | /api/tickets/{id} | ✅ | Update status/priority/assigned_to |
| DELETE | /api/tickets/{id} | ✅ | Soft delete |
| GET | /api/tickets/{id}/comments | ✅ | List replies + internal notes |
| POST | /api/tickets/{id}/comments | ✅ | Add reply or note |
| GET | /api/dashboard/stats | ✅ | By-status, by-priority, agent workload, recent tickets |
| GET | /api/dashboard/agents | ✅ | Org agents + admins list |
| GET | /api/dashboard/customers | ✅ | Org customers list |

---

## Key Decisions

- **Laravel 12 + Sanctum**: Token-based API auth — stateless, works perfectly with a decoupled React SPA.
- **`pending` over `in_progress`**: Matches the SLA spec (open → pending → resolved/closed).
- **`description` over `body`**: Explicit field name avoids confusion with `Comment.body`.
- **`requester_id` (FK) over `customer_name/email` strings**: Proper relational integrity; requester is a `User` with role=customer.
- **`ActivityLog` model** (not `TicketActivity`): Follows the exact model naming required by the submission spec.
- **No Eloquent Global Scopes**: Scoping happens explicitly in controllers, making the codebase auditable.
- **Seeder** creates: 1 org, 1 admin, 2 agents, 2 customers, 12 tickets, 18 comments, 25 activity logs, 4 SLA policies.
