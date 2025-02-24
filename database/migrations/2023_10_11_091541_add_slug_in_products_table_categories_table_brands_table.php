<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSlugInProductsTableCategoriesTableBrandsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('slug')->after('id')->nullable();
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->string('slug')->after('id')->nullable();
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
            $table->dropColumn('slug');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn('slug');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
}
