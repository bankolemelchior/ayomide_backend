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
            $table->string('client_email')->after('client_name');
            $table->string('client_phone', 20)->nullable()->after('client_email');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('estimate_date');
            $table->decimal('total_amount', 10, 2)->default(0)->after('status');
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
