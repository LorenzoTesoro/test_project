# Test Suite and API Documentation

## API Endpoints Specification

### Base URL
All endpoints are prefixed with `/api/orders`

### 1. Create Order
- **Endpoint:** `POST /api/orders`
- **Method:** POST
- **Success Response Code:** 201 (Created)
- **Error Response Code:** 400 (Bad Request), 500 (Internal Server Error)
- **Request Body Example:**
```json
{
    "name": "Order Name",
    "description": "Order Description",
    "order_date": "2025-02-22"
}
```
- **Response Example:**
```json
{
    "id": 1,
    "name": "Order Name",
    "description": "Order Description",
    "order_date": "2025-02-22"
}
```

### 2. Update Order
- **Endpoint:** `PUT /api/orders/{id}`
- **Method:** PUT
- **Parameters:** `id` (integer) - Order ID
- **Success Response Code:** 200 (OK)
- **Error Response Codes:** 400 (Bad Request), 404 (Not Found), 500 (Internal Server Error)
- **Request Body Example:**
```json
{
    "name": "Updated Order Name",
    "description": "Updated Description",
    "order_date": "2025-03-01"
}
```
- **Response Example:**
```json
{
    "id": 1,
    "name": "Updated Order Name",
    "description": "Updated Description",
    "order_date": "2025-03-01"
}
```

### 3. Delete Order
- **Endpoint:** `DELETE /api/orders/{id}`
- **Method:** DELETE
- **Parameters:** `id` (integer) - Order ID
- **Success Response Code:** 200 (OK)
- **Error Response Codes:** 404 (Not Found), 500 (Internal Server Error)
- **Response Example:**
```json
{
    "message": "Order deleted successfully"
}
```

### 4. List Orders
- **Endpoint:** `GET /api/orders/orders`
- **Method:** GET
- **Query Parameters:**
  - `name` (optional): Filter by order name
  - `description` (optional): Filter by description
  - `start_date` (optional): Filter by start date (format: YYYY-MM-DD)
  - `end_date` (optional): Filter by end date (format: YYYY-MM-DD)
- **Success Response Code:** 200 (OK)
- **Response Example:**
```json
{
    "data": [
        {
            "id": 1,
            "name": "Order Name",
            "description": "Description",
            "order_date": "2025-02-22",
            "products": [
                {
                    "id": 1,
                    "price": 29.99
                },
                {
                    "id": 2,
                    "price": 49.99
                }
            ]
        }
    ]
}
```

## Test Suite Overview

# Test Suite Documentation

## Overview

This test suite covers the order management system with two main test classes:
- `OrderControllerTest`: Tests the API endpoints for order management
- `StockManagerTest`: Tests the stock management business logic

## OrderControllerTest

### Test Setup
The test class uses Symfony's `WebTestCase` and handles test isolation by:
- Creating a test client for API requests
- Clearing the database before each test
- Providing utility methods for creating test data

### API Endpoints Tested

#### POST /api/orders
Creates new orders with the following test scenarios:
- Successful order creation with complete data
- Order creation with minimal data (only name)
- Validation of invalid dates
- Validation of empty names
- Handling of invalid JSON

#### PUT /api/orders/{id}
Updates existing orders with tests for:
- Full order updates
- Partial updates (single field)
- Handling of non-existent orders
- Validation of invalid dates
- Invalid JSON handling

#### DELETE /api/orders/{id}
Tests order deletion with verification of:
- Successful deletion
- Success message response
- Optional verification of order removal

#### GET /api/orders/orders
Tests order listing with various filters:
- Listing orders with their associated products
- Filtering by order name
- Date range filtering
- Multiple filter combinations

### Test Data Utilities

```php
createOrder(array $data): array
createOrderWithProducts(string $name, string $description, string $date, array $productPrices): Order
createProduct(float $price): Product
```

## StockManagerTest

### Test Setup
Uses Symfony's `KernelTestCase` for testing business logic with:
- Database isolation between tests
- Direct service testing without HTTP layer

### Functionality Tested

#### Order Creation
- Verifies stock decrease on order creation
- Validates insufficient stock handling
- Tests stock level updates

#### Order Cancellation
- Verifies stock increase on cancellation
- Tests stock level restoration

#### Stock Validation
- Tests sufficient stock scenarios
- Tests insufficient stock scenarios

### Key Test Methods

```php
testProcessOrderCreationDecreasesStock()
testProcessOrderCreationWithInsufficientStock()
testProcessOrderCancellationIncreasesStock()
testValidateStockWithSufficientStock()
testValidateStockWithInsufficientStock()
```

## Running Tests

From within the PHP container:
```bash
php bin/phpunit
```

# Project Setup Guide

## Prerequisites

Before you begin, ensure you have the following installed:
- Git
- Docker and Docker Compose
- Composer (PHP package manager)

## Installation Steps

### 1. Clone the Repository

```bash
git clone https://github.com/LorenzoTesoro/test_project.git
```

### 2. Build and Start Docker Containers

Build the containers:
```bash
docker compose build
```

Start the containers:
```bash
docker compose up
```

### 3. Access PHP Container

Open a new terminal and execute:
```bash
docker exec -it test_project_php bash
```

### 4. Install Dependencies and Setup Database

Once inside the PHP container, run the following commands:

```bash
# Install PHP dependencies
composer install

# Run database migrations
php bin/console doctrine:migrations:migrate

# Execute test suite
php bin/phpunit
```

## Verification

After completing these steps, your project should be up and running with:
- All dependencies installed
- Database migrations applied
- Test suite executed successfully
