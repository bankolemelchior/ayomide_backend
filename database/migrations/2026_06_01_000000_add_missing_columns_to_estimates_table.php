<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            if (!Schema::hasColumn('estimates', 'client_email')) {
                $table->string('client_email')->after('client_name');
            }

            if (!Schema::hasColumn('estimates', 'client_phone')) {
                $table->string('client_phone', 20)->nullable()->after('client_email');
            }

            if (!Schema::hasColumn('estimates', 'status')) {
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('estimate_date');
            }

            if (!Schema::hasColumn('estimates', 'total_amount')) {
                $table->decimal('total_amount', 10, 2)->default(0)->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropColumn(['client_email', 'client_phone', 'status', 'total_amount']);
        });
    }
};
