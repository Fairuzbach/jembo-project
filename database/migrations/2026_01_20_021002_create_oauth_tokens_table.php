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
        Schema::create('oauth_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('microsoft');
            $table->text('access_token');
            $table->text('refresh_token')->nullable(); // PENTING: Ini agar login awet
            $table->string('expires_in')->nullable();
            $table->timestamp('updated_at')->useCurrent(); // Kita pakai ini untuk cek expired
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_tokens');
    }
};
