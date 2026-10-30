# Live Queue System

A real-time queueing system for a school registrar and cashier. Students take a ticket from their phone with no login, staff call the next number from a dashboard, and a public screen shows who's being served, all updating live without refreshing.

Built for PHINMA UPANG College of Urdaneta. My first Laravel project.

## How it works

- **Students** scan a QR code at the counter, pick a service (Registrar, Cashier), and get a ticket like `A-042`. Their phone shows their place in line and updates live. No account needed: tickets are tied to the browser session, and each student can hold only one active ticket per service.
- **Staff** log in, choose their counter (e.g. Window 2), and press **Call Next**. They can mark a ticket done or skipped.
- **The Now Serving display** runs on a TV in the waiting area and updates the moment staff call a number.
- **Sound and vibration alerts** let people know when their number is called.
- **Admins** manage services and counters.

## Engineering highlights

- **No duplicate ticket numbers under load.** When two students tap "Get Ticket" at the same instant, both requests could read the same "last number". Issuing a ticket locks the service's counter row (`lockForUpdate()`) inside a database transaction, and a unique constraint on `(service, date, number)` backs it up. Calling the next ticket uses the same locking, so two staff can't call the same student.
- **Live updates over WebSockets** with Laravel Reverb and Echo: the student's phone, the staff dashboard and the public display all react to the same broadcast events.
- **Runs on a school PC, no paid hosting.** A PowerShell setup script installs it on a Windows PC with XAMPP and Herd, and phones on the school Wi-Fi connect over the local network. The student page tells them which Wi-Fi to join first.
- **Daily database backup** after office hours, scheduled with Laravel's scheduler.
- **83 automated tests** covering ticket issuing, calling, the full ticket flow, the display, admin screens and backups.

## Tech stack

Laravel 13 (PHP 8.3+) · React + Inertia.js · Tailwind CSS · Laravel Reverb + Echo · MySQL (SQLite for development) · PHPUnit · Vite

## Running it locally

```bash
composer run setup        # install dependencies, create .env, migrate, build assets
php artisan staff:create  # create a staff login (public registration is off)
composer run dev          # start the development processes
```

To test on phones over your local network, use `composer run lan`. It builds the assets and starts Reverb, the queue worker and the scheduler together.

For installing on a school PC, see `setup-school-pc.ps1`; it lists its prerequisites at the top.

---

By [Brandon Dylan Narito](https://brandonnarito.com)
