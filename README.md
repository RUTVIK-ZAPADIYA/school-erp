# School ERP Project

This project is now structured around a single shared backend bootstrap so all modules (admin, teacher, student) use the same database and schema.

## Quick Start

1. Place project in your PHP server root (XAMPP/WAMP/Laragon).
2. Ensure MySQL is running.
3. Open `setup.php` once in browser.
4. Open `login.php` and sign in.

## Default Accounts

- Admin: `admin` / `admin123`
- Teacher: `teacher1` / `teacher123`
- Student: `student1` / `student123`

Note: If passwords are plain from old data, login upgrades them automatically to hashed format.

## Current Structure

- `includes/db_connect.php`:
  - Single source of DB connection.
  - Auto-creates `school_erp` database.
  - Ensures all required tables/columns exist.
  - Seeds default users/classes/subjects.
- `dbconfig.php`:
  - Backward-compatible wrapper for old admin pages.
  - Reuses the same connection from `includes/db_connect.php`.
- `database-setup.sql`:
  - Unified SQL schema and seed data for manual import/reset.
- `setup.php` and `create_tables.php`:
  - Lightweight setup endpoints using shared bootstrap.

## Authentication Notes

- Login now sets normalized session keys:
  - Common: `user_id`, `username`, `role`, `name`
  - Admin compatibility: `admin_id`, `admin_name`
  - Student compatibility: `student_id`, `student_name`
- Teacher and student pages now redirect to login instead of force-setting test sessions.

## Recommended Next Cleanup

- Migrate remaining raw SQL in admin CRUD pages to prepared statements for stronger SQL injection resistance.
- Add CSRF tokens to create/update/delete forms across admin/teacher/student modules.
- Replace placeholder dashboard/report sample rows with live DB-driven queries.
