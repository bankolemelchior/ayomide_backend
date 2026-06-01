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
                $table->string('client_email')->nullable()->after('client_name');
            }

            if (!Schema::hasColumn('estimates', 'client_phone')) {
                $table->string('client_phone')->nullable()->after('client_email');
            }

            if (!Schema::hasColumn('estimates', 'status')) {
                $table->string('status')->default('pending')->after('estimate_date');
            }

            if (!Schema::hasColumn('estimates', 'total_amount')) {
                $table->decimal('total_amount', 12, 2)->default(0)->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            if (Schema::hasColumn('estimates', 'total_amount')) {
                $table->dropColumn('total_amount');
            }

            if (Schema::hasColumn('estimates', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('estimates', 'client_phone')) {
                $table->dropColumn('client_phone');
            }

            if (Schema::hasColumn('estimates', 'client_email')) {
                $table->dropColumn('client_email');
            }
        });
    }
};
