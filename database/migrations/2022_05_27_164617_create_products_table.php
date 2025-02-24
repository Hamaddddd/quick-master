<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('region_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('country_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete();
            $table->string('keywords', 191)->nullable();
            $table->string('img')->nullable();
            $table->double('price')->nullable();
            $table->boolean('active')->default(0);
            $table->enum('status', Product::STATUSES)->default(Product::PENDING);
            $table->smallInteger('age_limit')->default(0);
            $table->boolean('visibility')->default(true);
            $table->string('status_note')->nullable();
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
        Schema::dropIfExists('products');
    }
}
