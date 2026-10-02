# Barangay Certificate Online Reservation and Tracking System

A beginner-friendly web application that lets residents reserve an appointment
for a Barangay Certificate online, and lets barangay staff/admin manage
reservations, residents, appointment schedules, and certificate issuance.

## Technology Used

- HTML5
- CSS3 (hand-written, no frameworks)
- Vanilla JavaScript (no jQuery)
- PHP (no frameworks — plain PHP with PDO)
- MySQL

No React, Node.js, Laravel, Bootstrap, or external APIs are used.

## Features

**Residents**
- Register an account, log in, log out
- View/update profile
- Reserve a Barangay Certificate appointment (date, time slot, purpose)
- View current reservation and full reservation history
- View certificate history and last certificate date
- Cancel a pending/confirmed reservation

**Barangay Staff**
- Dashboard with today's/upcoming reservation stats
- Search residents and view their record + certificate history
- Confirm, reject, or mark reservations as no-show
- Issue certificates (creates the certificate record + marks reservation Completed)
- Manage appointment slots

**Administrators**
- System-wide dashboard (residents, reservations, certificates)
- Manage residents (enable/disable accounts)
- Manage staff (create accounts, enable/disable)
- Manage appointment schedules
- View all reservations and audit logs

## Requirements

- [XAMPP](https://www.apachefriends.org/) (Apache + PHP + MySQL) — PHP 8.0+ recommended
- A modern web browser

## Folder Structure

```
barangay-system/
├── index.php
├── login.php
├── register.php
├── logout.php
├── config/
│   └── database.php
├── includes/
│   ├── header.php
│   ├── footer.php
│   ├── auth.php
│   └── functions.php
├── css/
│   └── style.css
├── js/
│   └── script.js
├── resident/
│   ├── dashboard.php
│   ├── reservation.php
│   ├── reserve.php
│   ├── reservation-success.php
│   ├── reservations.php
│   ├── certificates.php
│   └── profile.php
├── staff/
│   ├── dashboard.php
│   ├── reservations.php
│   ├── residents.php
│   ├── resident-view.php
│   └── issue-certificate.php
├── admin/
│   ├── dashboard.php
│   ├── residents.php
│   ├── staff.php
│   ├── appointments.php
│   ├── reservations.php
│   └── audit-logs.php
└── database/
    └── barangay_reservation.sql
```

## XAMPP Setup Instructions

1. **Install XAMPP** from https://www.apachefriends.org/ (choose the version with PHP 8.0+).
2. **Start Apache and MySQL** from the XAMPP Control Panel.
3. **Create the database**:
   - Open `http://localhost/phpmyadmin` in your browser.
   - Click **New**, or simply import the SQL file below (it creates the
     database automatically).
4. **Import the database**:
   - In phpMyAdmin, click **Import** → choose `database/barangay_reservation.sql` → **Go**.
   - This creates the `barangay_reservation` database, all tables, and sample data.
5. **Configure the database connection**:
   - Open `config/database.php`.
   - By default it uses `DB_HOST = localhost`, `DB_USER = root`, `DB_PASS = ''`
     (the standard XAMPP defaults). Change these if your MySQL setup differs.
6. **Place the project in htdocs**:
   - Copy the entire `barangay-system` folder into `C:\xampp\htdocs\`
     (on macOS/Linux: `/Applications/XAMPP/htdocs/` or `/opt/lampp/htdocs/`).
7. **Open the application**:
   - Visit `http://localhost/barangay-system/` in your browser.

## Default Accounts (Sample Data)

All sample accounts use the password: **Password123!**

| Role      | Email / Username                  | Password       |
|-----------|------------------------------------|----------------|
| Admin     | admin@barangay.gov.ph / admin      | Password123!   |
| Staff     | staff1@barangay.gov.ph / staff1    | Password123!   |
| Staff     | staff2@barangay.gov.ph / staff2    | Password123!   |
| Resident  | juan.delacruz@example.com / jdelacruz | Password123! |
| Resident  | maria.santos@example.com / msantos    | Password123! |

You can register additional resident accounts from the **Register** page.

## How the System Works

- All pages start by including `includes/auth.php`, which starts the PHP
  session and provides `requireLogin()` / `requireRole()` helpers so that,
  for example, a resident cannot open a staff or admin page just by typing
  its URL.
- All database access goes through `config/database.php`, which creates a
  single PDO connection (`$pdo`) using **prepared statements** everywhere —
  no raw string concatenation of user input into SQL.
- Reservation availability is always re-checked in PHP (`resident/reserve.php`)
  against the live MySQL data, even though the browser already hides full
  slots with JavaScript. This is intentional: never trust the client.
- A certificate record is only created at the moment staff clicks
  **Issue Certificate** (`staff/issue-certificate.php`) — not when the
  reservation is made.

## Basic Troubleshooting

- **"A system error occurred" on every page** — check `config/database.php`
  credentials, and make sure MySQL is running in the XAMPP Control Panel.
- **Blank white page** — turn on PHP error display temporarily by adding
  `ini_set('display_errors', 1); error_reporting(E_ALL);` at the very top of
  `config/database.php` while developing (remove it again before going live).
- **"Table doesn't exist" errors** — make sure you imported
  `database/barangay_reservation.sql` completely, and that `DB_NAME` in
  `config/database.php` matches (`barangay_reservation`).
- **CSS/JS not loading** — make sure the project folder is named exactly
  `barangay-system` inside `htdocs`, since the pages link to
  `/barangay-system/css/style.css` etc.
- **Can't log in with sample accounts** — double-check you're using
  `Password123!` exactly, and that the SQL import completed without errors.

## Security Notes

This project implements: `password_hash()` / `password_verify()`, PHP
sessions with `session_regenerate_id()` on login, prepared statements for
all queries, output escaping via `htmlspecialchars()`, role-based access
control on every private page, and CSRF tokens on state-changing forms.
It is built for learning purposes — for a real production deployment you
should also add HTTPS, rate-limiting on login, and stronger password
policies.
