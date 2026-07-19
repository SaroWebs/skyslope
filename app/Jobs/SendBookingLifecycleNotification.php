<?php

namespace App\Jobs;

use App\Models\CarRental;
use App\Models\RideBooking;
use App\Models\TourBooking;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SendBookingLifecycleNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public string $bookingType,
        public int $bookingId,
        public string $action,
        public array $metadata = []
    ) {
        $this->onQueue('notifications');
    }

    public function handle(NotificationService $notifications): void
    {
        $booking = $this->booking();
        if (!$booking || !$booking->customer) {
            Log::warning('Booking notification skipped: booking or customer missing', $this->logContext());
            return;
        }

        $content = $this->contentFor($booking);
        $channels = $this->channelsFor($booking);

        $results = $notifications->notify($booking->customer, $channels, $content);
        $failed = collect($channels)
            ->filter(fn (string $channel) => array_key_exists($channel, $results)
                && $results[$channel] === false
                && $this->channelConfigured($channel))
            ->values()
            ->all();
        $skipped = collect($channels)
            ->filter(fn (string $channel) => array_key_exists($channel, $results)
                && $results[$channel] === false
                && !$this->channelConfigured($channel))
            ->values()
            ->all();

        if ($skipped !== []) {
            Log::warning('Booking notification channel skipped: provider not configured', $this->logContext([
                'skipped_channels' => $skipped,
            ]));
        }

        if ($failed !== []) {
            Log::warning('Booking notification delivery failed', $this->logContext([
                'failed_channels' => $failed,
            ]));

            throw new RuntimeException('Booking notification failed for channels: '.implode(', ', $failed));
        }

        Log::info('Booking notification queued delivery completed', $this->logContext([
            'channels' => array_keys($results),
        ]));
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Booking notification job failed permanently', $this->logContext([
            'error' => $exception->getMessage(),
        ]));
    }

    private function booking(): ?Model
    {
        return match ($this->bookingType) {
            'ride' => RideBooking::with('customer', 'driver')->find($this->bookingId),
            'tour' => TourBooking::with('customer', 'assignedDriver')->find($this->bookingId),
            'rental' => CarRental::with('customer', 'driver')->find($this->bookingId),
            default => null,
        };
    }

    /**
     * @return array<int, string>
     */
    private function channelsFor(Model $booking): array
    {
        $channels = [];

        if ($booking->sms_notification ?? true) {
            $channels[] = 'sms';
        }
        if ($booking->whatsapp_notification ?? true) {
            $channels[] = 'whatsapp';
        }
        if (($booking->email_notification ?? false) && !empty($booking->customer?->email)) {
            $channels[] = 'email';
        }

        return array_values(array_unique($channels));
    }

    /**
     * @return array<string, mixed>
     */
    private function contentFor(Model $booking): array
    {
        $label = ucfirst($this->bookingType);
        $bookingNumber = $booking->booking_number ?: (string) $booking->id;
        $message = match ($this->action) {
            'booking.created' => "{$label} booking #{$bookingNumber} has been created.",
            'driver.assigned' => "A driver has been assigned to {$label} booking #{$bookingNumber}.",
            'booking.accepted' => "{$label} booking #{$bookingNumber} has been accepted.",
            'booking.declined' => "{$label} booking #{$bookingNumber} has been declined.",
            'booking.started' => "{$label} booking #{$bookingNumber} has started.",
            'booking.completed' => "{$label} booking #{$bookingNumber} has been completed.",
            'booking.cancelled' => "{$label} booking #{$bookingNumber} has been cancelled.",
            'payment.paid' => "Payment received for {$label} booking #{$bookingNumber}.",
            'payment.failed' => "Payment failed for {$label} booking #{$bookingNumber}.",
            'refund.processed' => "Refund processed for {$label} booking #{$bookingNumber}.",
            default => "{$label} booking #{$bookingNumber} update: {$this->action}.",
        };

        return [
            'sms' => 'HappyMiles: '.$message,
            'whatsapp' => "*HappyMiles Update*\n\n".$message,
            'email' => $message,
            'subject' => "HappyMiles {$label} Booking Update",
        ];
    }

    private function channelConfigured(string $channel): bool
    {
        return match ($channel) {
            'sms' => filled(config('services.twilio.sid')) && filled(config('services.twilio.token')),
            'whatsapp' => filled(config('services.whatsapp.api_url')) && filled(config('services.whatsapp.api_key')),
            'email' => true,
            default => false,
        };
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function logContext(array $extra = []): array
    {
        return array_merge([
            'booking_type' => $this->bookingType,
            'booking_id' => $this->bookingId,
            'action' => $this->action,
            'metadata' => $this->metadata,
        ], $extra);
    }
}
