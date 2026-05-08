# Laravel + Supabase (PostgreSQL) Setup Guide

> Quick reference for running this Laravel project with Supabase (cloud PostgreSQL) on Windows.

---

## Requirements

- PHP >= 8.x
- Laragon 8.6.x
- Composer
- Supabase Account
- Node.js

---

## 1. Supabase Setup

1. Go to [supabase.com](https://supabase.com) and log in
2. Click **New Project** and fill in the project name and database password
3. Wait for the project to finish provisioning (~1 minute)
4. Go to **Project Settings → Database** to find your connection details

---

## 2. Laravel Setup

```bash
# Install dependencies
composer install

# Create environment file
cp .env.example .env

# Generate app key
php artisan key:generate
```

---

## 3. Configure `.env`

In your Supabase dashboard go to **Project Settings → Database → Connection parameters** and copy the values into your `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=db.<your-project-ref>.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=your_supabase_password
DB_SSLMODE=require
```

> **Note:** Supabase requires SSL. Make sure `DB_SSLMODE=require` is set.  
> Your project ref is the string in your Supabase project URL: `https://supabase.com/dashboard/project/<ref>`

---

## 4. Enable PostgreSQL PHP Driver

In `php.ini`, uncomment the following lines:

```ini
extension=pdo_pgsql
extension=pgsql
```

Then restart Apache or your terminal.

---

## 5. Run Migrations & Start Server

```bash
# Run database migrations
php artisan migrate

# Link storage for file uploads (mugshots, etc.)
php artisan storage:link

# Start the dev server
php artisan serve
```

Visit: `http://127.0.0.1:8000`

---

## Viewing the Database

**Supabase Table Editor:** Go to your project → **Table Editor** to browse and edit data directly in the browser.

**Supabase SQL Editor:** Go to **SQL Editor** to run raw queries against your database.

**Tinker:**
```bash
php artisan tinker
>>> DB::table('users')->get();
```

---

## Storage (File Uploads)

This project stores uploaded files (e.g. mugshots) using Laravel's public disk. After running `php artisan storage:link`, uploaded files are accessible at:

```
http://127.0.0.1:8000/storage/<path>
```

If mugshots or other uploads aren't displaying, make sure you've run `storage:link`. You only need to do this once.

---

## Common Errors

| Error | Fix |
|---|---|
| `could not find driver` | Enable `pdo_pgsql` in `php.ini` and restart |
| `connection refused` | Check your Supabase host and credentials in `.env` |
| `SSL required` | Add `DB_SSLMODE=require` to `.env` |
| `migration fails` | Confirm the project is fully provisioned and `.env` credentials are correct |
| `No such file or directory (storage)` | Run `php artisan storage:link` |
| `password authentication failed` | Re-copy your password from Supabase — it's shown only once on project creation; reset it under **Project Settings → Database** if needed |

---

## Pre-flight Checklist

Before running the project, confirm:

- [ ] Supabase project is created and fully provisioned
- [ ] `.env` is configured with Supabase connection parameters
- [ ] `DB_SSLMODE=require` is set
- [ ] PHP PostgreSQL driver is enabled in `php.ini`
- [ ] `php artisan storage:link` has been run
- [ ] `php artisan serve` is running