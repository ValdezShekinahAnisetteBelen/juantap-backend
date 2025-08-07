<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('username')->unique();
            $table->text('bio')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->foreignId('template_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('background_type', ['image', 'color'])->default('color');
            $table->string('background_value')->nullable();
            $table->string('font_style')->nullable();
            $table->string('font_size')->nullable();
            $table->string('button_style')->nullable();
            $table->string('accent_color')->nullable();
            $table->string('nfc_redirect_url')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
