# Apparel Inventory Management System

This repository contains a professional Apparel Inventory Management System built from the AIMS project proposal. The implementation keeps the proposal's required scope at the center of the system and adds a small set of practical operational features where they materially improve usability.

## Stack

- Laravel 12
- Filament 5
- SQLite

## Proposal Coverage

The current build implements the main proposal requirements:

- Variant-level inventory tracking by SKU, size, color, and season
- Supplier management with lead times and purchasing history
- Reorder visibility through low-stock alerts and dashboard widgets
- Sales transaction capture for monthly and category-level analytics
- Role-based access control for administrator, manager, and read-only users
- Analytics dashboard for inventory health, monthly sales, category stock, size and color demand, and reorder alerts

It also adds a few restrained, suitable extras:

- Stock movement audit trail
- Manual stock adjustment workflow
- Purchase receiving workflow that updates inventory automatically
- Sales completion workflow that blocks negative stock

## Modules

### 1. Dashboard and Analytics

- Inventory overview KPI cards
- Monthly sales trend chart
- Inventory by category chart
- Size and color demand heatmap
- Reorder alert table

### 2. Catalog Management

- Categories
- Brands
- Suppliers
- Products
- Product variants

### 3. Inventory Operations

- Inventory records per variant
- Reorder thresholds and reorder quantities
- Manual stock adjustments
- Stock movement history

### 4. Purchasing

- Purchase orders
- Purchase order items
- Receive-order action that posts inbound stock

### 5. Sales

- Sales orders
- Sales order items
- Complete-sale action that posts outbound stock

### 6. Administration

- User management
- Admin, manager, and read-only access levels

## Data Model

Core entities:

- Categories
- Brands
- Suppliers
- Products
- ProductVariants
- Inventories
- PurchaseOrders
- PurchaseOrderItems
- SalesOrders
- SalesOrderItems
- StockMovements
- Users

This keeps the proposal's normalized relational approach while remaining practical for a Filament-based operational system.

## Implementation Phases

### Phase 1: Foundation

- Laravel 12 + Filament 5 project alignment
- SQLite configuration
- Normalized schema and Eloquent relationships

### Phase 2: Product Master and Variant Tracking

- Category, brand, supplier, product, and variant modules
- Variant-level stock model with reorder settings

### Phase 3: Transactional Workflows

- Purchase order receiving
- Sales order completion
- Automatic stock movement logging

### Phase 4: Analytics and Alerts

- Dashboard widgets
- Low-stock detection
- Category and monthly sales analytics

### Phase 5: Quality and Delivery

- Seeded demo dataset
- Automated tests for inventory rules
- Project documentation

## Setup

1. Ensure PHP has these extensions enabled:
	- `pdo_sqlite`
	- `sqlite3`
	- `intl`
2. Install PHP dependencies:

```bash
composer install
```

3. Prepare the environment file if needed:

```bash
copy .env.example .env
php artisan key:generate
```

4. Build the database and seed demo data:

```bash
php artisan migrate:fresh --seed
```

5. Start the application:

```bash
php artisan serve
```

If you are running the built-in server on Windows with PHP 8.5, this project includes a local workaround for a `mb_convert_encoding()` / `intl` runtime bug that can break `.env` loading during HTTP requests.

6. Open the Filament admin panel at:

```text
/admin
```

## Seeded Demo Accounts

All seeded accounts use the password below:

```text
password
```

Accounts:

- `admin@test.com` - full administrative access
- `manager@test.com` - operational CRUD access
- `viewer@test.com` - read-only access

## Demo Dataset

The seeder generates a realistic apparel dataset including:

- 100 products
- 1,000+ product variants
- Inventory records with healthy and low-stock mixes
- Purchase orders and purchase order items
- Sales orders and sales order items
- Stock movement history

This is designed to make the dashboard and operational workflows meaningful immediately after seeding.

## Tests

Run the automated test suite with:

```bash
php artisan test
```

Current tests cover:

- Purchase receipt inventory updates
- Sales completion inventory updates
- Negative-stock protection
- Low-stock scope and stock health logic

## Out of Scope

The implementation intentionally does not include:

- Mobile application support
- Third-party ERP or shipping integrations
- Demand forecasting or machine learning
- Public storefront features

These remain consistent with the original proposal's scope boundaries.
# apparel_inventory
