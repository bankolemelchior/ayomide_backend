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
        Schema::table('products', function (Blueprint $table) {
            $table->integer('promo_price_m2')->nullable()->after('price_m2');
            $table->string('promo_label')->nullable()->after('promo_price_m2'); // ex: "Promo -20%", "Déstockage"
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['promo_price_m2', 'promo_label']);
        });
    }
};
