# Sneat Store ERP

A modular ERP and administration system built with **Laravel 12**, **Jetstream**, **Livewire 3**, and the **Sneat Admin Template** from ThemeSelection.

This project includes authentication, role and permission management, accounting, billing, payment options, PDF generation, and a scalable module-based architecture.

---

## 🚀 Tech Stack

* PHP 8.2+
* Laravel 12
* Laravel Jetstream
* Livewire 3
* Laravel Sanctum
* Sneat HTML Laravel Jetstream Template
* nwidart/laravel-modules
* Spatie Laravel Permission
* Barryvdh DOMPDF
* Vite
* MySQL / MariaDB / PostgreSQL / SQLite

---

## 📦 Installed Packages

| Package                                     | Purpose                      |
| ------------------------------------------- | ---------------------------- |
| laravel/framework                           | Core framework               |
| laravel/jetstream                           | Authentication scaffolding   |
| livewire/livewire                           | Reactive frontend components |
| laravel/sanctum                             | API authentication           |
| nwidart/laravel-modules                     | Modular architecture         |
| spatie/laravel-permission                   | Roles and permissions        |
| barryvdh/laravel-dompdf                     | PDF generation               |
| themeselection/sneat-html-laravel-jetstream | Sneat admin UI               |

---

## 🧩 Enabled Modules

The application is architected as a modular, reusable, multi-tenant enterprise e-commerce ERP:

* **Context**: Multi-Tenancy (`Tenant` → `Store` → `Branch`), domain resolution, dynamic FX multi-currency engine.
* **Catalog**: Product catalog, variants, attributes & values, categories, brands, specifications, and verified customer reviews.
* **Inventory**: Branch stock ledger, atomic stock adjustments, real-time reservations, and low-stock alerts.
* **Cart**: Shopping cart management, session/customer cart persistence, real-time stock limits, and coupon discounts.
* **Order**: Transactional checkout engine, shipping rate calculations, shipments & carrier tracking, coupons, sales analytics, and customer self-service portal.
* **Payment**: Multi-gateway driver architecture (Stripe, PayPal, Offline/Bank Wire, COD), webhook processing, settlement ledger, and refund auditing.
* **Marketplace**: Multi-vendor marketplace, vendor approval workflow, vendor portal, commission splits, and payout disbursements.
* **Permission**: Multi-tenant RBAC with granular domain permissions, role hierarchy, and guard enforcement.
* **Notifications**: Event-driven notification center, navbar real-time feed, email & database dispatching.
* **Accounting & Billing**: Automated financial journal entries, charts of accounts, invoices, and double-entry reconciliation.

---

## 📁 Project Structure

```text
app/
bootstrap/
config/
database/
Modules/
 ├── Context/       (Multi-Tenancy, Stores, Branches, FX Currencies)
 ├── Catalog/       (Products, Variants, Attributes, Brands, Categories, Reviews)
 ├── Inventory/     (Branch Stock, Reservations, Audit Transactions)
 ├── Cart/          (Cart Storage, Pricing, Live Limits)
 ├── Order/         (Checkout, Shipping, Shipments, Coupons, Customer Portal, Reports)
 ├── Payment/       (Stripe, PayPal, Offline, Webhooks, Settlements)
 ├── Marketplace/   (Vendors, Commissions, Vendor Portal, Payouts)
 ├── Permission/    (RBAC, Scoped Permissions & Roles)
 ├── Accounting/    (Double-entry Journals, Accounts)
 ├── Billing/       (Invoices, Items, Payment Records)
 ├── General/       (Document Templates, Menus)
 └── SampleModule/  (Boilerplate Template)
public/
resources/
routes/
storage/
tests/
vendor/
```

---

## ⚙️ Requirements

Before installation, ensure your environment includes:

* PHP 8.2 or higher
* Composer 2+
* Node.js 20+ and npm
* MySQL 8+ or MariaDB 10.5+
* Git

---

## 📥 Installation

### 1. Clone the Repository

