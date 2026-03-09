# Inventory & Order Processing API

A simple inventory and order processing REST API built with **Laravel 12** and **PHP 8.2**.

---

## Requirements

Before you start, make sure you have these installed on your computer:

- **PHP 8.2+** — runs the Laravel backend
- **Composer** — installs PHP packages ([getcomposer.org](https://getcomposer.org/download/))
- **Node.js & npm** — builds the frontend assets ([nodejs.org](https://nodejs.org), download the LTS version)
- **SQLite** — used as the database (comes built into PHP, no setup needed)

---

## Setup & Run Locally

Follow these steps **in order** every time you clone this project fresh.

### Step 1 — Install PHP dependencies

```bash
composer install
```
> This downloads all the PHP packages the project needs into a `vendor/` folder.

---

### Step 2 — Create your environment file

```bash
cp .env.example .env
php artisan key:generate
```
> `.env` holds your app's configuration. `key:generate` fills in the `APP_KEY` which Laravel needs for encryption and security.

---

### Step 3 — Create the SQLite database file

**Windows:**
```bash
type nul > database\database.sqlite
```

**Mac & Linux:**
```bash
touch database/database.sqlite
```
> This creates an empty database file. Laravel will use this file to store all your data.

---

### Step 4 — Run database migrations

```bash
php artisan migrate
```
> This creates all the required tables inside your database file.

---

### Step 5 — Install frontend dependencies & build assets

```bash
npm install
npm run build
```
> `npm install` downloads the frontend packages. `npm run build` compiles and bundles the CSS and JavaScript files the app needs to display properly in the browser.

---

### Step 6 — Start the server

```bash
php artisan serve
```
> This starts a local development server.

Your API is now live at: **`http://localhost:8000/api`**

---

## Full Setup Commands (Copy & Paste)

### Windows
```bash
composer install
copy .env.example .env
php artisan key:generate
type nul > database\database.sqlite
php artisan migrate
npm install
npm run build
php artisan serve
```

### Mac & Linux
```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install
npm run build
php artisan serve
```

---

## Environment Variable

Make sure your `.env` file contains this line (it's already in `.env.example`):

```env
LOW_STOCK_THRESHOLD=5
```

You can change `5` to any value to adjust what counts as "low stock".

---

## Run Tests

```bash
php artisan test
```

---

## API Endpoints

> All requests must include these headers:
> ```
> Content-Type: application/json
> Accept: application/json
> ```

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

1. **Place an Order** — Validates stock for all items. All-or-nothing: if one item fails, nothing is reserved. Stock never goes negative even under concurrent requests.
2. **Cancel an Order** — Only `pending` or `confirmed` orders can be cancelled. Stock is fully restored on cancellation.
3. **List Orders** — Paginated, filterable by status, includes nested items and product details. Fast at any scale.
4. **Low Stock Report** — Returns products below a configurable threshold. Change the threshold via `LOW_STOCK_THRESHOLD` in `.env`.
