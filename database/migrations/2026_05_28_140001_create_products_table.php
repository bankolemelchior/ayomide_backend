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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('price_m2');
            $table->string('dimension');
            $table->string('type'); // sol, mur
            $table->string('finition');
            $table->string('thickness')->nullable();
            $table->text('usage')->nullable();
            $table->string('epaisseur')->nullable();
            $table->json('images'); // Tableau JSON d'images
            $table->boolean('popular')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
