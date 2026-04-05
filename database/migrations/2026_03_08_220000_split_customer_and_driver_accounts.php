<?php

use App\Models\Customer;
use App\Models\Driver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
            $table->string('email', 191)->unique();
            $table->string('phone', 191)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('drivers', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
            $table->string('email', 191)->unique();
            $table->string('phone', 191)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('license_number')->nullable();
            $table->string('vehicle_type')->nullable();
            $table->string('vehicle_number')->nullable();
            $table->string('status')->default('active');
            $table->rememberToken();
            $table->timestamps();
        });

        $customerRows = DB::table('users')->where('role', 'customer')->get();
        foreach ($customerRows as $row) {
            DB::table('customers')->insert([
                'id' => $row->id,
                'name' => $row->name,
                'email' => $row->email,
                'phone' => $row->phone,
                'email_verified_at' => $row->email_verified_at,
                'password' => $row->password,
                'remember_token' => $row->remember_token,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        $driverRows = DB::table('users')->where('role', 'driver')->get();
        foreach ($driverRows as $row) {
            DB::table('drivers')->insert([
                'id' => $row->id,
                'name' => $row->name,
                'email' => $row->email,
                'phone' => $row->phone,
                'email_verified_at' => $row->email_verified_at,
                'password' => $row->password,
                'remember_token' => $row->remember_token,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        $this->repointForeignKey('bookings', 'user_id', 'customers');
        $this->repointForeignKey('car_rentals', 'user_id', 'customers');
        $this->repointForeignKey('ride_bookings', 'user_id', 'customers');
        $this->repointForeignKey('ride_bookings', 'driver_id', 'drivers', true);
        $this->repointForeignKey('driver_availabilities', 'driver_id', 'drivers');
        $this->repointForeignKey('tour_drivers', 'user_id', 'drivers');
        $this->repointForeignKey('insurance_policies', 'user_id', 'customers');
        $this->repointForeignKey('extended_care', 'user_id', 'customers');
        $this->repointForeignKey('ride_booking_reviews', 'customer_id', 'customers');
        $this->repointForeignKey('ride_booking_reviews', 'driver_id', 'drivers', true);
        $this->repointForeignKey('ride_booking_tips', 'customer_id', 'customers');
        $this->repointForeignKey('ride_booking_tips', 'driver_id', 'drivers');

        Schema::table('wallets', function (Blueprint $table) {
            $table->nullableMorphs('owner');
        });

        foreach (DB::table('wallets')->select('id', 'user_id')->get() as $wallet) {
            $ownerClass = DB::table('drivers')->where('id', $wallet->user_id)->exists()
                ? Driver::class
                : Customer::class;

            DB::table('wallets')->where('id', $wallet->id)->update([
                'owner_type' => $ownerClass,
                'owner_id' => $wallet->user_id,
            ]);
        }

        Schema::table('wallets', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->nullableMorphs('owner');
        });

        foreach (DB::table('withdrawal_requests')->select('id', 'user_id')->get() as $withdrawal) {
            $ownerClass = DB::table('drivers')->where('id', $withdrawal->user_id)->exists()
                ? Driver::class
                : Customer::class;

            DB::table('withdrawal_requests')->where('id', $withdrawal->id)->update([
                'owner_type' => $ownerClass,
                'owner_id' => $withdrawal->user_id,
            ]);
        }

        Schema::table('withdrawal_requests', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropColumn('user_id');
            $table->index(['owner_type', 'owner_id', 'status'], 'withdrawal_requests_owner_status_index');
        });

        DB::table('users')->whereIn('role', ['customer', 'driver'])->delete();
    }

    public function down(): void
    {
        // Down migration intentionally omitted for this structural split.
    }

    private function repointForeignKey(string $tableName, string $column, string $targetTable, bool $nullable = false): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($column) {
            $table->dropForeign([$column]);
        });

        Schema::table($tableName, function (Blueprint $table) use ($column, $targetTable, $nullable) {
            $definition = $table->foreign($column)->references('id')->on($targetTable);
            $nullable ? $definition->nullOnDelete() : $definition->cascadeOnDelete();
        });
    }
};
