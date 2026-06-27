# PulseDesk — Forge 2 / Edition 1

> Multi-tenant SaaS help-desk platform **built by orchestrating Hermes + OpenClaw over Slack**.

[![CI](https://github.com/suryansh-codes15/forge2-suryansh/actions/workflows/ci.yml/badge.svg)](https://github.com/suryansh-codes15/forge2-suryansh/actions)

## Stack

| Layer | Technology |
|-------|-----------|
| Backend | **Laravel 12** · PHP 8.2 · MySQL 8 · Laravel Sanctum |
| Frontend | **React 19** · Vite · Vanilla CSS |
| Auth | Sanctum token-based (SPA) |
| Multi-tenancy | `organization_id` on every row, derived from `auth()->user()` |

## EastRouter Models Used

- **Hermes** (planning / product owner): orchestration + code review
- **OpenClaw** (coding): local inference gateway — code generation + debugging

---

## How to Run (from a fresh clone)

### Backend (Laravel + MySQL)

```bash
cd backend
cp .env.example .env
# Edit .env: set DB_DATABASE, DB_USERNAME, DB_PASSWORD
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve                 # → http://localhost:8000
```

### Frontend (React + Vite)

```bash
cd frontend
npm install
npm run dev                       # → http://localhost:5173
```

> **Note:** The frontend `.env` already has `VITE_API_URL=http://localhost:8000/api`. No changes needed for local dev.

---

## Demo Logins (seeded automatically)

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@democorp.test | password |
| Agent | bob@democorp.test | password |
| Agent | carol@democorp.test | password |
| Customer | dave@customer.test | password |
| Customer | eve@customer.test | password |

---

## Seeded Data

The seeder creates:
- **1 organization** — Demo Corp
- **5 users** — 1 admin, 2 agents, 2 customers
- **12 tickets** — mix of all statuses and priorities
- **18 comments** — replies + internal notes
- **25 activity logs** — created, assigned, status_changed events
- **4 SLA policies** — one per priority level

---

## Live URL

Runs locally per the steps above.

---

## Evidence in this Repo

| Path | Contents |
|------|----------|
| `agents/hermes/hermes-config.yaml` | Hermes agent config (secrets redacted) |
| `agents/openclaw/openclaw.json` | OpenClaw local gateway config (secrets redacted) |
| `agent-log.md` | Human → Hermes → OpenClaw loop with real outputs |
| `sprints/` | Sprint-01 (backend), Sprint-02 (frontend) docs |
| `slack-export/screenshots/` | Slack proof per channel |
| `evidence/screenshots/` | App running, CI green screenshots |
| `ARCHITECTURE.md` | Data model, API routes, multi-tenancy approach |
| `SUBMISSION.md` | Full submission checklist |
