<?php

use App\Jobs\SendBookingLifecycleNotification;
use App\Models\Customer;
use App\Models\RideBooking;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

it('delivers booking lifecycle notifications through enabled channels', function () {
    $customer = Customer::create([
        'name' => 'Lifecycle Customer',
        'phone' => '9400000001',
        'email' => 'lifecycle@example.com',
    ]);

    $ride = RideBooking::create([
        'customer_id' => $customer->id,
        'service_type' => 'point_to_point',
        'customer_name' => $customer->name,
        'customer_email' => $customer->email,
        'customer_phone' => $customer->phone,
        'pickup_location' => 'Gate',
        'scheduled_at' => now()->addDay(),
        'estimated_distance_km' => 7,
        'total_fare' => 280,
        'status' => 'confirmed',
        'payment_status' => 'paid',
        'payment_method' => 'cash',
        'sms_notification' => true,
        'whatsapp_notification' => true,
        'email_notification' => true,
    ]);

    $service = new class extends NotificationService {
        public array $calls = [];

        public function notify(object $user, array $channels, array $content): array
        {
            $this->calls[] = compact('user', 'channels', 'content');

            return array_fill_keys($channels, true);
        }
    };

    (new SendBookingLifecycleNotification('ride', $ride->id, 'payment.paid'))->handle($service);

    expect($service->calls)->toHaveCount(1)
        ->and($service->calls[0]['channels'])->toBe(['sms', 'whatsapp', 'email'])
        ->and($service->calls[0]['content']['sms'])->toContain('Payment received');
});

it('retries and logs failed booking lifecycle notification channels', function () {
    Log::spy();
    config([
        'services.twilio.sid' => 'test-sid',
        'services.twilio.token' => 'test-token',
    ]);

    $customer = Customer::create([
        'name' => 'Failed Lifecycle Customer',
        'phone' => '9400000002',
    ]);

    $ride = RideBooking::create([
        'customer_id' => $customer->id,
        'service_type' => 'point_to_point',
        'customer_name' => $customer->name,
        'customer_phone' => $customer->phone,
        'pickup_location' => 'Gate',
        'scheduled_at' => now()->addDay(),
        'estimated_distance_km' => 7,
        'total_fare' => 280,
        'status' => 'confirmed',
        'payment_status' => 'pending',
        'payment_method' => 'cash',
        'sms_notification' => true,
        'whatsapp_notification' => false,
        'email_notification' => false,
    ]);

    $service = new class extends NotificationService {
        public function notify(object $user, array $channels, array $content): array
        {
            return ['sms' => false];
        }
    };

    $job = new SendBookingLifecycleNotification('ride', $ride->id, 'booking.created');

    expect($job->tries)->toBe(3)
        ->and($job->backoff)->toBe([60, 300, 900]);

    expect(fn () => $job->handle($service))
        ->toThrow(RuntimeException::class, 'Booking notification failed for channels: sms');

    Log::shouldHaveReceived('warning')
        ->with('Booking notification delivery failed', Mockery::on(
            fn (array $context) => $context['booking_type'] === 'ride'
                && $context['booking_id'] === $ride->id
                && $context['action'] === 'booking.created'
                && $context['failed_channels'] === ['sms']
        ))
        ->once();
});
