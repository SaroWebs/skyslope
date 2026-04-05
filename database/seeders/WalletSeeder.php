<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Driver;

class WalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users who are drivers for wallet seeding
        $drivers = Driver::all();

        foreach ($drivers as $driver) {
            // Create wallet for driver
            $wallet = $driver->wallet()->firstOrCreate([], [
                'balance' => rand(5000, 50000),
                'currency' => 'INR',
                'status' => 'active',
            ]);

            // Create wallet transactions
            $transactions = [
                [
                    'transaction_type' => 'credit',
                    'amount' => 10000,
                    'description' => 'Initial wallet top-up',
                    'status' => 'completed',
                ],
                [
                    'transaction_type' => 'credit',
                    'amount' => 5000,
                    'description' => 'Payment for booking RIDE20251201ABCD',
                    'status' => 'completed',
                ],
                [
                    'transaction_type' => 'commission',
                    'amount' => 1000,
                    'description' => 'Commission for booking RIDE20251201ABCD',
                    'status' => 'completed',
                ],
                [
                    'transaction_type' => 'debit',
                    'amount' => 2000,
                    'description' => 'Driver withdrawal',
                    'status' => 'completed',
                ],
                [
                    'transaction_type' => 'credit',
                    'amount' => 8000,
                    'description' => 'Payment for booking RIDE20251202EFGH',
                    'status' => 'completed',
                ],
                [
                    'transaction_type' => 'commission',
                    'amount' => 800,
                    'description' => 'Commission for booking RIDE20251202EFGH',
                    'status' => 'completed',
                ],
            ];

            foreach ($transactions as $transactionData) {
                $wallet->transactions()->create($transactionData);
            }
        }

        // Create wallets for some customers too
        $customers = Customer::limit(3)->get();

        foreach ($customers as $customer) {
            $wallet = $customer->wallet()->firstOrCreate([], [
                'balance' => rand(1000, 10000),
                'currency' => 'INR',
                'status' => 'active',
            ]);

            $wallet->transactions()->create([
                'transaction_type' => 'topup',
                'amount' => 5000,
                'description' => 'Wallet top-up via credit card',
                'status' => 'completed',
            ]);
        }
    }
}
