<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\RideBooking;

class NewRideRequest implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public RideBooking $booking,
        public array $candidateDriverIds = [],
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('drivers.available'),
        ];

        foreach ($this->candidateDriverIds as $driverId) {
            $channels[] = new PrivateChannel("driver.{$driverId}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'ride.new_request';
    }

    public function broadcastWith(): array
    {
        return [
            'ride_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'pickup_location' => $this->booking->pickup_location,
            'dropoff_location' => $this->booking->dropoff_location,
            'pickup_lat' => $this->booking->pickup_lat,
            'pickup_lng' => $this->booking->pickup_lng,
            'total_fare' => $this->booking->total_fare,
            'service_type' => $this->booking->service_type,
            'distance_km' => $this->booking->estimated_distance_km,
            'estimated_distance_km' => $this->booking->estimated_distance_km,
            'candidate_driver_ids' => $this->candidateDriverIds,
            'targeted' => $this->candidateDriverIds !== [],
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
