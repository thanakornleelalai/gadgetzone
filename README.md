# ⚡ GadgetZone — PHP / MySQL E-Commerce

A complete, self-contained electronics storefront with a full admin panel,
session-based cart, multi-currency pricing, and optional Stripe card payments.
Built with plain PHP 8 + MySQL/MariaDB (mysqli) — no framework, no Composer.

---

## ✨ Features

**Storefront**
- Home page: hero, feature strip, category grid, featured products, deal-of-the-day with live countdown, new arrivals, testimonials, newsletter
- Shop with category / search / price / badge filters, sorting, and pagination (9 per page)
- Product detail pages with related items and quantity selector
- AJAX cart (add / update / remove) with a server-side `<form>` fallback, free-shipping progress, and a live cart badge
- Checkout: Cash on Delivery, bKash, Nagad, and Stripe (card)
- Customer accounts: dashboard, order history, profile + avatar upload, change password

**Admin panel** (`/admin`)
- KPI dashboard with revenue, orders, low-stock and pending alerts
- Product CRUD with image upload or URL, in a modal editor
- Order management with status updates and an order detail viewer
- User & role management (member / admin / super_admin, with protected accounts)
- Settings: active currency (12 currencies) and Stripe API keys

**Platform**
- Prices stored in BDT and converted on the fly to the chosen display currency
- One config constant (`BASE_URL`) controls every link — works in a sub-folder or at the web root
- Prepared statements throughout; passwords hashed with `password_hash()`

---

## 🚀 Quick start (XAMPP / local)

1. **Copy the folder** into your web root, keeping the folder name `gadget`:
   ```
   C:\xampp\htdocs\gadget        (Windows)
   /Applications/XAMPP/htdocs/gadget   (macOS)
   ```
2. **Create the database.** In phpMyAdmin (or the CLI) import:
   ```
   database_setup.sql
   ```
   This creates the `gadgetzone` database, all tables, and seed data
   (6 categories, 14 products, and the default admin account).
3. **Check the DB credentials** in `includes/db.php`. Defaults are the XAMPP
   defaults (`root` / empty password / host `127.0.0.1`):
   ```php
   define('DB_HOST', '127.0.0.1');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'gadgetzone');
   ```
4. **Open** `http://localhost/gadget/` in your browser.

### Default admin login
```
URL:      http://localhost/gadget/admin/
Email:    admin@gadgetzone.com
Password: Admin@1234
```
Change this password after first login (My Account → Password).

---

## 💳 Enabling Stripe (optional)

Card payments stay hidden until valid keys are saved.

1. Sign in to your Stripe Dashboard → **Developers → API keys**.
2. In GadgetZone go to **Admin → Settings → Stripe Payment**.
3. Paste your **Publishable key** (`pk_…`) and **Secret key** (`sk_…`) and save.

The card option then appears at checkout. Stripe redirects back to
`pages/stripe_return.php`, which verifies payment before completing the order.
COD / bKash / Nagad always work regardless of Stripe configuration.

> Note: outbound HTTPS to `api.stripe.com` must be allowed on your server.

---

## 💱 Currency

All product prices live in the database in **BDT**. The admin picks a single
active display currency in **Settings**; conversion and formatting happen at
render time, and the selection is cached per session. Supported: BDT, USD, EUR,
GBP, CAD, AUD, INR, SGD, SAR, AED, JPY, MYR. Adjust rates in
`includes/currency.php`.

---

## 🌐 Deploying at the web root (no `/gadget` sub-folder)

The whole app is path-agnostic thanks to one constant. To serve from the
document root (e.g. `https://yourdomain.com/`):

1. Set the base path to empty in `includes/db.php`:
   ```php
   define('BASE_URL', '');
   ```
2. (Optional) the JavaScript reads `BASE_URL` from a `data-base` attribute, so
   no JS edits are needed. If you prefer a hard rewrite of any stray
   `/gadget/` references, from the project root run:
   ```bash
   grep -rl '/gadget/' . --include='*.php' | xargs sed -i 's|/gadget/|/|g'
   ```
3. Upload the contents of the folder to your web root and import
   `database_setup.sql`.

Already have an older database without the Stripe / payment columns? Import
`migration_stripe_currency.sql` instead of re-importing everything.

---

## 📁 Structure

```
gadget/
├── index.php                 Home page
├── database_setup.sql        Full schema + seed (fresh install)
├── migration_stripe_currency.sql   Upgrade an existing DB
├── includes/                 db, functions, currency, header, footer
├── pages/                    shop, product, cart, checkout, auth, account, stripe
├── admin/                    dashboard, products, orders, users, settings (+ css/js)
└── assets/                   css/style.css, js/main.js, uploads/
```

## 🔧 Requirements
- PHP 8.0+ with `mysqli`, `curl`, `gd`, `mbstring`
- MySQL 5.7+ / MariaDB 10.3+
- Writable `assets/uploads/avatars/` and `admin/uploads/` for image uploads

---

© 2026 GadgetZone. Demo project.
