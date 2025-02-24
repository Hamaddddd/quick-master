<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttributesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->foreignId('category_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('attribute_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('title');
            $table->string('placeholder');
            $table->string('placeholder_to')->nullable();
            $table->unique(['attribute_id', 'locale']);
        });

        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
        });

        Schema::create('attribute_value_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_value_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('title');
            $table->unique(['attribute_value_id', 'locale']);
        });

        Schema::create('attribute_value_products', function (Blueprint $table) {
            $table->string('value')->nullable();
            $table->foreignId('product_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->nullable()->constrained()->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_translations');
        Schema::dropIfExists('attribute_value_translations');
        Schema::dropIfExists('attribute_value_products');
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attributes');
    }
}
