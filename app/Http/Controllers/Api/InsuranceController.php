<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InsurancePolicy;
use App\Models\Claim;
use App\Models\ExtendedCare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InsuranceController extends Controller
{
    /**
     * Get user's insurance policies
     */
    public function getPolicies(Request $request)
    {
        if (!$request->user()->isCustomer()) {
            return response()->json(['success' => false, 'message' => 'Only customer accounts can access insurance.'], 403);
        }

        $policies = InsurancePolicy::where('user_id', $request->user()->id)
            ->with(['claims'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $policies,
        ]);
    }

    /**
     * Get insurance policy by ID
     */
    public function getPolicy(Request $request, $id)
    {
        if (!$request->user()->isCustomer()) {
            return response()->json(['success' => false, 'message' => 'Only customer accounts can access insurance.'], 403);
        }

        $policy = InsurancePolicy::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->with(['claims'])
            ->first();

        if (!$policy) {
            return response()->json([
                'success' => false,
                'message' => 'Policy not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $policy,
        ]);
    }

    /**
     * Create new insurance policy
     */
    public function createPolicy(Request $request)
    {
        if (!$request->user()->isCustomer()) {
            return response()->json(['success' => false, 'message' => 'Only customer accounts can access insurance.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'insurance_type' => 'required|in:comprehensive,third_party,personal_accident',
            'coverage_amount' => 'required|numeric|min:1000',
            'premium_amount' => 'required|numeric|min:100',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'terms_accepted' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $policy = InsurancePolicy::create([
            'user_id' => $request->user()->id,
            'policy_number' => InsurancePolicy::generatePolicyNumber(),
            'insurance_type' => $request->insurance_type,
            'coverage_amount' => $request->coverage_amount,
            'premium_amount' => $request->premium_amount,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => 'active',
            'payment_status' => 'pending',
            'terms_accepted' => $request->terms_accepted,
        ]);

        return response()->json([
            'success' => true,
            'data' => $policy,
            'message' => 'Insurance policy created successfully',
        ], 201);
    }

    /**
     * Update insurance policy
     */
    public function updatePolicy(Request $request, $id)
    {
        if (!$request->user()->isCustomer()) {
            return response()->json(['success' => false, 'message' => 'Only customer accounts can access insurance.'], 403);
        }

        $policy = InsurancePolicy::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->first();

        if (!$policy) {
            return response()->json([
                'success' => false,
                'message' => 'Policy not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'coverage_amount' => 'numeric|min:1000',
            'premium_amount' => 'numeric|min:100',
            'status' => 'in:active,expired,cancelled',
            'payment_status' => 'in:paid,pending,failed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $policy->update($request->only([
            'coverage_amount',
            'premium_amount',
            'status',
            'payment_status',
        ]));

        return response()->json([
            'success' => true,
            'data' => $policy,
            'message' => 'Policy updated successfully',
        ]);
    }

    /**
     * Cancel insurance policy
     */
    public function cancelPolicy(Request $request, $id)
    {
        if (!$request->user()->isCustomer()) {
            return response()->json(['success' => false, 'message' => 'Only customer accounts can access insurance.'], 403);
        }

        $policy = InsurancePolicy::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->first();

        if (!$policy) {
            return response()->json([
                'success' => false,
                'message' => 'Policy not found',
            ], 404);
        }

        $policy->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Policy cancelled successfully',
        ]);
    }

    /**
     * Get user's claims
     */
    public function getClaims(Request $request)
    {
        if (!$request->user()->isCustomer()) {
            return response()->json(['success' => false, 'message' => 'Only customer accounts can access insurance.'], 403);
        }

        $claims = Claim::whereHas('insurance', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })
        ->with(['insurance'])
        ->latest()
        ->get();

        return response()->json([
            'success' => true,
            'data' => $claims,
        ]);
    }

    /**
     * Create new claim
     */
    public function createClaim(Request $request)
    {
        if (!$request->user()->isCustomer()) {
            return response()->json(['success' => false, 'message' => 'Only customer accounts can access insurance.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'insurance_id' => 'required|exists:insurance_policies,id',
            'incident_date' => 'required|date|before_or_equal:today',
            'incident_description' => 'required|string|max:1000',
            'claim_amount' => 'required|numeric|min:100',
            'documents' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Verify insurance belongs to user
        $insurance = InsurancePolicy::where('user_id', $request->user()->id)
            ->where('id', $request->insurance_id)
            ->first();

        if (!$insurance) {
            return response()->json([
                'success' => false,
                'message' => 'Insurance policy not found or does not belong to you',
            ], 404);
        }

        if (!$insurance->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Insurance policy is not active',
            ], 400);
        }

        $claim = Claim::create([
            'insurance_id' => $request->insurance_id,
            'claim_number' => Claim::generateClaimNumber(),
            'incident_date' => $request->incident_date,
            'incident_description' => $request->incident_description,
            'claim_amount' => $request->claim_amount,
            'status' => 'pending',
            'documents' => $request->documents,
        ]);

        return response()->json([
            'success' => true,
            'data' => $claim,
            'message' => 'Claim submitted successfully',
        ], 201);
    }

    /**
     * Get user's extended care requests
     */
    public function getExtendedCare(Request $request)
    {
        if (!$request->user()->isCustomer()) {
            return response()->json(['success' => false, 'message' => 'Only customer accounts can access insurance.'], 403);
        }

        $careRequests = ExtendedCare::where('user_id', $request->user()->id)
            ->with(['insurance'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $careRequests,
        ]);
    }

    /**
     * Request emergency assistance
     */
    public function requestAssistance(Request $request)
    {
        if (!$request->user()->isCustomer()) {
            return response()->json(['success' => false, 'message' => 'Only customer accounts can access insurance.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'care_type' => 'required|in:emergency,roadside,medical,legal',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $care = ExtendedCare::requestAssistance(
            $request->user()->id,
            $request->care_type,
            $request->notes
        );

        return response()->json([
            'success' => true,
            'data' => $care,
            'message' => 'Assistance requested successfully',
        ], 201);
    }

    /**
     * Cancel assistance request
     */
    public function cancelAssistance(Request $request, $id)
    {
        if (!$request->user()->isCustomer()) {
            return response()->json(['success' => false, 'message' => 'Only customer accounts can access insurance.'], 403);
        }

        $care = ExtendedCare::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->first();

        if (!$care) {
            return response()->json([
                'success' => false,
                'message' => 'Assistance request not found',
            ], 404);
        }

        if (!$care->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel completed or cancelled request',
            ], 400);
        }

        $care->cancel();

        return response()->json([
            'success' => true,
            'message' => 'Assistance request cancelled successfully',
        ]);
    }
}
