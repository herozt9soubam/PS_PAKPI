# Offline Android Build Notes

This project has been converted from PostgreSQL to a local SQLite database.

## What Changed

- `config.php` now opens `data/pspakpi.sqlite` with PDO SQLite.
- `db.php` reuses the same offline connection.
- The SQLite database and required tables are created automatically on first run.
- Starter data is seeded for admin login, services, countries, industries, and field visibility.
- PostgreSQL-only SQL such as `NOW()`, `ILIKE`, `RETURNING id`, and `crypt(..., gen_salt(...))` was replaced.

## Local Offline Login

Default admin login after first run:

- Username: `admin`
- Password: `admin123`

Change this password before sharing a build.

## Required PHP Extension

The offline build requires the PHP `pdo_sqlite` extension.

For this XAMPP install, the SQLite DLL exists but is not enabled in `D:\xampp\php\php.ini`.
Enable it by uncommenting or adding:

```ini
extension=pdo_sqlite
```

For Android packaging, use a PHP runtime/build that includes PDO SQLite.

## Android Packaging Direction

Package these pieces inside the Android app:

- PHP runtime with `pdo_sqlite`
- A small local HTTP server
- This project folder
- The writable `data/` directory for `pspakpi.sqlite`
- A WebView pointed at the local PHP server

The app is offline-first and does not need PostgreSQL.
