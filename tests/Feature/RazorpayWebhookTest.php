<?php

it('accepts razorpay webhooks with a valid signature', function () {
    config(['services.razorpay.webhook_secret' => 'webhook-secret']);

    $payload = json_encode([
        'event' => 'payment.captured',
        'payload' => [
            'payment' => [
                'entity' => [
                    'id' => 'pay_webhook_123',
                    'status' => 'captured',
                ],
            ],
        ],
    ]);
    $signature = hash_hmac('sha256', $payload, 'webhook-secret');

    $this->call('POST', '/api/razorpay/webhook', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_RAZORPAY_SIGNATURE' => $signature,
    ], $payload)
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('event', 'payment.captured');
});

it('rejects razorpay webhooks with an invalid signature', function () {
    config(['services.razorpay.webhook_secret' => 'webhook-secret']);

    $payload = json_encode(['event' => 'payment.captured']);

    $this->call('POST', '/api/razorpay/webhook', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_RAZORPAY_SIGNATURE' => 'bad-signature',
    ], $payload)
        ->assertStatus(400)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Invalid webhook signature');
});
