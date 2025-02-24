<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVacanciesTypeInCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('work')->default(false);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->double('price_from')->nullable();
            $table->double('price_to')->nullable();
            $table->boolean('work')->default(false);
        });

        Schema::create('work_requests', function (Blueprint $table) {
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('resume')->nullable();
            $table->string('message')->nullable();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('user_resumes', function (Blueprint $table) {
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('resume')->nullable();
            $table->string('message')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
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
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('work');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('price_from');
            $table->dropColumn('price_to');
            $table->dropColumn('work');
        });

        Schema::dropIfExists('work_requests');
        Schema::dropIfExists('user_resumes');
    }
}
