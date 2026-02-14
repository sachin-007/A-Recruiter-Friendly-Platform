# Assessment Platform

Assessment Platform is a Laravel + Vue application for technical assessments.

It supports:
- Recruiter/Admin OTP login
- Candidate magic-link access
- Role-based access (admin, recruiter, author, candidate)
- Question bank (MCQ, coding, SQL, free-text)
- Test creation with sections and question attachment
- Invitations by email
- Candidate attempts with autosave
- Reports with PDF/CSV export
- CSV import for questions
- Admin user and organization settings

## Tech Stack
- Backend: Laravel 12, Sanctum, DomPDF
- Frontend: Vue 3, Pinia, Vue Router, Vite
- Database: MySQL or SQLite
- Mail: SMTP (Gmail supported)

## Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+ and npm
- MySQL 8+ (or SQLite)

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
Create the file once:
```bash
type nul > database\database.sqlite
```

4. Session/cache/queue recommendation for local
```env
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=database
```

5. Run migrations and seed data
```bash
php artisan migrate
php artisan db:seed
```

6. Start app
```bash
php artisan serve
npm run dev
```

Optional worker (if you use queued jobs):
```bash
php artisan queue:work
```

## Seeded Staff Users
After `php artisan db:seed`:
- `admin@example.com` (admin)
- `recruiter@example.com` (recruiter)
- `author@example.com` (author)

Password exists for seed consistency, but login flow is OTP-based for staff.

## Mail Configuration

### Development (no real email sending)
Use log mailer to inspect outgoing OTP/invitation emails in `storage/logs/laravel.log`:
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
- Enable 2-Step Verification on Google account
- Create an App Password and use it as `MAIL_PASSWORD`

Apply config changes:
```bash
php artisan optimize:clear
```

## Authentication Flows

- Staff login:
  1. `POST /api/v1/otp/send`
  2. `POST /api/v1/otp/verify`

- Candidate flow:
  1. Recruiter sends invitation
  2. Candidate opens `/test/{token}`
  3. Frontend verifies token via `POST /api/v1/magic-link/verify`
  4. Candidate starts and submits attempt

## Main API Groups
- Auth: OTP + magic-link
- Dashboard
- Questions + Tags + CSV imports
- Tests + sections + question attachment
- Invitations (single/bulk)
- Attempts (candidate lifecycle)
- Reports (view, PDF export, CSV export)
- Admin users + organization settings

Routes are defined in `routes/api.php`.

## Useful Commands
```bash
php artisan route:list
php artisan test
php artisan optimize:clear
```

## Troubleshooting

### 1) `Target class [ability] does not exist`
Ensure alias exists in `bootstrap/app.php`:
- `ability` => `\Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class`
- `abilities` => `\Laravel\Sanctum\Http\Middleware\CheckAbilities::class`

### 2) `Table '...sessions' doesn't exist`
Either:
- set `SESSION_DRIVER=file`, or
- generate session migration:
  ```bash
  php artisan session:table
  php artisan migrate
  ```

### 3) `The "tls" scheme is not supported`
Use:
```env
MAIL_SCHEME=smtp
```
or `MAIL_SCHEME=smtps` with port 465.

### 4) Invitation link opens wrong host
Set correct:
```env
APP_FRONTEND_URL=http://127.0.0.1:8000
```
Then run:
```bash
php artisan optimize:clear
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