```bash
git clone git@github.com:akhilmeleppura/sneat-store.git
cd sneat-store
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install Frontend Dependencies

```bash
npm install
```

### 4. Create Environment File

```bash
cp .env.example .env
```

### 5. Generate Application Key

```bash
php artisan key:generate
```

### 6. Configure Database

Update the `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sneat_store
DB_USERNAME=root
DB_PASSWORD=
```

### 7. Run Migrations and Seeders

```bash
php artisan migrate --seed
```

### 8. Build Frontend Assets

```bash
npm run build
```

### 9. Start Development Server

```bash
composer run dev
```

Or run manually:

```bash
php artisan serve
npm run dev
```

---

## 🔐 Default Admin Login

The `DatabaseSeeder` creates a Supreme Admin user:

| Field    | Value                                     |
| -------- | ----------------------------------------- |
| Email    | [admin@gmail.com](mailto:admin@gmail.com) |
| Password | Admin@123                                 |
| Role     | Supreme Admin                             |

> ⚠️ Change this password immediately in production.

---

## 🌱 Seeders Included

The database seeder performs the following:

1. Runs `PaymentOptionSeeder`
2. Creates 10 test users via factory
3. Creates the default Supreme Admin account

Run again anytime with:

```bash
php artisan db:seed
```

---

## 👥 Roles & Permissions

Role and permission management is powered by Spatie Laravel Permission.

Features include:

* Role creation
* Permission assignment
* Route middleware protection
* Module-based access control

---

## 📄 PDF Generation

PDF export functionality is provided by Barryvdh DOMPDF.

Example:

```php
use Barryvdh\DomPDF\Facade\Pdf;

$pdf = Pdf::loadView('reports.invoice', $data);
return $pdf->download('invoice.pdf');
```

---

## 🧱 Modular Architecture

This project uses `nwidart/laravel-modules` together with `spatie/laravel-permission`.

### 🔐 Automatic Permission Generation for New Modules

Whenever a new module is created, the system is designed to generate corresponding permissions automatically.

For example, creating an `Inventory` module can automatically create permissions such as:

* inventory.view
* inventory.create
* inventory.edit
* inventory.delete
* inventory.export
* inventory.manage

These permissions can then be assigned to roles using the Permission module.

**Example workflow:**

1. Create a new module.
2. Run the module permission seeder or command.
3. Permissions are inserted into the `permissions` table.
4. Assign them to roles from the admin panel.

**Create module:**

```bash
php artisan module:make Inventory
```

**Generate permissions (custom command example):**

```bash
php artisan permissions:generate Inventory
```

**Seed permissions:**

```bash
php artisan db:seed --class=ModulePermissionSeeder
```

This approach ensures every module has its own permission set and integrates seamlessly with Spatie Laravel Permission.

### Create a New Module

```bash
php artisan module:make Inventory
```

### Enable a Module

```bash
php artisan module:enable Inventory
```

### Disable a Module

```bash
php artisan module:disable Inventory
```

### List Modules

```bash
php artisan module:list
```

---

## 🛠️ Useful Artisan Commands

### Clear Caches

```bash
php artisan optimize:clear
```

### Run Migrations

```bash
php artisan migrate
```

### Fresh Migration with Seed

```bash
php artisan migrate:fresh --seed
```

### Queue Worker

```bash
php artisan queue:listen --tries=1
```

### View Logs

```bash
php artisan pail
```

---

## 🎨 Sneat Admin Template

This project uses the Sneat Laravel Jetstream integration from ThemeSelection.

Features:

* Responsive admin dashboard
* Sidebar navigation
* Dark/light mode support
* Reusable UI components
* Form and table layouts

---

## 🧪 Testing

Run the test suite:

```bash
php artisan test
```

Or:

```bash
vendor/bin/phpunit
```

---

## 🧹 Code Quality

### Format Code

```bash
./vendor/bin/pint
```

### Static Analysis (if configured)

```bash
vendor/bin/phpstan analyse
```

---

## 📦 Production Deployment

### Optimize Application

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Build Assets

```bash
npm run build
```

### Run Migrations

```bash
php artisan migrate --force
```

---

## 🔄 Updating Dependencies

### PHP Packages

```bash
composer update
```

### Frontend Packages

```bash
npm update
```

---

## 🐞 Troubleshooting

### Permission Issues

```bash
chmod -R 775 storage bootstrap/cache
```

### Clear Everything

```bash
php artisan optimize:clear
composer dump-autoload
```

### Reinstall Dependencies

```bash
rm -rf vendor node_modules
composer install
npm install
```

---

## 📝 Environment Variables

Important variables:

```env
APP_NAME="Sneat Store ERP"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sneat_store
DB_USERNAME=root
DB_PASSWORD=
```

---

## 📚 Features

* User Authentication
* Team Management
* Roles & Permissions
* Supreme Admin Access
* Accounting Module
* Billing Module
* Payment Options
* PDF Reports
* Modular Architecture
* Responsive Admin Dashboard

---

## 🔐 Security Recommendations

Before going live:

* Change default admin password
* Disable debug mode
* Configure secure mail settings
* Use HTTPS
* Set strong database credentials
* Restrict file permissions

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to your branch
5. Open a Pull Request

---

## 📄 License

This project is licensed under the MIT License.

---

## 👨‍💻 Author

**Akhil Meleppura**

* GitHub: [https://github.com/akhilmeleppura](https://github.com/akhilmeleppura)

---

## ⭐ Support

If you find this project useful, please star the repository on GitHub.
