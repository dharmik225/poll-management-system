# Poll Management System

Real-time poll application built with **Laravel 12**, **Livewire 4**, **Flux UI**, and **Laravel Reverb** (WebSockets).

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 12 (PHP 8.4) |
| Frontend | Livewire 4 + Flux UI + Tailwind CSS 4 |
| Auth | Laravel Fortify (with 2FA) |
| Real-Time | Laravel Reverb + Laravel Echo |
| Testing | Pest 4 |

## Features

**Admin** — Poll CRUD with draft/published/archived states, real-time vote dashboard, voter analytics with search & filter, shareable poll links, poll expiry dates.

**Public** — Vote on polls via shareable link, live-updating results, duplicate vote prevention (by user ID + IP address) enforced at both application and database level.

**Security** — Role-based access with admin middleware, 2FA with recovery codes, email verification, rate limiting (5 attempts/min), soft deletes.

## Setup

```bash
git clone https://github.com/dharmik225/poll-management-system.git
cd poll-management-system
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan install:broadcasting
```

Configure `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=poll_management
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=<your-app-id>
REVERB_APP_KEY=<your-app-key>
REVERB_APP_SECRET=<your-app-secret>
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

Run migrations and seed:

```bash
php artisan migrate
php artisan db:seed   # Creates admin: admin@yopmail.com / Password
```

## Running the Application

This runs four processes concurrently:

| Process | Command | Port |
|---------|---------|------|
| Laravel Server | `php artisan serve` | 8000 |
| Queue Worker | `php artisan queue:listen` | — |
| Vite Dev Server | `npm run dev` | 5173 |
| Reverb WebSocket | `php artisan reverb:start` | 8080 |

### Verify WebSocket is Working

```bash
# Terminal should show:
# Starting server on 0.0.0.0:8080...

# Test connection:
curl -i http://localhost:8080

# With --debug flag, you'll see live connection/event logs
```

## Running Tests

```bash
# Full test suite
composer test

# Or directly
php artisan test

# With coverage
php artisan test --coverage

# Load testing with k6
k6 run tests/k6/load-test.js
```

## Production Build

```bash
npm run build
```

For production, run Reverb with **Supervisor** to keep it alive:

```ini
[program:reverb]
command=php /path/to/artisan reverb:start
autostart=true
autorestart=true
user=ploi
```

## How It Works

### Real-Time Vote Flow

```
User votes → Laravel creates Vote record
           → Dispatches VoteRecorded event (ShouldBroadcastNow)
           → Reverb pushes to WebSocket channel: poll.{id}
           → All connected browsers receive update instantly
```

Livewire components listen via `#[On('echo:poll.{poll.id},VoteRecorded')]` — no custom JavaScript needed.

### Poll Status Lifecycle

```
DRAFT → PUBLISHED → ARCHIVED
```

- **Draft** — Editable, not visible to public
- **Published** — Accepts votes, visible via shareable link
- **Archived** — Read-only, no new votes

### Duplicate Vote Prevention

Two layers of protection:

1. **Application** — Checks existing vote by `user_id` (authenticated) or `ip_address` (anonymous) inside a DB transaction
2. **Database** — Unique composite indexes on `(poll_id, user_id)` and `(poll_id, ip_address)` catch race conditions

## Database Schema

```
users
├── polls (one-to-many)
│   ├── poll_options (one-to-many)
│   │   └── votes (one-to-many)
│   └── votes (one-to-many)
└── votes (one-to-many, nullable for anonymous)
```

Key tables: `users`, `polls` (with slug, status enum, expires_at), `poll_options` (with sort_order), `votes` (with user_id nullable + ip_address).

## AI Assistance

The following areas were developed with AI assistance:

- **Poll CRUD & View Page Design** — UI layout, component structure, and Blade template design
- **Vote Page & Effects Design** — Voting interface, progress bar animations, and real-time result transitions
- **Test Cases** — Writing Pest test cases for voting logic and duplicate prevention
- **Database Design** — Schema structure, indexing strategy, and unique constraint planning

All core logic, authentication flow, WebSocket integration, and architecture decisions were implemented manually.