<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('lastname');
            $table->string('location')->nullable()->after('website'); // assuming 'website' exists
        });
    }

    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['firstname', 'lastname', 'display_name', 'location']);
        });
    }
};
