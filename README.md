# Laravel + Neon (PostgreSQL) Setup Guide

> Quick reference for running this Laravel project with Neon (cloud PostgreSQL) on Windows.

---

## Requirements

- PHP >= 8.x
- Laragon 8.6.x
- Composer
- Neon Account
- Node.js

---

## 1. Neon Setup

1. Go to [neon.tech](https://neon.tech) and log in
2. Create a new **Project**
3. Create a new **Database**
4. Copy your connection string from the Neon dashboard

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

Paste your Neon credentials (found in your Neon dashboard under **Connection Details**):

```env
DB_CONNECTION=pgsql
DB_HOST=your-neon-host.neon.tech
DB_PORT=5432
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_neon_password
DB_SSLMODE=require
```

> **Note:** Neon requires SSL. Make sure `DB_SSLMODE=require` is set.

---

## 4. Enable PostgreSQL PHP Driver

In `php.ini`, uncomment:

```ini
extension=pdo_pgsql
extension=pgsql
```

Then restart Apache or your terminal.

---

## 5. Run Migrations & Start Server

```bash
php artisan migrate
php artisan serve
```

Visit: `http://127.0.0.1:8000`

---

## Viewing the Database

**Neon Dashboard:** Go to your project → **Tables** to browse data directly in the browser.

**Tinker:**
```bash
php artisan tinker
>>> DB::table('users')->get();
```

---

## Common Errors

| Error | Fix |
|---|---|
| `could not find driver` | Enable `pdo_pgsql` in `php.ini` |
| `connection refused` | Check your Neon host and credentials in `.env` |
| `SSL required` | Add `DB_SSLMODE=require` to `.env` |
| `migration fails` | Confirm database exists and `.env` credentials are correct |

---

## Pre-flight Checklist

Before running the project, confirm:

- [ ] Neon project and database are created
- [ ] `.env` is configured with Neon credentials
- [ ] `DB_SSLMODE=require` is set
- [ ] PHP PostgreSQL driver is enabled
- [ ] `php artisan serve` is running