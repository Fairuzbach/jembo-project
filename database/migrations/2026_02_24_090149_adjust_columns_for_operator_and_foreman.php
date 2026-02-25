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
            // 1. Hapus Foreign Key-nya dulu (sesuaikan nama constraint-nya)
            $table->dropForeign(['diperiksa_oleh']);

            // 2. Sekarang baru bisa ubah tipe datanya menjadi String
            $table->string('diperiksa_oleh')->nullable()->change();

            // 3. Tambahkan kolom diketahui_oleh (jika belum ada)
            if (!Schema::hasColumn('eng_compound_checks', 'diketahui_oleh')) {
                $table->string('diketahui_oleh')->nullable()->after('diperiksa_oleh');
            }
        });
    }

    public function down()
    {
        Schema::table('eng_compound_checks', function (Blueprint $table) {
            // Jika ingin rollback, kembalikan ke BigInteger dan pasang Foreign Key lagi
            $table->unsignedBigInteger('diperiksa_oleh')->change();
            $table->foreign('diperiksa_oleh')->references('id')->on('users');
            $table->dropColumn('diketahui_oleh');
        });
    }
};
