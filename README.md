# Assessment Platform

Assessment Platform is a Laravel + Vue application for technical assessments.

## Core Capabilities
- Staff login with email + OTP (`super_admin`, `admin`, `recruiter`, `author`)
- Candidate access by magic link (single-use token flow)
- Role/ability-based authorization on API and UI
- Question bank: MCQ, coding, SQL, free-text
- Tests with sections and question attachment
- Single and bulk candidate invitations by email
- Candidate assessment with timer + autosave
- Reports with section-wise breakdown
- Report export as PDF and CSV
- CSV import for questions (validation + error reporting)
- Organization management (super admin) + organization settings
- Dashboard analytics with Chart.js (including super-admin organization overview)

## Reports (Current Behavior)
- Report list (`/reports`) includes candidate, test, score, and invitation status.
- For `super_admin`, report list also shows organization.
- Report list and report detail show "Shared By" (name + email of inviter/owner).
- PDF and CSV export include organization and shared-by context for traceability.

## Tech Stack
- Backend: Laravel 12, Sanctum, DomPDF
- Frontend: Vue 3, Pinia, Vue Router, Vite
- Charts: Chart.js + vue-chartjs
- Database: MySQL or SQLite
- Mail: SMTP (Gmail supported)

## Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+ and npm
- MySQL 8+ (optional if using SQLite)

## Local Setup

1. Install dependencies
```bash
composer install
npm install
```

2. Create environment file and app key
```bash
cp .env.example .env
php artisan key:generate
```

3. Configure `.env`

Minimum required:
```env
APP_NAME="Assessment Platform"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_FRONTEND_URL=http://127.0.0.1:8000
```

### Database Option A: MySQL
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3310
DB_DATABASE=assessment_db
DB_USERNAME=root
DB_PASSWORD=
```

### Database Option B: SQLite
```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

Create SQLite file once:
```bash
type nul > database\database.sqlite
```

4. Session/cache/queue recommendation for local
```env
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=database
```

5. Run migrations and seed
```bash
php artisan migrate
php artisan db:seed
```

6. Start backend + frontend
```bash
php artisan serve
npm run dev
```

Optional worker (for queued jobs):
```bash
php artisan queue:work
```

## Seeded Staff Users
After `php artisan db:seed`:
- `superadmin@example.com` (`super_admin`)
- `admin@example.com` (`admin`)
- `recruiter@example.com` (`recruiter`)
- `author@example.com` (`author`)

Password exists for seeding consistency, but staff auth flow is OTP-based.

## Mail Configuration

### Development (no real email sending)
Use log mailer and inspect `storage/logs/laravel.log`:
```env
MAIL_MAILER=log
```

### Gmail SMTP (real email sending)
```env
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-16-char-app-password
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

Gmail requirements:
- Enable 2-Step Verification
- Generate App Password and use it in `MAIL_PASSWORD`

Apply env/config changes:
```bash
php artisan optimize:clear
```

## Authentication Flows

### Staff
1. `POST /api/v1/otp/send`
2. `POST /api/v1/otp/verify`

### Candidate
1. Recruiter/admin sends invitation
2. Candidate opens `/test/{token}`
3. Frontend verifies token: `POST /api/v1/magic-link/verify`
4. Candidate starts and submits attempt

## Main API Groups
- Auth: OTP + magic-link
- Dashboard
- Questions + tags + CSV imports
- Tests + sections + question attachment
- Invitations (single/bulk)
- Attempts (candidate lifecycle)
- Reports (view, PDF export, CSV export)
- User management
- Organization settings + organization management

Routes: `routes/api.php`

## Useful Commands
```bash
php artisan route:list
php artisan test
php artisan optimize:clear
```

## Troubleshooting

### 1) `Target class [ability] does not exist`
Ensure middleware aliases exist in `bootstrap/app.php`:
- `ability` => `\Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class`
- `abilities` => `\Laravel\Sanctum\Http\Middleware\CheckAbilities::class`

### 2) `Table '...sessions' doesn't exist`
Either:
- set `SESSION_DRIVER=file`, or
- create and migrate session table:
  ```bash
  php artisan session:table
  php artisan migrate
  ```

### 3) `The "tls" scheme is not supported`
Use:
```env
MAIL_SCHEME=smtp
```
or use `MAIL_SCHEME=smtps` with port `465`.

### 4) Invitation link opens wrong host
Set:
```env
APP_FRONTEND_URL=http://127.0.0.1:8000
```
Then run:
```bash
php artisan optimize:clear
```

### 5) UI changes not visible after code update
Rebuild/restart frontend assets:
```bash
php artisan optimize:clear
npm run dev
```

If still stale, hard refresh browser (`Ctrl + F5`) or run:
```bash
npm run build
```

## Snapshot Seeder (Optional)
To seed from `database/seeders/data/current_db_snapshot.json`:
```env
SEED_FROM_SNAPSHOT=true
```
Then run:
```bash
php artisan db:seed
```
