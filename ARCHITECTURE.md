# Architecture — PulseDesk

## Overview

PulseDesk is a multi-tenant SaaS help-desk platform built on **Laravel 11** (API) and **React + Vite** (SPA). Every piece of data belongs to an **Organization** (the tenant). Cross-tenant leakage is structurally impossible because the tenant identifier is always derived from the **authenticated user's session** — never from a client-supplied header or query parameter.

---

## Multi-tenancy & Authorization Approach

| Layer | Mechanism |
|---|---|
| **Middleware** | `EnsureOrganizationContext` — runs after `auth:sanctum`, reads `$request->user()->organization_id` and binds it as `app('organization_id')` |
| **Eloquent Scope** | `OrganizationScope` — automatically scopes models like `Notification` to filter queries exclusively within the current tenant context. |
| **Laravel Policies** | [TicketPolicy](file:///d:/final%20hackathon/backend/app/Policies/TicketPolicy.php) — centralizes authorization boundaries: <br>• *Tenant Isolation*: Ensures `user->organization_id === ticket->organization_id`. <br>• *Role Isolation*: Restricts customer roles to viewing/commenting only on their requested tickets, restricts deletes to admins only, and disables admin updates (assignment, status, priority) for customer roles. |

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
  priority[low|medium|high|urgent]
  response_due_at, resolution_due_at, responded_at, resolved_at
  csat_rating (unsigned tinyint nullable)
  timestamps

Comment
  id, ticket_id (FK), user_id (FK)
  type[reply|note], body, timestamps

Notification
  id, organization_id (FK), user_id (FK), ticket_id (FK)
  type[assigned|replied], title, message, read_at, timestamps

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
| POST | /api/tickets | ✅ | Create ticket (org-scoped, forces authenticated user for customer requests) |
| GET | /api/tickets/{id} | ✅ | Detail + comments + activity log |
| PATCH | /api/tickets/{id} | ✅ | Update status/priority/assigned_to/csat_rating (authorized via TicketPolicy) |
| DELETE | /api/tickets/{id} | ✅ | Soft delete (admins only) |
| GET | /api/tickets/{id}/comments | ✅ | List replies + internal notes |
| POST | /api/tickets/{id}/comments | ✅ | Add reply or note |
| GET | /api/notifications | ✅ | List in-app notifications for authenticated user (organization-scoped) |
| POST | /api/notifications/{id}/read | ✅ | Mark a notification as read |
| POST | /api/notifications/read-all | ✅ | Mark all user notifications as read |
| GET | /api/dashboard/stats | ✅ | Database-agnostic calculations: SLA breach, average first response (minutes), 7-day volume, workload |
| GET | /api/dashboard/agents | ✅ | Org agents + admins list |
| GET | /api/dashboard/customers | ✅ | Org customers list |

---

## Key Decisions

*   **Laravel Policies**: Replaced raw inline query checking with [TicketPolicy.php](file:///d:/final%20hackathon/backend/app/Policies/TicketPolicy.php) for structured and extensible security auditing.
*   **Customer Portal Separation**: Dynamically redirects customer roles from `/dashboard` to `/tickets` (represented as "My Tickets"), hides administrative metadata forms, disables modification select elements, and conceals the "Internal Note" commenting tab.
*   **Satisfaction Ratings (CSAT)**: Integrated customer post-resolution satisfaction scoring directly in `tickets` and logged the action in the activity feed.
*   **Client-Side CSV Export**: Encodes current query results as RFC-compliant CSV payloads directly in the browser to reduce server overhead.
*   **Database-Agnostic SQL**: Avoided MySQL-specific functions like `TIMESTAMPDIFF` or `NOW()` in favor of Carbon comparisons and PHP collection filters, ensuring full compatibility with automated SQLite testing databases.
