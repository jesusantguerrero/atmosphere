# Multi-Currency Transaction API Endpoints

This document describes the new API endpoints for handling multi-currency credit card transactions.

## Overview

The multi-currency transaction endpoints allow you to:
- Create transactions in secondary currencies without immediate conversion
- Process credit card payments with currency conversion at payment time
- Retrieve currency balances for multi-currency accounts
- List transactions with multi-currency display information

## Authentication

All endpoints require authentication using Laravel Sanctum. Include the bearer token in the Authorization header:

```
Authorization: Bearer {your-token}
```

## Endpoints

### 1. Create Multi-Currency Transaction

**POST** `/api/multi-currency/transactions`

Creates a transaction in a secondary currency without immediate conversion to the account's primary currency.

#### Request Body

```json
{
  "account_id": 123,
  "total": 1500.00,
  "currency_code": "DOP",
  "description": "Restaurant bill in Dominican Republic",
  "date": "2024-01-15",
  "direction": "debit",
  "category_id": 456,
  "payee_id": 789,
  "status": "verified"
}
```

#### Response

```json
{
  "success": true,
  "data": {
    "id": 1001,
    "description": "Restaurant bill in Dominican Republic",
    "total": 1500.00,
    "currency_code": "DOP",
    "date": "2024-01-15",
    "direction": "debit",
    "status": "verified",
    "account": {
      "id": 123,
      "name": "Multi-Currency Credit Card",
      "currency_code": "USD",
      "is_multi_currency": true,
      "primary_currency": "USD",
      "secondary_currencies": ["DOP", "EUR"]
    },
    "multi_currency": {
      "is_converted": false,
      "is_secondary_currency": true,
      "display_currencies": {
        "transaction": {
          "code": "DOP",
          "amount": 1500.00,
          "is_primary": false
        }
      }
    }
  },
  "message": "Transaction created successfully"
}
```

### 2. Process Credit Card Payment

**POST** `/api/multi-currency/payments`

Processes a credit card payment with currency conversion from secondary currency to primary currency.

#### Request Body

```json
{
  "account_id": 123,
  "total": 1500.00,
  "exchange_amount": 85.00,
  "secondary_currency": "DOP",
  "payment_date": "2024-01-20",
  "description": "Credit card payment",
  "category_id": 456,
  "payee_id": 789
}
```

#### Response

```json
{
  "success": true,
  "data": {
    "id": 1002,
    "description": "Credit card payment - DOP to USD",
    "total": 1500.00,
    "currency_code": "DOP",
    "date": "2024-01-20",
    "multi_currency": {
      "is_converted": true,
      "exchange_rate": 0.056667,
      "exchange_amount": 85.00,
      "primary_currency_amount": 85.00,
      "secondary_currency_amount": 1500.00,
      "display_currencies": {
        "transaction": {
          "code": "DOP",
          "amount": 1500.00,
          "is_primary": false
        },
        "converted": {
          "code": "USD",
          "amount": 85.00,
          "is_primary": true,
          "exchange_rate": 0.056667
        }
      }
    }
  },
  "message": "Payment processed successfully"
}
```

### 3. Get Currency Balances

**GET** `/api/accounts/{account}/currency-balances`

Retrieves all currency balances for a multi-currency account.

#### Response

```json
{
  "success": true,
  "data": {
    "account_id": 123,
    "account_name": "Multi-Currency Credit Card",
    "is_multi_currency": true,
    "primary_currency": "USD",
    "secondary_currencies": ["DOP", "EUR"],
    "balances": {
      "USD": {
        "currency_code": "USD",
        "balance": -85.00,
        "pending_balance": 0.00,
        "total_balance": -85.00,
        "is_primary": true
      },
      "DOP": {
        "currency_code": "DOP",
        "balance": 0.00,
        "pending_balance": 0.00,
        "total_balance": 0.00,
        "is_primary": false
      }
    }
  }
}
```

### 4. Get Pending Balances

**GET** `/api/accounts/{account}/pending-balances`

Retrieves pending balances for a credit card account (charges not yet paid).

