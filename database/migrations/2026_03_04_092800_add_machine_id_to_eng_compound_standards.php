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
        Schema::table('eng_compound_standards', function (Blueprint $table) {
            $table->foreignId('machine_id')->nullable()->constrained('machines')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eng_compound_standards', function (Blueprint $table) {
            //
        });
    }
};
