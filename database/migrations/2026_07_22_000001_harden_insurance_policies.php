<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurance_policies', function (Blueprint $table) {
            $table->string('product_code')->nullable()->after('policy_type');
            $table->string('provider_name')->nullable()->after('product_code');
            $table->string('terms_version')->nullable()->after('terms');
            $table->timestamp('terms_accepted_at')->nullable()->after('terms_version');
            $table->timestamp('issued_at')->nullable()->after('terms_accepted_at');
            $table->timestamp('cancelled_at')->nullable()->after('issued_at');
            $table->unique(['coverable_type', 'coverable_id'], 'insurance_coverable_unique');
        });

        Schema::table('insurance_claims', function (Blueprint $table) {
            $table->date('incident_date')->nullable()->after('claim_number');
            $table->json('documents')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('insurance_claims', function (Blueprint $table) {
            $table->dropColumn(['incident_date', 'documents']);
        });

        Schema::table('insurance_policies', function (Blueprint $table) {
            $table->dropUnique('insurance_coverable_unique');
            $table->dropColumn(['product_code', 'provider_name', 'terms_version', 'terms_accepted_at', 'issued_at', 'cancelled_at']);
        });
    }
};
