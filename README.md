# Office Visitor Manager — Laravel 12

A reception-desk application for recording office visitors, the employee and company they are meeting, their purpose, photo, ID-card number, check-in time, and checkout time.

## Included

- Secure receptionist login
- Dashboard with today's visits, people currently inside, employee count, and company count
- Company CRUD with safe deletion rules
- Employee CRUD linked to companies
- Fast visitor check-in with camera/file photo upload
- CNIC, ID card, or passport number; visitor contact/company; host employee; purpose; notes
- One-click checkout and complete visit history
- Search and filters by visitor, identity, host, date, and status
- Responsive desktop/tablet/mobile interface
- Server-side validation, protected routes, CSRF protection, and image restrictions
- Feature tests for login protection, validation, check-in, and checkout

## Local setup (Laragon / Windows)

Requirements: PHP 8.2+, Composer 2, MySQL 8+, and the PHP extensions normally required by Laravel.

```bash
composer install
copy .env.example .env
php artisan key:generate
```

Create a MySQL database named `office_visitor_manager`, confirm the `DB_*` values in `.env`, then run:

```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Open `http://127.0.0.1:8000`.

Default login:

- Email: `admin@example.com`
- Password: `password`

Change these credentials before production use. You can update the seeded user in the database or add a user-management module.

## Production checklist

Set `APP_ENV=production`, `APP_DEBUG=false`, and your HTTPS `APP_URL`; use a strong database password; point the web root to `/public`; make `storage` and `bootstrap/cache` writable; run `php artisan migrate --force`, `php artisan storage:link`, and `php artisan optimize`. Configure HTTPS and regular database/upload backups.

## Tests

```bash
php artisan test
```

### Frontend and check-in behavior

The interface uses directly served `public/css/app.css` and `public/js/app.js`; no npm build is required. Existing Blade templates provide the page content, with JavaScript swapping pages for same-origin navigation. Forms retain Laravel validation and normal submissions.

Visitor check-in records today's date in the configured office timezone (Asia/Karachi) and accepts a time only. Editing an existing visit preserves its original date. Visitor company is no longer collected; existing stored data is retained.

Run regression checks with `php artisan test`. If the CLI does not enable SQLite, use `php -d extension=pdo_sqlite vendor/phpunit/phpunit/phpunit`.

### Roles, photos, and visitor reports

- The existing first user becomes Admin when the role migration runs; new accounts default to Desk. Admins can add users and change roles/passwords in **Users & roles**. The last administrator cannot be demoted.
- Desk staff can view visitors/employees, create visits and employees, add companies through the employee form, and check visitors out. Editing/deleting records, managing users, and reporting require Admin access on the server.
- **Reports** offers daily/monthly previews and CSV downloads with visitor identity/contact details, host/company, purpose, check-in/out, duration, status, notes, and recorder. Periods use check-in dates in Asia/Karachi. Overnight visits retain their full duration; open visits show elapsed time at report generation. Durations use complete minutes.
- Visitor thumbnails open a full-size dialog. The authenticated photo route serves existing uploads without depending on a public storage symlink.


Repository: [Faraz-sandhu/Visitor-Desk](https://github.com/Faraz-sandhu/Visitor-Desk)
