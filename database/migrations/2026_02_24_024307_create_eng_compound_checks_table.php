<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('eng_compound_checks', function (Blueprint $table) {
            $table->id();

            // Relasi ke tabel plants, machines, dan users
            $table->foreignId('plant_id')->constrained('plants')->onDelete('cascade');
            $table->foreignId('machine_id')->constrained('machines')->onDelete('cascade'); // Relasi ke tabel mesin
            $table->date('tanggal_cek');

            // Kolom Pengecekan Drawing
            $table->string('draw_type')->nullable();
            $table->string('draw_supplier')->nullable();
            $table->string('draw_warna')->nullable();
            $table->string('draw_konsentrasi')->nullable();
            $table->string('draw_ph')->nullable();
            $table->string('draw_temp')->nullable();

            // Kolom Pengecekan Annealing
            $table->string('ann_type')->nullable();
            $table->string('ann_supplier')->nullable();
            $table->string('ann_warna')->nullable();
            $table->string('ann_konsentrasi')->nullable();
            $table->string('ann_ph')->nullable();
            $table->string('ann_temp')->nullable();

            // Info Tambahan & Approval
            $table->text('keterangan')->nullable();
            $table->foreignId('diperiksa_oleh')->constrained('users')->onDelete('cascade');
            $table->foreignId('diketahui_oleh')->nullable()->constrained('users')->onDelete('set null');
            $table->string('status')->default('waiting_approval');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('eng_compound_checks');
    }
};
