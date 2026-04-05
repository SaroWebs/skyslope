<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\RideBooking;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Exception;

class CommissionService
{
    /**
     * Calculate commission for a ride booking
     */
    public function calculateCommission(RideBooking $booking): float
    {
        $baseCommissionRate = 0.20; // 20%
        $surgeMultiplier = $booking->surge_multiplier ?? 1.0;
        
        // Dynamic commission based on booking type
        $commissionRate = match($booking->service_type) {
            'point_to_point' => $baseCommissionRate,
            'hourly_rental' => $baseCommissionRate + 0.05, // 25%
            'round_trip' => $baseCommissionRate - 0.02,    // 18%
            default => $baseCommissionRate,
        };
        
        return round($booking->total_fare * $commissionRate * $surgeMultiplier, 2);
    }
    
    /**
     * Process payment and handle commission
     */
    public function processPayment(RideBooking $booking): bool
    {
        try {
            // Get driver's wallet
            $driverWallet = $booking->driver->wallet;
            
            if (!$driverWallet) {
                throw new Exception('Driver wallet not found');
            }
            
            if (!$driverWallet->isActive()) {
                throw new Exception('Driver wallet is not active');
            }
            
            // Calculate commission and driver share
            $commission = $this->calculateCommission($booking);
            $driverShare = $booking->total_fare - $commission;
            
            // Start transaction
            \DB::beginTransaction();
            
            try {
                // Handle commission deduction for cash payments
                if ($booking->payment_method === 'cash') {
                    // Deduct commission from driver wallet
                    $commissionSuccess = $driverWallet->processCommission(
                        $commission,
                        'Commission for booking ' . $booking->booking_number,
                        $booking->id
                    );
                    
                    if (!$commissionSuccess) {
                        throw new Exception('Failed to deduct commission from driver wallet');
                    }
                }
                
                // Add driver share to wallet
                $creditSuccess = $driverWallet->credit(
                    $driverShare,
                    'Payment for booking ' . $booking->booking_number,
                    $booking->id
                );
                
                if (!$creditSuccess) {
                    throw new Exception('Failed to credit driver wallet');
                }
                
                // Update booking status
                $booking->update([
                    'payment_status' => 'completed',
                    'commission_amount' => $commission,
                    'driver_share' => $driverShare,
                ]);
                
                \DB::commit();
                return true;
                
            } catch (Exception $e) {
                \DB::rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            // Log error
            \Log::error('Commission processing failed: ' . $e->getMessage(), [
                'booking_id' => $booking->id,
                'driver_id' => $booking->driver_id,
            ]);
            
            return false;
        }
    }
    
    /**
     * Get commission statistics for a driver
     */
    public function getDriverCommissionStats(int $driverId, ?string $startDate = null, ?string $endDate = null)
    {
        $query = WalletTransaction::where('transaction_type', 'commission')
            ->whereHas('wallet', function ($query) use ($driverId) {
                $query->where('owner_type', Driver::class)
                    ->where('owner_id', $driverId);
            });
        
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        
        $totalCommission = $query->sum('amount');
        $commissionCount = $query->count();
        
        return [
            'total_commission' => $totalCommission,
            'commission_count' => $commissionCount,
            'average_commission' => $commissionCount > 0 ? $totalCommission / $commissionCount : 0,
        ];
    }
    
    /**
     * Get platform commission statistics
     */
    public function getPlatformCommissionStats(?string $startDate = null, ?string $endDate = null)
    {
        $query = WalletTransaction::where('transaction_type', 'commission');
        
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        
        $totalCommission = $query->sum('amount');
        $commissionCount = $query->count();
        $activeDrivers = $query->distinct('wallet_id')->count('wallet_id');
        
        return [
            'total_commission' => $totalCommission,
            'commission_count' => $commissionCount,
            'active_drivers' => $activeDrivers,
            'average_commission_per_driver' => $activeDrivers > 0 ? $totalCommission / $activeDrivers : 0,
        ];
    }
    
    /**
     * Process driver withdrawal
     */
    public function processDriverWithdrawal(int $driverId, float $amount, string $description = ''): bool
    {
        try {
            $driver = Driver::find($driverId);
            $driverWallet = $driver ? Wallet::forOwner($driver)->first() : null;
            
            if (!$driverWallet) {
                throw new Exception('Driver wallet not found');
            }
            
            if (!$driverWallet->isActive()) {
                throw new Exception('Driver wallet is not active');
            }
            
            if ($driverWallet->getBalance() < $amount) {
                throw new Exception('Insufficient wallet balance');
            }
            
            // Debit amount from wallet
            $success = $driverWallet->debit(
                $amount,
                $description ?: 'Driver withdrawal',
                null
            );
            
            if (!$success) {
                throw new Exception('Failed to process withdrawal');
            }
            
            return true;
            
        } catch (Exception $e) {
            \Log::error('Driver withdrawal failed: ' . $e->getMessage(), [
                'driver_id' => $driverId,
                'amount' => $amount,
            ]);
            
            return false;
        }
    }
}
