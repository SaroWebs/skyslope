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
    public ?string $message;

    /**
     * Create a new event instance.
     */
    public function __construct(RideBooking $booking, string $status, ?string $message = null)
    {
        $this->booking = $booking;
        $this->status = $status;
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel("ride.{$this->booking->id}"),
            new PrivateChannel("user.{$this->booking->user_id}.rides"),
        ];

        // Also broadcast to driver if assigned
        if ($this->booking->driver_id) {
            $channels[] = new PrivateChannel("driver.{$this->booking->driver_id}.rides");
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'status.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'booking_id' => $this->booking->id,
            'booking_number' => $this->booking->booking_number,
            'status' => $this->status,
            'message' => $this->message,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
