# 📘 Laravel + PostgreSQL Setup Guide (Quick Reminder)

This guide is for setting up and running your Laravel project with PostgreSQL on Windows.

---

# 🧰 1. Requirements

Make sure you already have installed:

* PHP (>= 8.x)
* Composer
* PostgreSQL
* pgAdmin (optional but recommended)
* Node.js (for frontend assets)

---

# 🗄️ 2. PostgreSQL Setup

## ✔ Check if PostgreSQL is running

* Open **Services (Windows)**
* Find: `postgresql-x64`
* Status should be: **Running**

---

## ✔ Create database

Using pgAdmin:

1. Open pgAdmin
2. Login
3. Right-click **Databases → Create → Database**
4. Name it (example):

   ```
   ```

iimms

````

---

# ⚙️ 3. Laravel Setup

## Install dependencies
```bash
composer install
````

---

## Create environment file

If not existing:

```bash
cp .env.example .env
```

---

## Generate app key

```bash
php artisan key:generate
```

---

# 🔌 4. Configure Database (.env)

Set this for PostgreSQL:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=iimms
DB_USERNAME=postgres
DB_PASSWORD=1234 <<password>>
```

---

# 🧪 5. Enable PHP PostgreSQL Driver

Open `php.ini` and enable:

```ini
extension=pdo_pgsql
extension=pgsql
```

Restart Apache / terminal after changes.

---

# 🚀 6. Run Migrations

```bash
php artisan migrate
```

---

# 🌐 7. Start Development Server

```bash
php artisan serve
```

Then open:

```
http://127.0.0.1:8000
```

---

# 🧾 8. View Database

## Option 1: pgAdmin

* Servers → PostgreSQL → Databases → iimms
* Schemas → public → Tables → View Data

## Option 2: Laravel Tinker

```bash
php artisan tinker
```

```php
DB::table('users')->get();
```

---

# ⚠️ Common Errors

## ❌ could not find driver

✔ Fix:

* Enable `pdo_pgsql` in php.ini

---

## ❌ connection refused

✔ Fix:

* Start PostgreSQL service
* Check port 5432

---

## ❌ migration fails

✔ Fix:

* Ensure database exists
* Check `.env` credentials

---

# 🧠 Quick Mental Checklist

Every time you run project:

✔ PostgreSQL running
✔ `.env` correct
✔ PHP driver enabled
✔ `php artisan serve` running

---

# 🎯 Done

If everything works, Laravel + PostgreSQL is fully set up 🚀
