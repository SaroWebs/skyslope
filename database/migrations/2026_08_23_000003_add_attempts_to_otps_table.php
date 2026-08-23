<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            // Per-code incorrect-verification counter for the brute-force lockout
            // (SKY-MRD-001 §12.1). Once it reaches the configured cap the code is
            // burned (is_used) and a fresh OTP must be requested.
            $table->unsignedTinyInteger('attempts')->default(0)->after('is_used');
        });
    }

    public function down(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            $table->dropColumn('attempts');
        });
    }
};
