<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Exception;

class NotificationService
{
    protected ?string $twilioSid;
    protected ?string $twilioToken;
    protected ?string $twilioFrom;
    protected ?string $whatsappApiUrl;
    protected ?string $whatsappApiKey;
    protected ?string $whatsappFrom;

    public function __construct()
    {
        $this->twilioSid = config('services.twilio.sid');
        $this->twilioToken = config('services.twilio.token');
        $this->twilioFrom = config('services.twilio.from');
        $this->whatsappApiUrl = config('services.whatsapp.api_url');
        $this->whatsappApiKey = config('services.whatsapp.api_key');
        $this->whatsappFrom = config('services.whatsapp.from');
    }

    /**
     * Send SMS via Twilio
     *
     * @param string $to Phone number with country code
     * @param string $message SMS message content
     * @return bool
     */
    public function sendSms(string $to, string $message): bool
    {
        if (!$this->twilioSid || !$this->twilioToken) {
            Log::warning('Twilio credentials not configured');
            return false;
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth($this->twilioSid, $this->twilioToken)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->twilioSid}/Messages.json", [
                    'From' => $this->twilioFrom,
                    'To' => $to,
                    'Body' => $message,
                ]);

            if (!$response->successful()) {
                Log::error('Twilio SMS failed', [
                    'to' => $to,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            Log::info('SMS sent successfully', ['to' => $to]);
            return true;
        } catch (Exception $e) {
            Log::error('SMS sending exception', [
                'to' => $to,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send WhatsApp message via Business API
     *
     * @param string $to Phone number with country code
     * @param string $message Message content
     * @param array $templateData Template data for structured messages
     * @return bool
     */
    public function sendWhatsApp(string $to, string $message, array $templateData = []): bool
    {
        if (!$this->whatsappApiUrl || !$this->whatsappApiKey) {
            Log::warning('WhatsApp API credentials not configured');
            return false;
        }

        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
            ];

            // Use template if provided
            if (!empty($templateData)) {
                $payload['type'] = 'template';
                $payload['template'] = $templateData;
            } else {
                $payload['type'] = 'text';
                $payload['text'] = [
                    'preview_url' => false,
                    'body' => $message,
                ];
            }

            $response = Http::withToken($this->whatsappApiKey)
                ->post("{$this->whatsappApiUrl}/{$this->whatsappFrom}/messages", $payload);

            if (!$response->successful()) {
                Log::error('WhatsApp message failed', [
                    'to' => $to,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            Log::info('WhatsApp message sent successfully', ['to' => $to]);
            return true;
        } catch (Exception $e) {
            Log::error('WhatsApp sending exception', [
                'to' => $to,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send email notification
     *
     * @param string $to Email address
     * @param string $subject Email subject
     * @param string $view Blade view name
     * @param array $data Data to pass to view
     * @return bool
     */
    public function sendEmail(string $to, string $subject, string $view, array $data = []): bool
    {
        try {
            Mail::send($view, $data, function ($message) use ($to, $subject) {
                $message->to($to)
                    ->subject($subject);
            });

            if (count(Mail::failures()) > 0) {
                Log::error('Email sending failed', [
                    'to' => $to,
                    'failures' => Mail::failures(),
                ]);
                return false;
            }

            Log::info('Email sent successfully', ['to' => $to]);
            return true;
        } catch (Exception $e) {
            Log::error('Email sending exception', [
                'to' => $to,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send notification to user via multiple channels
     *
     * @param object $user User-like recipient to notify
     * @param array $channels Channels to use (sms, email, whatsapp)
     * @param array $content Content for each channel
     * @return array Results for each channel
     */
    public function notify(object $user, array $channels, array $content): array
    {
        $results = [];

        foreach ($channels as $channel) {
            switch ($channel) {
                case 'sms':
                    if (!empty($user->phone) && !empty($content['sms'])) {
                        $results['sms'] = $this->sendSms($user->phone, $content['sms']);
                    }
                    break;

                case 'email':
                    if (!empty($user->email) && !empty($content['email'])) {
                        $results['email'] = $this->sendEmail(
                            $user->email,
                            $content['subject'] ?? 'Notification from SkySlope',
                            $content['email_view'] ?? 'emails.notification',
                            $content['email_data'] ?? ['message' => $content['email']]
                        );
                    }
                    break;

                case 'whatsapp':
                    if (!empty($user->phone) && !empty($content['whatsapp'])) {
                        $results['whatsapp'] = $this->sendWhatsApp(
                            $user->phone,
                            $content['whatsapp'],
                            $content['whatsapp_template'] ?? []
                        );
                    }
                    break;
            }
        }

        return $results;
    }

    /**
     * Send ride booking confirmation notification
     *
     * @param object $user
     * @param array $bookingData
     * @return array
     */
    public function sendRideBookingConfirmation(object $user, array $bookingData): array
    {
        $smsMessage = "SkySlope: Your ride has been booked! Booking #{$bookingData['booking_number']}. " .
            "Pickup: {$bookingData['pickup_location']}. " .
            "Scheduled: {$bookingData['scheduled_at']}. " .
            "Fare: ₹{$bookingData['total_fare']}";

        $whatsappMessage = "🚗 *SkySlope Ride Booked!*\n\n" .
            "Booking ID: {$bookingData['booking_number']}\n" .
            "Pickup: {$bookingData['pickup_location']}\n" .
            "Drop-off: {$bookingData['dropoff_location']}\n" .
            "Scheduled: {$bookingData['scheduled_at']}\n" .
            "Fare: ₹{$bookingData['total_fare']}\n\n" .
            "Track your ride: " . url("/ride-booking/{$bookingData['id']}");

        return $this->notify($user, ['sms', 'email', 'whatsapp'], [
            'sms' => $smsMessage,
            'email' => $whatsappMessage,
            'subject' => "Ride Booking Confirmed - #{$bookingData['booking_number']}",
            'whatsapp' => $whatsappMessage,
        ]);
    }

    /**
     * Send ride status update notification
     *
     * @param object $user
     * @param array $bookingData
     * @param string $status
     * @param string|null $message
     * @return array
     */
    public function sendRideStatusUpdate(object $user, array $bookingData, string $status, ?string $message = null): array
    {
        $statusMessages = [
            'confirmed' => 'Your ride has been confirmed!',
            'driver_assigned' => 'A driver has been assigned to your ride.',
            'on_the_way' => 'Your driver is on the way!',
            'arrived' => 'Your driver has arrived at the pickup location.',
            'started' => 'Your ride has started. Have a safe journey!',
            'completed' => 'Your ride has been completed. Thank you for choosing SkySlope!',
            'cancelled' => 'Your ride has been cancelled.',
        ];

        $statusMessage = $message ?? ($statusMessages[$status] ?? "Your ride status: {$status}");

        $smsMessage = "SkySlope: {$statusMessage} Booking #{$bookingData['booking_number']}";

        return $this->notify($user, ['sms', 'whatsapp'], [
            'sms' => $smsMessage,
            'whatsapp' => "🚗 *SkySlope Update*\n\n{$statusMessage}\n\nBooking: #{$bookingData['booking_number']}",
        ]);
    }

    /**
     * Send driver assignment notification
     *
     * @param object $customer
     * @param object $driver
     * @param array $bookingData
     * @return array
     */
    public function sendDriverAssignmentNotification(object $customer, object $driver, array $bookingData): array
    {
        $smsMessage = "SkySlope: Driver assigned! {$driver->name} will pick you up. " .
            "Contact: {$driver->phone}. Booking #{$bookingData['booking_number']}";

        $whatsappMessage = "🚗 *Driver Assigned!*\n\n" .
            "Driver: {$driver->name}\n" .
            "Phone: {$driver->phone}\n" .
            "Vehicle: {$bookingData['vehicle_number']}\n\n" .
            "Booking: #{$bookingData['booking_number']}\n\n" .
            "Your driver will arrive shortly!";

        return $this->notify($customer, ['sms', 'whatsapp'], [
            'sms' => $smsMessage,
            'whatsapp' => $whatsappMessage,
        ]);
    }

    /**
     * Send wallet transaction notification
     *
     * @param object $user
     * @param array $transactionData
     * @return array
     */
    public function sendWalletNotification(object $user, array $transactionData): array
    {
        $type = $transactionData['type'] ?? 'transaction';
        $amount = $transactionData['amount'] ?? 0;
        $balance = $transactionData['balance'] ?? 0;

        $smsMessage = "SkySlope Wallet: {$type} of ₹{$amount}. New balance: ₹{$balance}";

        $whatsappMessage = "💰 *SkySlope Wallet*\n\n" .
            "Transaction: {$type}\n" .
            "Amount: ₹{$amount}\n" .
            "New Balance: ₹{$balance}\n\n" .
            "Thank you for using SkySlope!";

        return $this->notify($user, ['sms', 'whatsapp'], [
            'sms' => $smsMessage,
            'whatsapp' => $whatsappMessage,
        ]);
    }

    /**
     * Send OTP verification code
     *
     * @param string $phone
     * @param string $otp
     * @return bool
     */
    public function sendOtp(string $phone, string $otp): bool
    {
        $message = "SkySlope: Your verification code is {$otp}. Valid for 5 minutes. Do not share with anyone.";
        return $this->sendSms($phone, $message);
    }

    /**
     * Send promotional message
     *
     * @param object $user
     * @param string $message
     * @param array $channels
     * @return array
     */
    public function sendPromotional(object $user, string $message, array $channels = ['sms', 'whatsapp']): array
    {
        return $this->notify($user, $channels, [
            'sms' => $message,
            'whatsapp' => $message,
            'subject' => 'Special Offer from SkySlope!',
        ]);
    }
}
