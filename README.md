# Inventory & Order Processing API

A simple inventory and order processing REST API built with **Laravel 12** and **PHP 8.2**.

---

## Requirements

- PHP 8.2+
- Composer
- SQLite (default)

---

## Setup & Run Locally

```bash
# 1. Install dependencies
composer install

# 2. Create environment file
copy .env.example .env
php artisan key:generate

# 3. Create the database file (Windows)
type nul > database\database.sqlite

# 4. Run migrations
php artisan migrate

# 5. Start the server
php artisan serve
```

API is live at `http://localhost:8000/api`

Add this to your `.env` file:
```env
LOW_STOCK_THRESHOLD=5
```

---

## Run Tests

```bash
php artisan test
```

---

## API Endpoints

> All requests need these headers:
> `Content-Type: application/json`
> `Accept: application/json`

### Create a Product
**POST** `/api/products`
```json
{
    "name": "Laptop Stand",
    "sku": "LPT-STD-001",
    "price": 49.99,
    "stock_quantity": 25,
    "is_active": true
}
```

### List All Products
**GET** `/api/products`

### Low Stock Report
**GET** `/api/products/low-stock`
**GET** `/api/products/low-stock?threshold=10`

### Place an Order
**POST** `/api/orders`
```json
{
    "user_id": 1,
    "items": [
        { "product_id": 1, "quantity": 2 },
        { "product_id": 2, "quantity": 1 }
    ]
}
```

### Cancel an Order
**POST** `/api/orders/{id}/cancel`

### List Orders for a User
**GET** `/api/users/{userId}/orders`
**GET** `/api/users/1/orders?status=pending`
**GET** `/api/users/1/orders?per_page=5&page=2`

---

## Features

1. **Place an Order** — Validates stock for all items. All-or-nothing if one item fails, nothing is reserved. Stock never goes negative even under concurrent requests.
2. **Cancel an Order** — Only `pending` or `confirmed` orders can be cancelled. Stock is fully restored on cancellation.
3. **List Orders** — Paginated, filterable by status, includes nested items and product details. Fast at any scale.
4. **Low Stock Report** — Returns products below a configurable threshold. Change the threshold via `LOW_STOCK_THRESHOLD` in `.env`
