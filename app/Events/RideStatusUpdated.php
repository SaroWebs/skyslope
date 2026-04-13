<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\RideBooking;

class RideStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public RideBooking $booking;
    public string $status;
    public ?string $previousStatus;
    public ?string $message;

    public function __construct(RideBooking $booking, string $status, ?string $message = null, ?string $previousStatus = null)
    {
        $this->booking = $booking;
        $this->status = $status;
        $this->message = $message;
        $this->previousStatus = $previousStatus;
    }

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel("ride.{$this->booking->id}"),
        ];

        // Broadcast to customer channel
        if ($this->booking->customer_id) {
            $channels[] = new PrivateChannel("customer.{$this->booking->customer_id}");
        }

        // Broadcast to driver channel
        if ($this->booking->driver_id) {
            $channels[] = new PrivateChannel("driver.{$this->booking->driver_id}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'ride.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'ride_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'status' => $this->status,
            'previous_status' => $this->previousStatus,
            'message' => $this->message,
            'pickup_location' => $this->booking->pickup_location,
            'dropoff_location' => $this->booking->dropoff_location,
            'total_fare' => $this->booking->total_fare,
            'driver_id' => $this->booking->driver_id,
            'customer_id' => $this->booking->customer_id,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
