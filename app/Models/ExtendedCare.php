<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtendedCare extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'insurance_id',
        'care_type',
        'status',
        'request_date',
        'completion_date',
        'service_provider',
        'cost_incurred',
        'coverage_applied',
        'notes',
    ];

    protected $casts = [
        'request_date' => 'date',
        'completion_date' => 'date',
        'cost_incurred' => 'decimal:2',
        'coverage_applied' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }

    public function insurance(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicy::class, 'insurance_id');
    }

    /**
     * Get care types
     */
    public static function getCareTypes(): array
    {
        return ['emergency', 'roadside', 'medical', 'legal'];
    }

    /**
     * Get care statuses
     */
    public static function getStatuses(): array
    {
        return ['active', 'in_progress', 'completed', 'cancelled'];
    }

    /**
     * Request emergency assistance
     */
    public static function requestAssistance(int $userId, string $careType, string $notes = ''): self
    {
        return self::create([
            'user_id' => $userId,
            'care_type' => $careType,
            'status' => 'active',
            'request_date' => now(),
            'notes' => $notes,
        ]);
    }

    /**
     * Update care status
     */
    public function updateStatus(string $status, ?string $serviceProvider = null, ?float $cost = null): bool
    {
        $data = ['status' => $status];

        if ($status === 'completed') {
            $data['completion_date'] = now();
        }

        if ($serviceProvider) {
            $data['service_provider'] = $serviceProvider;
        }

        if ($cost !== null) {
            $data['cost_incurred'] = $cost;
        }

        return $this->update($data);
    }

    /**
     * Check if care is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' || $this->status === 'in_progress';
    }

    /**
     * Cancel care request
     */
    public function cancel(): bool
    {
        return $this->update(['status' => 'cancelled']);
    }
}