#### Response

```json
{
  "success": true,
  "data": {
    "account_id": 123,
    "account_name": "Multi-Currency Credit Card",
    "pending_balances": {
      "DOP": 0.00,
      "EUR": 250.00
    }
  }
}
```

### 5. List Multi-Currency Transactions

**GET** `/api/multi-currency/transactions`

Lists transactions with multi-currency display information.

#### Query Parameters

- `account_id` (optional): Filter by account ID
- `currency_code` (optional): Filter by currency code
- `start_date` (optional): Filter by start date (YYYY-MM-DD)
- `end_date` (optional): Filter by end date (YYYY-MM-DD)
- `limit` (optional): Number of results per page (1-100, default: 20)
- `page` (optional): Page number

#### Response

```json
{
  "success": true,
  "data": {
    "transactions": [
      {
        "id": 1001,
        "description": "Restaurant bill",
        "total": 1500.00,
        "currency_code": "DOP",
        "date": "2024-01-15",
        "multi_currency": {
          "is_converted": false,
          "is_secondary_currency": true,
          "display_currencies": {
            "transaction": {
              "code": "DOP",
              "amount": 1500.00,
              "is_primary": false
            }
          }
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 20,
      "total": 1
    }
  }
}
```

### 6. Get Single Transaction

**GET** `/api/multi-currency/transactions/{transaction}`

Retrieves a single transaction with multi-currency information.

#### Response

```json
{
  "success": true,
  "data": {
    "id": 1001,
    "description": "Restaurant bill",
    "total": 1500.00,
    "currency_code": "DOP",
    "date": "2024-01-15",
    "account": {
      "id": 123,
      "name": "Multi-Currency Credit Card",
      "is_multi_currency": true,
      "primary_currency": "USD"
    },
    "multi_currency": {
      "is_converted": false,
      "is_secondary_currency": true,
      "display_currencies": {
        "transaction": {
          "code": "DOP",
          "amount": 1500.00,
          "is_primary": false
        }
      }
    }
  }
}
```

## Enhanced Existing Endpoints

### Account Multi-Currency Balances

**GET** `/api/accounts/{account}/multi-currency-balances`

Enhanced version of account balance retrieval with formatted currency information and activity summary.

#### Response

```json
{
  "success": true,
  "data": {
    "account_id": 123,
    "account_name": "Multi-Currency Credit Card",
    "is_multi_currency": true,
    "primary_currency": "USD",
    "secondary_currencies": ["DOP", "EUR"],
    "balances": {
      "USD": {
        "currency_code": "USD",
        "balance": -85.00,
        "formatted_balance": "$-85.00",
        "pending_balance": 0.00,
        "formatted_pending_balance": "$0.00",
        "total_balance": -85.00,
        "formatted_total_balance": "$-85.00",
        "is_primary": true
      }
    },
    "activity_summary": {
      "total_transactions": 5,
      "multi_currency_transactions": 3,
      "currencies_used": {
        "USD": {
          "count": 2,
          "total_amount": 185.00
        },
        "DOP": {
          "count": 3,
          "total_amount": 4500.00
        }
      },
      "conversion_summary": {
        "DOP_to_USD": {
          "count": 2,
          "total_original": 3000.00,
          "total_converted": 170.00,
          "avg_rate": 0.056667
        }
      }
    }
  }
}
```

## Error Responses

All endpoints return consistent error responses:

```json
{
  "success": false,
  "message": "Error description here"
}
```

Common HTTP status codes:
- `422`: Validation error or business logic error
- `404`: Resource not found
- `403`: Unauthorized access
- `500`: Server error

## Integration with Existing Transaction Display

The existing transaction list endpoints now include multi-currency display information when transactions are enhanced by the `MultiCurrencyDisplayService`. This includes:

- `display_currencies`: Currency display information
- `is_multi_currency_transaction`: Boolean flag
- `conversion_info`: Conversion details if applicable
- `exchange_rate` and `exchange_amount`: Raw conversion data

This ensures backward compatibility while providing enhanced multi-currency information to frontend applications.