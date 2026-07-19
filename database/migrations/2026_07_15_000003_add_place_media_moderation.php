<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('place_media', function (Blueprint $table) {
            $table->string('type', 20)->default('image')->change();
            $table->foreignId('uploaded_by_customer_id')->nullable()->after('place_id')->constrained('customers')->nullOnDelete();
            $table->string('source', 20)->default('admin')->after('type');
            $table->string('approval_status', 20)->default('approved')->after('source')->index();
            $table->foreignId('reviewed_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('rejection_reason')->nullable()->after('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('place_media', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by_customer_id']);
            $table->dropForeign(['reviewed_by']);
            $table->dropIndex(['approval_status']);
            $table->dropColumn(['uploaded_by_customer_id', 'source', 'approval_status', 'reviewed_by', 'reviewed_at', 'rejection_reason']);
            $table->enum('type', ['image', 'video'])->default('image')->change();
        });
    }
};
