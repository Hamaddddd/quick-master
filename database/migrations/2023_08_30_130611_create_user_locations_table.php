<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('user_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('region_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('country_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('user_locations');
    }
}
