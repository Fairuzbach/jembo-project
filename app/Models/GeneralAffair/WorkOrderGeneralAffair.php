<?php

namespace App\Models\GeneralAffair;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\GeneralAffair\WorkOrderGaHistory;

class WorkOrderGeneralAffair extends Model
{
    use HasFactory;

    protected $table = 'work_order_general_affairs';

    // 1. UPDATE FILLABLE (Agar kolom baru bisa disimpan)
    protected $fillable = [
        'ticket_num',
        'requester_id',
        'requester_nik',
        'requester_name',
        'requester_department',
        'plant',
        'department',
        'category',
        'description',
        'parameter_permintaan',
        'status_permintaan',
        'rejection_reason',     // Alasan Reject
        'cancellation_note',    // Alasan Cancel
        'completion_note',      // Catatan Selesai
        'target_completion_date',
        'actual_start_date',
        'actual_completion_date',
        'status',
        'photo_path',
        'photo_completed_path',
        'processed_by',
        'processed_by_name',
        'processed_at',
        'completed_at',
        'rejected_at',
        'approved_spv_by',      // Tracking approval dari supervisor
        'approved_spv_at',      // Waktu supervisor approve
        'approved_ga_by',       // Tracking approval dari GA admin
        'approved_ga_at'        // Waktu GA admin approve
    ];

    // 2. TAMBAHKAN CASTS (Agar Tanggal terbaca sebagai Carbon Date di Email)
    protected $casts = [
        'actual_completion_date' => 'datetime',
        'target_completion_date' => 'date',
        'actual_start_date'      => 'datetime',
        'processed_at'           => 'datetime',
        'completed_at'           => 'datetime',
        'rejected_at'            => 'datetime',
        'approved_ga_at'         => 'datetime',
        'created_at'             => 'datetime',
        'updated_at'             => 'datetime',
    ];

    // --- RELATIONSHIPS ---

    public function user()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function processor()
    {
        return $this->belongsTo(\App\Models\User::class, 'processed_by');
    }

    public function histories()
    {
        return $this->hasMany(WorkOrderGaHistory::class, 'work_order_id')->latest();
    }

    public function plantInfo()
    {
        return $this->belongsTo(\App\Models\Engineering\Plant::class, 'plant', 'id');
    }
}
