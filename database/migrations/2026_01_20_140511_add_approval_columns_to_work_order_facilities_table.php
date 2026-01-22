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
        Schema::table('work_order_facilities', function (Blueprint $table) {
            // Status enum perlu kita perlebar logikanya nanti di kodingan, 
            // tapi kolom string 'status' yang ada sudah cukup.

            // Approval SPV
            $table->timestamp('approved_at_spv')->nullable();
            $table->unsignedBigInteger('approved_by_spv')->nullable(); // ID User SPV

            // Approval Manager
            $table->timestamp('approved_at_manager')->nullable();
            $table->unsignedBigInteger('approved_by_manager')->nullable(); // ID User Manager

            // Approval Facility (Head/SPV Facility)
            $table->timestamp('approved_at_facility')->nullable();
            $table->unsignedBigInteger('approved_by_facility')->nullable();

            // Alasan Reject (Jika ditolak di tengah jalan)
            $table->string('rejection_reason')->nullable();
        });
    }

    public function down()
    {
        Schema::table('work_order_facilities', function (Blueprint $table) {
            $table->dropColumn([
                'approved_at_spv',
                'approved_by_spv',
                'approved_at_manager',
                'approved_by_manager',
                'approved_at_facility',
                'approved_by_facility',
                'rejection_reason'
            ]);
        });
    }
};
