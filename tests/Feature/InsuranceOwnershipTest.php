<?php

use App\Models\Customer;
use App\Models\Driver;
use App\Models\InsurancePolicy;
use Laravel\Sanctum\Sanctum;

it('scopes customer insurance policies by customer id', function () {
    $customer = Customer::create(['name' => 'Policy Customer', 'phone' => '9600000001']);
    $otherCustomer = Customer::create(['name' => 'Other Policy Customer', 'phone' => '9600000002']);

    $ownPolicy = InsurancePolicy::create([
        'policy_number' => 'POLICY-OWN',
        'customer_id' => $customer->id,
        'policy_type' => 'comprehensive',
        'premium' => 500,
        'coverage_amount' => 5000,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addYear()->toDateString(),
        'status' => 'active',
    ]);

    InsurancePolicy::create([
        'policy_number' => 'POLICY-OTHER',
        'customer_id' => $otherCustomer->id,
        'policy_type' => 'basic',
        'premium' => 300,
        'coverage_amount' => 3000,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addYear()->toDateString(),
        'status' => 'active',
    ]);

    Sanctum::actingAs($customer);

    $this->getJson('/api/customer-app/insurance/policies')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ownPolicy->id)
        ->assertJsonPath('data.0.customer_id', $customer->id);
});

it('rejects driver access to customer insurance policies', function () {
    $driver = Driver::create([
        'name' => 'Insurance Driver',
        'phone' => '8600000001',
        'status' => 'active',
        'is_active' => true,
        'is_approved' => true,
    ]);

    Sanctum::actingAs($driver);

    $this->getJson('/api/customer-app/insurance/policies')
        ->assertForbidden()
        ->assertJsonPath('success', false);
});
