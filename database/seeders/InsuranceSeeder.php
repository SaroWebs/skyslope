<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InsurancePolicy;
use App\Models\Claim;
use App\Models\ExtendedCare;
use App\Models\User;

class InsuranceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some users for testing
        $users = User::limit(5)->get();

        foreach ($users as $user) {
            // Create insurance policies
            $policies = [
                [
                    'policy_number' => InsurancePolicy::generatePolicyNumber(),
                    'insurance_type' => 'comprehensive',
                    'coverage_amount' => 500000,
                    'premium_amount' => 20000,
                    'start_date' => now()->subMonths(6),
                    'end_date' => now()->addMonths(6),
                    'status' => 'active',
                    'payment_status' => 'paid',
                    'terms_accepted' => true,
                ],
                [
                    'policy_number' => InsurancePolicy::generatePolicyNumber(),
                    'insurance_type' => 'third_party',
                    'coverage_amount' => 1000000,
                    'premium_amount' => 15000,
                    'start_date' => now()->subMonths(3),
                    'end_date' => now()->addMonths(9),
                    'status' => 'active',
                    'payment_status' => 'paid',
                    'terms_accepted' => true,
                ],
            ];

            foreach ($policies as $policyData) {
                $policy = $user->insurancePolicies()->create($policyData);

                // Create some claims for the policies
                if ($policy->insurance_type === 'comprehensive') {
                    $claims = [
                        [
                            'claim_number' => Claim::generateClaimNumber(),
                            'incident_date' => now()->subDays(30),
                            'incident_description' => 'Minor collision at intersection during rainy weather',
                            'claim_amount' => 15000,
                            'status' => 'approved',
                            'approved_by' => 1,
                            'approved_at' => now()->subDays(25),
                            'paid_at' => now()->subDays(20),
                        ],
                        [
                            'claim_number' => Claim::generateClaimNumber(),
                            'incident_date' => now()->subDays(60),
                            'incident_description' => 'Scratch damage while parking',
                            'claim_amount' => 5000,
                            'status' => 'pending',
                        ],
                    ];

                    foreach ($claims as $claimData) {
                        $policy->claims()->create($claimData);
                    }
                }

                // Create some extended care requests
                $careRequests = [
                    [
                        'care_type' => 'emergency',
                        'status' => 'completed',
                        'request_date' => now()->subDays(15),
                        'completion_date' => now()->subDays(14),
                        'service_provider' => 'Emergency Services Ltd',
                        'cost_incurred' => 2000,
                        'coverage_applied' => 2000,
                        'notes' => 'Minor accident assistance provided',
                    ],
                    [
                        'care_type' => 'roadside',
                        'status' => 'active',
                        'request_date' => now()->subDays(5),
                        'service_provider' => 'Roadside Assistance Co',
                        'cost_incurred' => 1000,
                        'coverage_applied' => 1000,
                        'notes' => 'Vehicle breakdown assistance',
                    ],
                ];

                foreach ($careRequests as $careData) {
                    $user->extendedCareRequests()->create(array_merge($careData, [
                        'insurance_id' => $policy->id
                    ]));
                }
            }
        }
    }
}