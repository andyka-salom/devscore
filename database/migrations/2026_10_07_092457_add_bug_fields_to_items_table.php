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
        Schema::table('items', function (Blueprint $table) {
            $table->string('menu')->nullable()->after('description');
            $table->string('category')->nullable()->after('menu');
            $table->boolean('is_production')->default(false)->after('category');
            $table->string('screenshot_path')->nullable()->after('is_production');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['menu', 'category', 'is_production', 'screenshot_path']);
        });
    }
};
