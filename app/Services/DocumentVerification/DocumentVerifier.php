<?php

namespace App\Services\DocumentVerification;

use App\Models\DocumentVerificationAttempt;
use App\Models\DriverDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DocumentVerifier
{
    public function catalog(): array
    {
        return collect(config('document_verification.types', []))->map(fn ($ids) => collect($ids)
            ->filter(fn ($id) => config("document_verification.providers.{$id}"))
            ->map(fn ($id) => ['value' => $id, 'label' => config("document_verification.providers.{$id}.label"),
                'simulated' => (bool) config("document_verification.providers.{$id}.simulated", false)])
            ->values()->all())->all();
    }

    private function fingerprint(DriverDocument $document): string
    {
        return hash('sha256', json_encode([$document->file_path, $document->document_number, $document->expires_at?->toDateString(), $document->type]));
    }

    public function verify(DriverDocument $document, string $provider, int $reviewer, ?string $outcome = null): DocumentVerificationAttempt
    {
        $definition = config("document_verification.providers.{$provider}");
        if (! $definition || ! in_array($provider, config("document_verification.types.{$document->type}", []), true)) {
            throw ValidationException::withMessages(['provider' => 'Choose a supported provider for this document type.']);
        }
        if ($outcome !== null && ! ($definition['simulated'] ?? false)) {
            throw ValidationException::withMessages(['outcome' => 'An outcome can only be selected for a simulation.']);
        }
        $adapter = app($definition['adapter']);
        if (! $adapter instanceof VerificationProvider) {
            throw ValidationException::withMessages(['provider' => 'The verification provider is not configured correctly.']);
        }
        $attempt = DB::transaction(function () use ($document, $provider, $reviewer, $definition) {
            $locked = DriverDocument::whereKey($document->id)->lockForUpdate()->firstOrFail();
            $document->setRawAttributes($locked->getAttributes(), true);
            if ($document->expires_at?->lt(today())) {
                throw ValidationException::withMessages(['document' => 'This document has expired. Upload a valid replacement.']);
            }
            if ($document->status === 'approved') {
                throw ValidationException::withMessages(['document' => 'This document is already approved.']);
            }
            if ($document->verificationAttempts()->where('status', 'processing')->where('created_at', '>', now()->subMinutes(5))->exists()) {
                throw ValidationException::withMessages(['document' => 'Verification is already running.']);
            }

            return $document->verificationAttempts()->create([
                'request_id' => (string) Str::uuid(), 'document_fingerprint' => $this->fingerprint($document),
                'provider' => $provider, 'simulated' => $definition['simulated'] ?? false, 'requested_by' => $reviewer,
            ]);
        });

        try {
            $result = $adapter->verify($document, $attempt->request_id, $outcome === null ? [] : ['outcome' => $outcome]);
        } catch (\Throwable $error) {
            report($error);
            $result = new VerificationResult('error', $attempt->request_id, 'Provider unavailable. Retry verification.');
        }

        return DB::transaction(function () use ($document, $attempt, $result, $reviewer) {
            $current = DriverDocument::whereKey($document->id)->lockForUpdate()->firstOrFail();
            $stale = $this->fingerprint($current) !== $attempt->document_fingerprint
                || (int) $current->verificationAttempts()->max('id') !== (int) $attempt->id
                || $current->expires_at?->lt(today())
                || $current->status !== $document->status;
            $attempt->update([
                'status' => $stale ? 'superseded' : $result->status, 'reference' => $result->reference,
                'message' => $stale ? 'Document changed. Run verification on the current upload.' : $result->message,
                'completed_at' => now(),
            ]);
            if (! $stale && in_array($result->status, ['approved', 'rejected'], true)) {
                $current->update([
                    'status' => $result->status, 'rejection_reason' => $result->status === 'rejected' ? $result->message : null,
                    'reviewed_by' => $reviewer, 'reviewed_at' => now(),
                ]);
            }

            return $attempt;
        });
    }
}
