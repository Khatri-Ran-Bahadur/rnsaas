# SathiSaaS - Enterprise Multi-Tenant B2B SaaS Platform

A multi-tenant B2B SaaS platform built with **Laravel 12**, **Inertia.js v3**, **Vue 3**, and **Tailwind CSS**. Designed for multi-tenant subscription management, tenant isolation, role & permission control, billing, customer management, and platform branding.

---

## 📋 System Requirements

Before installing, ensure your server meets the following requirements:

| Requirement | Minimum Version | Recommended |
|---|---|---|
| **PHP** | 8.2 | **8.3+** |
| **MySQL / MariaDB** | MySQL 8.0+ / MariaDB 10.4+ | MySQL 8.0+ |
| **Node.js & NPM** | Node 18.x | Node 20.x LTS |
| **Composer** | 2.0+ | Latest |
| **Web Server** | Nginx, Apache, Laravel Herd, or Valet | Nginx / Herd |

### Required PHP Extensions
- `BCMath`
- `Ctype`
- `cURL`
- `DOM`
- `Fileinfo`
- `JSON`
- `Mbstring`
- `OpenSSL`
- `PCRE`
- `PDO` & `pdo_mysql`
- `Tokenizer`
- `XML`
- `Zip`

---

## 🚀 Installation Guide

You can install the application using either the **Web Installation Wizard** (recommended for clients and buyers) or via the **Command Line (CLI)**.

---

### Option A: Web Installation Wizard (Recommended)

When you deploy the application or connect to a new/fresh database, the system automatically detects that it is not yet installed and launches the interactive setup wizard.

#### Step 1: Clone or Extract Codebase
```bash
git clone <your-repository-url> rnsaas
cd rnsaas
```

#### Step 2: Install Dependencies & Build Assets
```bash
composer install
npm install
npm run build
```

#### Step 3: Setup Environment File & App Key
```bash
cp .env.example .env
php artisan key:generate
```

#### Step 4: Set Directory Permissions
Ensure the web server has write access to the storage and cache folders:
```bash
chmod -R 775 storage bootstrap/cache
```

#### Step 5: Open the Web Installer
Point your web server (Herd, Valet, Nginx, Apache) to the `public/` directory and open the URL in your browser:
```
http://your-domain.test/
```
The application will automatically redirect you to `http://your-domain.test/install` and guide you through 6 quick steps:

1. **Requirements & Permissions**: Validates PHP extensions, memory limits, and writable directories.
2. **License Activation**: Enter your purchase code / license key (for local development or testing, use `RN-SAAS-PRO-2026-ACTIVE`).
3. **Database Configuration**: Enter MySQL Host, Port, Database name, Username, and Password. If the database does not exist, the installer will automatically create it for you.
4. **System Setup & Migrations**: Configures Application Name, URL, environment, and automatically runs all migrations and system seeders.
5. **Super Admin Setup**: Set up your primary platform Super Administrator name, email, and password.
6. **Installation Complete**: Securely generates the installation lock (`storage/installed`) and links you directly to the login page.

---

### Option B: Command Line (CLI / Automated Deployment)

If you prefer terminal-based deployment or CI/CD pipelines:

```bash
# 1. Install PHP dependencies
composer install --no-dev --optimize-autoloader

# 2. Setup environment file
cp .env.example .env
php artisan key:generate

# 3. Configure database credentials in .env:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=rnsaas
# DB_USERNAME=root
# DB_PASSWORD=secret

# 4. Run automated system setup (migrates, seeds roles/permissions, creates super admin)
php artisan app:setup --admin-name="Super Admin" --admin-email="admin@example.com" --admin-password="YourSecurePassword"

# 5. Build frontend assets
npm install
npm run build

# 6. Create storage symlink
php artisan storage:link
```

---

## 🔑 License Keys & Verification

The application includes built-in license verification supporting:
- **Envato Purchase Code (UUID)**: `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`
- **SaaS Product Keys**: `RN-SAAS-XXXX-XXXX-XXXX`
- **Development / Demo Key**: `RN-SAAS-PRO-2026-ACTIVE`

The active license is saved upon installation in `.env` (`RN_PURCHASE_CODE`) and `storage/installed`.

---

## 📦 Demo Data Importer

To populate a complete showcase environment with realistic business data across **all 10+ modules**:

```bash
# Import demo data across all active organizations
php artisan demo:import

# Or import demo data targeted to a specific organization/tenant
php artisan demo:import --tenant=main-company
# or by ID:
php artisan demo:import --tenant=1

# Wipe database, run fresh migrations, and import all demo data
php artisan demo:import --fresh
```

### Datasets Populated:
- **Platform & SuperAdmin**: CMS Pages (About Us, Terms, Privacy), Notification Templates, Platform Settings
- **Subscriptions**: Tiered Plans (Starter, Business, Enterprise), Feature Flags, Promotional Coupons
- **Tenancy & Administration**: Branches, Corporate Departments, Job Designations, Staff Members
- **Statutory Tax**: Tax Types (VAT/GST, SST, Withholding), Categories, Rates, Exemption Rules
- **Financial Accounting**: Full Chart of Accounts, Customers, Vendors, Invoices, Purchase Bills, Journal Entries
- **Multi-Warehouse Inventory**: Categories, Measurement Units, Warehouses/Locations, Tracked Products, Stock Adjustments
- **POS & Restaurant Counter**: Registers, Cashier Shifts, Dining Tables, Orders, Kitchen Display Tickets
- **MRP Manufacturing**: Production Work Centers, Bill of Materials (BOM), Work Orders, QA Inspections, Material Issues, Finished Goods
- **HRM System**: Working Shifts, Work Schedules, Country/Company Holidays, Attendance Check-ins, Leave Requests, Overtime Records, Employee Documents
- **Payroll & Compensation**: Payroll Groups, Monthly Salary Runs, Statutory Contribution Calculations

---

## ⚙️ Background Tasks & Cron Configuration

For emails, tenant notifications, and subscription renewals to process correctly in production:

### 1. Queue Worker
Run the queue worker via Supervisor:
```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

### 2. Task Scheduling (Cron Job)
Add this entry to your server crontab (`crontab -e`):
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🛠️ Common Troubleshooting

### 1. HTTP 500 Error on Fresh Database
- **Cause**: Visiting a fresh database with old browser session cookies causes Laravel to query a `users` table that doesn't exist yet.
- **Solution**: The application middleware automatically clears old login sessions and redirects to `/install`. If your database is unmigrated and `storage/installed` exists, delete `storage/installed` or run `php artisan app:setup`.

### 2. "Table doesn't exist" or Missing Migrations
Run:
```bash
php artisan migrate --force
php artisan db:seed --class=SystemSetupSeeder --force
```

### 3. Re-running the Web Installer
To re-run the web installer on an existing instance, simply delete the lock file:
```bash
rm storage/installed
```
Then refresh your browser.

### 4. Storage or Symlink Errors
```bash
php artisan storage:link
chmod -R 775 storage bootstrap/cache
```

---

## 📄 License & Support

For support, feature requests, or questions, please open an issue or contact support at `support@rnsaas.com`.