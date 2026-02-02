# Subdomain-Based Multi-Tenancy Setup Guide

## Overview

This WMS application uses **subdomain-based multi-tenancy** with a **single database** where:
- Each subdomain represents a separate company/organization
- Users and warehouses belong to a specific subdomain
- Users can only access their subdomain's data
- All other entities (products, orders, clients, etc.) belong to warehouses
- Access is via root path: `http://subdomain1.wms.test/` (not /admin)

## Architecture

### Database Structure
```
Single Database
  └─ subdomains table
      ├─ users (subdomain_id)
      ├─ warehouses (subdomain_id)
          └─ products, orders, clients, etc. (warehouse_id)
```

### Key Components

1. **Subdomain Model** - Represents each company/organization
2. **IdentifySubdomain Middleware** - Identifies current subdomain from URL
3. **TenantScope** - Filters Users and Warehouses by subdomain_id
4. **WarehouseScope** - Filters all warehouse-related models by warehouse_id

## Setup Instructions

### 1. Rebuild Database

```bash
php artisan migrate:fresh
```

### 2. Seed Demo Data

```bash
php artisan db:seed TenantSeeder
```

This creates:
- **Subdomain 1** (`subdomain1.wms.test`)
  - User: `admin@subdomain1.test` / `password`
  - Warehouses: Warehouse A, Warehouse B
  
- **Subdomain 2** (`subdomain2.wms.test`)
  - User: `admin@subdomain2.test` / `password`
  - Warehouses: Warehouse C, Warehouse D

### 3. Configure Local DNS

Add to your `/etc/hosts` (macOS/Linux) or `C:\Windows\System32\drivers\etc\hosts` (Windows):

```
127.0.0.1 wms.test
127.0.0.1 subdomain1.wms.test
127.0.0.1 subdomain2.wms.test
```

### 4. Access the Application

- **Subdomain 1**: `http://subdomain1.wms.test/`
- **Subdomain 2**: `http://subdomain2.wms.test/`
- Central domain redirects to subdomain access

## Usage

### Creating New Subdomains

```php
use App\Models\Subdomain;
use App\Models\User;
use App\Models\Warehouse;

// Create subdomain
$subdomain = Subdomain::create([
    'subdomain' => 'newcompany',
    'name' => 'New Company Inc.',
]);

// Create user
$user = User::create([
    'subdomain_id' => $subdomain->id,
    'name' => 'Admin User',
    'email' => 'admin@newcompany.test',
    'password' => Hash::make('password'),
    'email_verified_at' => now(),
]);

// Create warehouse
$warehouse = Warehouse::create([
    'subdomain_id' => $subdomain->id,
    'name' => 'Main Warehouse',
]);

// Attach user to warehouse
$user->warehouses()->attach($warehouse->id);
```

### How It Works

1. **Request comes in**: User visits `http://subdomain1.wms.test/`
2. **Middleware identifies subdomain**: `IdentifySubdomain` extracts "subdomain1"
3. **Subdomain loaded**: Finds subdomain in database, stores in app container
4. **Global scopes applied**: All queries automatically filtered by subdomain_id
5. **User sees only their data**: Only sees warehouses and data for subdomain1

### Adding Warehouse Scope to Models

For any model that belongs to a warehouse, use the `BelongsToWarehouse` trait:

```php
use App\Models\Concerns\BelongsToWarehouse;

class Product extends Model
{
    use BelongsToWarehouse;
    
    // Model automatically scoped to current warehouse
}
```

## Configuration

### Central Domain

Set in `config/app.php` or `.env`:

```php
'central_domain' => env('CENTRAL_DOMAIN', 'wms.test'),
```

### Middleware

Subdomain identification is added to Filament panel middleware:

```php
->middleware([
    // ... other middleware
    IdentifySubdomain::class,
])
```

## Important Notes

### Single Database Approach

- All data stored in one database
- Isolation via subdomain_id and warehouse_id columns
- No separate tenant databases (different from stancl/tenancy default)
- Simpler backup and maintenance

### Data Isolation

Two levels of isolation:
1. **Subdomain level**: Users and Warehouses filtered by subdomain_id
2. **Warehouse level**: All other entities filtered by warehouse_id

### Filament Integration

- Panel path is `/` (root) not `/admin`
- Warehouse selection for multi-warehouse users
- All resources automatically scoped

## Troubleshooting

### "Subdomain not found"
- Check subdomain exists in `subdomains` table
- Verify DNS/hosts configuration
- Ensure subdomain is correctly configured

### User Can't See Warehouses
- Check `user_warehouses` pivot table
- Verify `subdomain_id` matches on user and warehouses
- Check global scopes are applied

### Data Not Showing
- Verify model has `warehouse_id` column
- Apply `BelongsToWarehouse` trait or `WarehouseScope`
- Check Filament tenant (warehouse) is selected

## Models with Subdomain Scope

- User
- Warehouse

## Models with Warehouse Scope

- Product
- Order
- Client  
- ProductCategory
- Brand
- VatRate
- AttributeGroup
- Picklist
- (All entities that belong to a warehouse)

