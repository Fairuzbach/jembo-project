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
            // Add missing columns from model
            if (!Schema::hasColumn('work_order_general_affairs', 'requester_nik')) {
                $table->string('requester_nik')->nullable()->after('requester_id');
            }
            if (!Schema::hasColumn('work_order_general_affairs', 'requester_department')) {
                $table->string('requester_department')->nullable()->after('requester_name');
            }
            if (!Schema::hasColumn('work_order_general_affairs', 'parameter_permintaan')) {
                $table->string('parameter_permintaan')->nullable()->after('category');
            }
            if (!Schema::hasColumn('work_order_general_affairs', 'status_permintaan')) {
                $table->string('status_permintaan')->nullable()->after('parameter_permintaan');
            }
            if (!Schema::hasColumn('work_order_general_affairs', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('status_permintaan');
            }
            if (!Schema::hasColumn('work_order_general_affairs', 'cancellation_note')) {
                $table->text('cancellation_note')->nullable()->after('rejection_reason');
            }
            if (!Schema::hasColumn('work_order_general_affairs', 'completion_note')) {
                $table->text('completion_note')->nullable()->after('cancellation_note');
            }
            if (!Schema::hasColumn('work_order_general_affairs', 'actual_start_date')) {
                $table->dateTime('actual_start_date')->nullable()->after('target_completion_date');
            }
            if (!Schema::hasColumn('work_order_general_affairs', 'photo_completed_path')) {
                $table->string('photo_completed_path')->nullable()->after('photo_path');
            }
            if (!Schema::hasColumn('work_order_general_affairs', 'processed_by')) {
                $table->unsignedBigInteger('processed_by')->nullable()->after('photo_completed_path');
            }
            if (!Schema::hasColumn('work_order_general_affairs', 'processed_by_name')) {
                $table->string('processed_by_name')->nullable()->after('processed_by');
            }
            if (!Schema::hasColumn('work_order_general_affairs', 'processed_at')) {
                $table->dateTime('processed_at')->nullable()->after('processed_by_name');
            }
            if (!Schema::hasColumn('work_order_general_affairs', 'completed_at')) {
                $table->dateTime('completed_at')->nullable()->after('processed_at');
            }
            if (!Schema::hasColumn('work_order_general_affairs', 'rejected_at')) {
                $table->dateTime('rejected_at')->nullable()->after('completed_at');
            }
            if (!Schema::hasColumn('work_order_general_affairs', 'approved_ga_by')) {
                $table->unsignedBigInteger('approved_ga_by')->nullable()->after('rejected_at');
            }
            if (!Schema::hasColumn('work_order_general_affairs', 'approved_ga_at')) {
                $table->dateTime('approved_ga_at')->nullable()->after('approved_ga_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_order_general_affairs', function (Blueprint $table) {
            $columnsToRemove = [
                'requester_nik',
                'requester_department',
                'parameter_permintaan',
                'status_permintaan',
                'rejection_reason',
                'cancellation_note',
                'completion_note',
                'actual_start_date',
                'photo_completed_path',
                'processed_by',
                'processed_by_name',
                'processed_at',
                'completed_at',
                'rejected_at',
                'approved_ga_by',
                'approved_ga_at'
            ];

            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('work_order_general_affairs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
