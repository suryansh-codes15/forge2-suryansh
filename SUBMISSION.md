# Submission Checklist — Forge 2 / Edition 1 (PulseDesk)

Tick each and point to the in-repo path. Everything must be committed in THIS repo.

## Core Requirements

- [x] **Repo** — named `forge2-<myname>`; public on GitHub
- [x] **README** — exact run steps; `php artisan migrate --seed` works from a fresh clone
- [x] **Stack** — Laravel 12 + MySQL 8 · React 19 + Vite
- [x] **Multi-tenancy** — Org A cannot see Org B data; tenant derived from `auth()->user()->organization_id`
  - Middleware: `app/Http/Middleware/EnsureOrganizationContext.php`
  - Controller guard: `authorizeTicket()` → 403 on org mismatch

## Models (naming must match exactly)

- [x] `Organization` — `app/Models/Organization.php`
- [x] `User` — `app/Models/User.php`
- [x] `Ticket` — `app/Models/Ticket.php`
- [x] `Comment` — `app/Models/Comment.php`
- [x] `SlaPolicy` — `app/Models/SlaPolicy.php`
- [x] `ActivityLog` — `app/Models/ActivityLog.php`

## Agent Configs (secrets redacted)

- [x] `agents/hermes/hermes-config.yaml`
- [x] `agents/openclaw/openclaw.json`

## Evidence & Sprints

- [x] `agent-log.md` — human→Hermes→OpenClaw loop with real command outputs
- [x] `sprints/sprint-01.md`
- [x] `sprints/sprint-02.md`
- [x] `sprints/sprint-03.md`
- [ ] `slack-export/screenshots/` — Slack proof per channel *(needs screenshots)*
- [ ] `evidence/screenshots/` — App running screenshots:
  - [ ] 01-ticket-list.png
  - [ ] 02-ticket-detail.png
  - [ ] 03-dashboard.png
  - [ ] 04-openclaw-gateway.png
  - [ ] 05-ci-green.png

## CI / CD

- [x] `.github/workflows/ci.yml` — present
- [ ] Green run on the Actions tab *(needs push + GitHub Actions pass)*

## Submission Requirements

- [ ] Repo is public on GitHub
- [ ] PRs merged by ME (human); commit authors are the agents
- [ ] All model calls went through EastRouter

## Models Used

- Hermes (planning + code generation)
- OpenClaw (local inference gateway)

## Sprints Run: 3 (Sprint 01 — Backend, Sprint 02 — Frontend, Sprint 03 — Depth)
