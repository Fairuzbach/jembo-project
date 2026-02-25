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
            // 1. Hapus Foreign Key Constraint-nya dulu
            // Laravel biasanya memberi nama: nama_tabel_nama_kolom_foreign
            $table->dropForeign('eng_compound_checks_diketahui_oleh_foreign');

            // Jika kolom 'diperiksa_oleh' juga masih bermasalah, hapus juga:
            // $table->dropForeign('eng_compound_checks_diperiksa_oleh_foreign');
        });

        // 2. Sekarang ubah tipe datanya menjadi String
        Schema::table('eng_compound_checks', function (Blueprint $table) {
            $table->string('diperiksa_oleh', 255)->nullable()->change();
            $table->string('diketahui_oleh', 255)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('eng_compound_checks', function (Blueprint $table) {
            // Jika rollback, kembalikan ke BigInteger (namun data string akan hilang/error)
            $table->unsignedBigInteger('diperiksa_oleh')->nullable()->change();
            $table->unsignedBigInteger('diketahui_oleh')->nullable()->change();
        });
    }
};
