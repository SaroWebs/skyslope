# Enhanced Features API Documentation

This document provides comprehensive documentation for the new enhanced features implemented in the transportation and insurance platform.

## Table of Contents

1. [Insurance Management API](#insurance-management-api)
2. [Wallet Management API](#wallet-management-api)
3. [Place Category API](#place-category-api)
4. [Extended Care API](#extended-care-api)
5. [Frontend Components](#frontend-components)
6. [Integration Examples](#integration-examples)

## Insurance Management API

### Base URL
```
/api/insurance
```

### Authentication
All endpoints require authentication via Bearer token in the Authorization header.

### Endpoints

#### 1. Get User's Insurance Policies
```http
GET /api/insurance/policies
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "policy_number": "POL-2025-000001",
      "insurance_type": "comprehensive",
      "coverage_amount": 500000,
      "premium_amount": 20000,
      "start_date": "2025-01-01",
      "end_date": "2026-01-01",
      "status": "active",
      "payment_status": "paid",
      "claims": [...]
    }
  ]
}
```

#### 2. Get Insurance Policy by ID
```http
GET /api/insurance/policies/{id}
```

#### 3. Create New Insurance Policy
```http
POST /api/insurance/policies
Content-Type: application/json

{
  "insurance_type": "comprehensive",
  "coverage_amount": 500000,
  "premium_amount": 20000,
  "start_date": "2025-01-01",
  "end_date": "2026-01-01",
  "terms_accepted": true
}
```

**Validation Rules:**
- `insurance_type`: required, in: comprehensive, third_party, personal_accident
- `coverage_amount`: required, numeric, min:1000
- `premium_amount`: required, numeric, min:100
- `start_date`: required, date, after_or_equal:today
- `end_date`: required, date, after:start_date
- `terms_accepted`: required, boolean

#### 4. Update Insurance Policy
```http
PUT /api/insurance/policies/{id}
Content-Type: application/json

{
  "coverage_amount": 600000,
  "status": "active"
}
```

#### 5. Cancel Insurance Policy
```http
DELETE /api/insurance/policies/{id}
```

#### 6. Get User's Claims
```http
GET /api/insurance/claims
```

#### 7. Create New Claim
```http
POST /api/insurance/claims
Content-Type: application/json

{
  "insurance_id": 1,
  "incident_date": "2025-12-15",
  "incident_description": "Minor collision at intersection",
  "claim_amount": 15000,
  "documents": ["document1.pdf", "document2.jpg"]
}
```

#### 8. Get Extended Care Requests
```http
GET /api/insurance/extended-care
```

#### 9. Request Emergency Assistance
```http
POST /api/insurance/extended-care
Content-Type: application/json

{
  "care_type": "emergency",
  "notes": "Vehicle breakdown on highway"
}
```

#### 10. Cancel Assistance Request
```http
DELETE /api/insurance/extended-care/{id}
```

## Wallet Management API

### Base URL
```
/api/wallet
```

### Endpoints

#### 1. Get User's Wallet
```http
GET /api/wallet
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "balance": 15000.50,
    "currency": "INR",
    "status": "active"
  }
}
```

#### 2. Get Wallet Transactions
```http
GET /api/wallet/transactions?page=1&limit=20
```

#### 3. Top Up Wallet
```http
POST /api/wallet/topup
Content-Type: application/json

{
  "amount": 5000,
  "payment_method": "card"
}
```

#### 4. Withdraw from Wallet
```http
POST /api/wallet/withdraw
Content-Type: application/json

{
  "amount": 2000,
  "bank_account": "ACC123456789"
}
```

#### 5. Get Wallet Statistics
```http
GET /api/wallet/stats
```

**Response:**
```json
{
  "success": true,
  "data": {
    "wallet_balance": 15000.50,
    "commission_stats": {
      "total_commission": 2500,
      "commission_count": 5,
      "average_commission": 500
    }
  }
}
```

## Place Category API

### Base URL
```
/api/place-categories
```

### Endpoints

#### 1. Get All Place Categories
```http
GET /api/place-categories
```

#### 2. Get Featured Categories
```http
GET /api/place-categories/featured
```

#### 3. Get Category by ID
```http
GET /api/place-categories/{id}
```

#### 4. Create New Category (Admin Only)
```http
POST /api/place-categories
Content-Type: application/json

{
  "name": "Historical Sites",
  "description": "Ancient monuments and historical landmarks",
  "icon": "landmark",
  "color": "#8B4513",
  "is_active": true
}
```

#### 5. Update Category (Admin Only)
```http
PUT /api/place-categories/{id}
Content-Type: application/json

{
  "name": "Historical Sites",
  "is_active": false
}
```

#### 6. Delete Category (Admin Only)
```http
DELETE /api/place-categories/{id}
```

## Extended Care API

### Base URL
```
/api/extended-care
```

### Endpoints

#### 1. Request Assistance
```http
POST /api/extended-care/request
Content-Type: application/json

{
  "care_type": "emergency",
  "location": {
    "latitude": 12.9716,
    "longitude": 77.5946,
    "timestamp": "2025-12-31T10:30:00Z"
  },
  "notes": "Emergency assistance requested"
}
```

#### 2. Update Care Status
```http
PUT /api/extended-care/{id}/status
Content-Type: application/json

{
  "status": "completed",
  "service_provider": "Emergency Services Ltd",
  "cost_incurred": 2000
}
```

## Frontend Components

### Insurance Dashboard

**Location:** `resources/js/components/insurance/InsuranceDashboard.tsx`

**Usage:**
```tsx
import InsuranceDashboard from '@/components/insurance/InsuranceDashboard';

function App() {
  return (
    <div>
      <InsuranceDashboard />
    </div>
  );
}
```

**Features:**
- Policy summary with active policies, total coverage, and premium
- Recent claims display with status indicators
- Insurance calculator for quotes
- Policy management interface

### Extended Care Component

**Location:** `resources/js/components/insurance/ExtendedCare.tsx`

**Usage:**
```tsx
import EmergencyAssistance from '@/components/insurance/ExtendedCare';

function App() {
  return (
    <div>
      <EmergencyAssistance />
    </div>
  );
}
```

**Features:**
- Emergency assistance buttons for different care types
- Location detection and capture
- Emergency contact information
- Assistance request tracking

### Driver Wallet Component

**Location:** `resources/js/components/wallet/DriverWallet.tsx`

**Usage:**
```tsx
import DriverWallet from '@/components/wallet/DriverWallet';

function App() {
  return (
    <div>
      <DriverWallet />
    </div>
  );
}
```

**Features:**
- Wallet balance display
- Top-up functionality
- Transaction history
- Commission summary
- Withdrawal functionality

### Admin Insurance Management

**Location:** `resources/js/pages/admin/InsuranceManagement.tsx`

**Usage:**
```tsx
import InsuranceManagement from '@/pages/admin/InsuranceManagement';

function AdminApp() {
  return (
    <div>
      <InsuranceManagement />
    </div>
  );
}
```

**Features:**
- Policy statistics and management
- Claim statistics and approval workflow
- Revenue tracking
- Search and filter functionality

## Integration Examples

### Commission Processing Integration

```php
use App\Services\CommissionService;
use App\Models\RideBooking;

// Process commission after ride completion
$booking = RideBooking::find($bookingId);
$commissionService = new CommissionService();

if ($commissionService->processPayment($booking)) {
    // Payment processed successfully
    // Commission deducted and driver share credited to wallet
} else {
    // Handle payment processing failure
}
```

### Insurance Policy Creation

```php
use App\Models\InsurancePolicy;

$policy = InsurancePolicy::create([
    'user_id' => $userId,
    'policy_number' => InsurancePolicy::generatePolicyNumber(),
    'insurance_type' => 'comprehensive',
    'coverage_amount' => 500000,
    'premium_amount' => 20000,
    'start_date' => now(),
    'end_date' => now()->addYear(),
    'status' => 'active',
    'payment_status' => 'pending',
    'terms_accepted' => true,
]);
```

### Wallet Transaction Processing

```php
use App\Models\Wallet;

$wallet = Wallet::where('user_id', $userId)->first();

// Credit amount
$wallet->credit(5000, 'Ride payment', $bookingId);

// Debit amount
$wallet->debit(1000, 'Commission', $bookingId);

// Process withdrawal
$wallet->debit(2000, 'Driver withdrawal to bank account: ACC123456');
```

### Extended Care Request

```php
use App\Models\ExtendedCare;

$care = ExtendedCare::requestAssistance(
    $userId,
    'emergency',
    'Vehicle breakdown on highway'
);

// Update status
$care->updateStatus('in_progress', 'Roadside Assistance Co', 1000);
```

## Error Handling

All API endpoints follow consistent error response format:

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

## Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Internal Server Error

## Notes

- All monetary values are in INR (Indian Rupees)
- Policy numbers are auto-generated with format: POL-{YEAR}-{SEQUENCE}
- Claim numbers are auto-generated with format: CLM-{YEAR}-{SEQUENCE}
- Wallet transactions are atomic and use database transactions
- Commission rates are dynamic based on service type
- Extended care requests are linked to insurance policies for coverage tracking