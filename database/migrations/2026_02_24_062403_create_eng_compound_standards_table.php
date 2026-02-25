<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('eng_compound_standards', function (Blueprint $table) {
            $table->id();
            $table->string('plant'); // Contoh: 'Plant A' atau 'Autowire'
            $table->string('kode_mesin'); // Contoh: 'bak_1', 'bak_2', 'cek_1' (agar gampang dipanggil di Blade)
            $table->string('nama_mesin'); // Contoh: 'BAK 1 (HD 10 C)'
            $table->string('proses'); // Contoh: 'drawing' atau 'annealing'

            // Kolom-kolom Nilai Standar
            $table->string('std_tipe')->nullable();
            $table->string('std_supplier')->nullable();
            $table->string('std_warna')->nullable();
            $table->string('std_konsentrasi')->nullable();
            $table->string('std_ph')->nullable();
            $table->string('std_temp')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('eng_compound_standards');
    }
};
