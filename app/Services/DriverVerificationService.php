<?php

namespace App\Services;

use App\Models\Driver;

class DriverVerificationService
{
    // Product requirements, shared by onboarding and administrator activation.
    public const DOCUMENTS = [
        'driving_license' => ['label' => 'Driving licence', 'number_label' => 'Licence number', 'required' => true, 'has_expiry' => true],
        'government_id' => ['label' => 'Government ID (e.g. Aadhaar)', 'number_label' => 'ID number', 'required' => true, 'has_expiry' => false],
        'police_verification' => ['label' => 'Police verification', 'number_label' => 'Reference number', 'required' => true, 'has_expiry' => true],
        'tax_id' => ['label' => 'PAN / tax ID', 'number_label' => 'PAN number', 'required' => false, 'has_expiry' => false],
    ];

    public function summary(Driver $driver): array
    {
        $documents = $driver->documents()->get()->keyBy('type');
        $requirements = collect(self::DOCUMENTS)->map(function (array $definition, string $type) use ($documents) {
            $document = $documents->get($type);
            $status = $document?->status ?? 'missing';
            if ($document?->expires_at && $document->expires_at->lt(today())) {
                $status = 'expired';
            }

            return [
                'type' => $type,
                ...$definition,
                'status' => $status,
                'rejection_reason' => $document?->rejection_reason,
                'expires_at' => $document?->expires_at?->toDateString(),
            ];
        })->values();
        $required = $requirements->where('required', true);
        $incomplete = $required->where('status', '!=', 'approved');
        $actionable = $incomplete->whereIn('status', ['missing', 'rejected', 'expired']);
        $status = $incomplete->isEmpty() ? 'complete' : ($actionable->isEmpty() ? 'in_review' : 'action_required');

        return [
            'required_types' => $required->pluck('type')->all(),
            'missing_or_unverified' => $incomplete->pluck('type')->all(),
            'is_complete' => $incomplete->isEmpty(),
            'status' => $status,
            'approved_count' => $required->where('status', 'approved')->count(),
            'required_count' => $required->count(),
            'next_document' => $actionable->sortBy(fn ($item) => match ($item['status']) {
                'rejected' => 0, 'expired' => 1, default => 2,
            })->first()['type'] ?? null,
            'message' => match ($status) {
                'complete' => 'All required driver documents are approved.',
                'in_review' => 'Your documents are being reviewed. No upload is needed right now.',
                default => 'Add or correct the marked documents. Approved documents do not need to be uploaded again.',
            },
            'requirements' => $requirements->all(),
        ];
    }
}
