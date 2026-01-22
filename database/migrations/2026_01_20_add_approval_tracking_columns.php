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
        Schema::table('work_order_general_affairs', function (Blueprint $table) {
            // Tracking approval dari supervisor/manager
            if (!Schema::hasColumn('work_order_general_affairs', 'approved_spv_by')) {
                $table->unsignedBigInteger('approved_spv_by')->nullable()->after('approved_ga_at')->comment('ID supervisor yang approve');
            }
            if (!Schema::hasColumn('work_order_general_affairs', 'approved_spv_at')) {
                $table->dateTime('approved_spv_at')->nullable()->after('approved_spv_by')->comment('Waktu supervisor approve');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_order_general_affairs', function (Blueprint $table) {
            $table->dropColumn(['approved_spv_by', 'approved_spv_at']);
        });
    }
};
