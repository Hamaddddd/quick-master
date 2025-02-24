<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnInStoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->string('color', 10)->nullable();
        });

        Schema::create('story_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('title');
            $table->unique(['story_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropColumn('color');
        });

        Schema::dropIfExists('story_translations');
    }
}
