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
        Schema::table('templates', function (Blueprint $table) {
            // Add only if not existing
            if (!Schema::hasColumn('templates', 'preview_url')) {
                $table->string('preview_url')->nullable()->after('description');
            }

            if (!Schema::hasColumn('templates', 'thumbnail_url')) {
                $table->string('thumbnail_url')->nullable()->after('preview_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            if (Schema::hasColumn('templates', 'preview_url')) {
                $table->dropColumn('preview_url');
            }

            if (Schema::hasColumn('templates', 'thumbnail_url')) {
                $table->dropColumn('thumbnail_url');
            }
        });
    }
};
