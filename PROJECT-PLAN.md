# Live Queue System — Project Plan

## Overview
A real-time queueing system for services like a school registrar, cashier, or clinic.
Students take a ticket from their phone (no login required), and staff call the next
number from a dashboard. A public display screen shows the current "now serving" status.
Built as a learning project — first Laravel project for the developer.

## Tech Stack
- **Backend:** Laravel 13, PHP 8.4
- **Frontend:** React (via Inertia.js), Tailwind CSS
- **Database:** MySQL (via XAMPP locally)
- **Real-time:** Laravel Reverb (WebSockets) + Laravel Echo on the frontend
- **Auth:** Laravel Breeze (React starter kit) — staff only
- **Local dev:** Laravel Herd (serves the app at `queue-system.test`), XAMPP (MySQL only)
- **Build tool:** Vite

## User Roles

### 1. Student (anonymous, no login)
- Visits the queue page (e.g. via a QR code posted at the counter).
- Picks a service (e.g. Registrar, Cashier).
- Taps "Get Ticket" → receives a ticket number (e.g. `A-042`).
- Sees live position updates (people ahead, currently serving number) via WebSocket.
- Tracked via browser session (cookie), NOT a user account.
- **Rule:** a student can only hold ONE active ticket (`waiting` or `serving` status)
  per service at a time. If they already have one, show it instead of creating a new one.

### 2. Staff (logged in via Breeze)
- Logs in to a dashboard tied to a specific counter (e.g. "Window 2").
- Sees the current queue for their assigned service.
- Clicks "Call Next" → picks the oldest `waiting` ticket for that service, marks it
  `serving`, assigns it to their counter, and broadcasts the update.
- Can mark a ticket as `done` or `skipped`.

### 3. Public Display (no login, read-only)
- A big-screen view (e.g. a TV in the waiting area).
- Shows "Now Serving: A-042 at Window 2" live, updating via WebSocket with no refresh.

## Core Data Model

### `services`
- `id`
- `name` (e.g. "Registrar", "Cashier")
- `prefix` (e.g. "A", "B" — used in ticket numbers like A-042)
- `is_active` (boolean)

### `counters`
- `id`
- `service_id` (FK → services)
- `name` (e.g. "Window 1")
- `is_active` (boolean)

### `tickets`
- `id`
- `service_id` (FK → services)
- `number` (integer, resets daily per service)
- `session_id` (string — the anonymous browser session identifier)
- `status` (enum: `waiting`, `serving`, `done`, `skipped`)
- `counter_id` (FK → counters, nullable until called)
- `created_at`, `updated_at`
- **Unique constraint:** (`service_id`, `date`, `number`) — prevents duplicate numbers
  even under race conditions.

### `queue_counters` (helper table for atomic numbering)
- `id`
- `service_id` (FK → services)
- `date`
- `last_number` (integer)
- Used with `lockForUpdate()` inside a DB transaction when issuing a new ticket number,
  to prevent two students getting the same number at the same time (the "race
  condition" problem).

## Key System Design Concern: Concurrency

Two problems to solve with **pessimistic locking**:

1. **Issuing tickets:** two students tap "Get Ticket" at the same instant. Solved by
   locking the `queue_counters` row for that service+date with `lockForUpdate()` inside
   a `DB::transaction()`, incrementing, then creating the ticket. Backed by a unique
   index as a safety net.

2. **Calling next:** two counters click "Call Next" for the same service at the same
   time. Solved by using `lockForUpdate()->skipLocked()` when selecting the oldest
   `waiting` ticket, so simultaneous calls never grab the same ticket.

A good test for this: fire many concurrent ticket requests (e.g. in a test or with a
tool like `k6` or a simple loop of parallel HTTP requests) and assert no duplicate
ticket numbers are ever created.

## Real-Time Flow (Reverb + Echo)

1. Staff clicks "Call Next" → backend updates the ticket's status and counter →
   fires a `TicketCalled` event via `broadcast()`.
2. Reverb pushes this event over WebSocket.
3. Both the student's ticket-status page and the public display page are subscribed
   (via Laravel Echo) to a channel for that service, and update instantly without
   a page refresh.

## Suggested Build Order

1. `laravel new` + Breeze React install (already done).
2. Migrations + models: `services`, `counters`, `tickets`, `queue_counters`.
3. Seeder: a couple of sample services and counters for testing.
4. Ticket creation flow (student side): controller + React page, session-based
   duplicate check, the locking logic described above.
5. Staff dashboard: login-protected, list of waiting tickets, "Call Next" button.
6. Reverb setup: install, configure `.env`, create the `TicketCalled` event,
   broadcast it, listen for it with Echo on the frontend.
7. Public display page: minimal, read-only, large text, auto-updating.
8. Daily reset: scheduled task (`php artisan schedule`) to reset `queue_counters`
   at midnight, or scope everything by date so no explicit reset job is needed.
9. Polish: styling with Tailwind, sound/visual alert when a student's number is
   close, basic admin CRUD for services/counters.

## Deployment (later, once working locally)
- **Laravel Cloud** (free trial, $5 credit): easiest, includes managed Reverb,
  MySQL, and a `.free.laravel.cloud` domain. Good for a demo.
- **VPS** (DigitalOcean/Vultr/Hetzner, ~$5–12/month): more setup (Nginx, Supervisor
  for Reverb + queue worker, Certbot for HTTPS), but more educational and full
  control. GitHub Student Developer Pack may provide free DigitalOcean credit.
- Note: Vercel does NOT work for this — it can't run Reverb or a persistent
  Laravel process.

## Notes for Claude Code
- This is the developer's first Laravel project — explain Laravel-specific
  conventions (migrations, Eloquent, Inertia page structure) briefly as you go,
  don't assume prior Laravel knowledge.
- Prioritize getting a working vertical slice first (one service, one counter,
  ticket creation + call next, no real-time yet) before adding Reverb.
- Keep the concurrency-safe ticket issuance logic as a well-isolated, well-tested
  piece of code — it's the standout technical feature of this project.
