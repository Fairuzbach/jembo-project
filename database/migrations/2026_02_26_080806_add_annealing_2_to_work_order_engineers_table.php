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
        Schema::table('eng_compound_checks', function (Blueprint $table) {
            // Menambah kolom tambahan untuk pengecekan annealing kedua
            $table->string('ann_konsentrasi_2')->nullable()->after('ann_konsentrasi');
            $table->string('ann_type_2')->nullable()->after('ann_type');
            $table->string('ann_warna_2')->nullable()->after('ann_warna');
            $table->string('ann_supplier_2')->nullable()->after('ann_supplier');
            $table->string('ann_ph_2')->nullable()->after('ann_ph');
            $table->string('ann_temp_2')->nullable()->after('ann_temp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('eng_compound_checks', function (Blueprint $table) {
            //
        });
    }
};
