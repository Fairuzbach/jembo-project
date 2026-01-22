<?php

namespace App\Services\GeneralAffair;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\GeneralAffair\WorkOrderGeneralAffair;
use Carbon\Carbon;

class DashboardService
{
    public function getDashboardData($request)
    {
        $user = auth()->user();

        $isOrangGa = $user->divisi === 'General Affair';
        $isAdminGa = $user->role === 'ga.admin';

        if (!$isOrangGa && !$isAdminGa) {
            abort(403, 'Unauthorized access to General Affair dashboard.');
        }
        // 1. Base Query
        $query = WorkOrderGeneralAffair::query()
            ->whereIn('status', ['in_progress', 'completed', 'approved']);

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereDate('created_at', '>=', $request->start_date)
                ->whereDate('created_at', '<=', $request->end_date);
        }

        // Ambil data (Eager Load)
        $allTickets = $query->with(['user', 'plantInfo'])->orderBy('created_at', 'desc')->get();

        // 2. Prepare Chart Data (Gantt Chart)
        $chartData = $this->prepareGanttChart($allTickets);

        // 3. Grouping Stats
        $groupedStats = $this->prepareGroupedStats($allTickets);

        // 4. Performance Stats
        $perfStats = $this->calculatePerformance($request->input('filter_month', date('Y-m')));

        // Hitung Delayed: Hanya tiket yang OVERDUE DAN status IN_PROGRESS
        // Logika: Jika overdue DAN in_progress → MASUKKAN ke overdue
        // Jika belum overdue TETAPI in_progress → TIDAK overdue
        $countDelayed = $allTickets
            ->filter(function ($ticket) {
                // Hanya hitung jika status in_progress
                if ($ticket->status !== 'in_progress') {
                    return false;
                }
                // Pastikan ada target_completion_date
                if (!$ticket->target_completion_date) {
                    return false;
                }
                // Cek apakah target_completion_date sudah lewat (overdue)
                return Carbon::parse($ticket->target_completion_date)->isPast();
            })
            ->count();

        // Hitung Rejected (query terpisah karena tidak termasuk di base query)
        $countRejected = WorkOrderGeneralAffair::query()
            ->whereIn('status', ['rejected'])
            ->when($request->filled('start_date') && $request->filled('end_date'), function ($q) use ($request) {
                $q->whereDate('created_at', '>=', $request->start_date)
                    ->whereDate('created_at', '<=', $request->end_date);
            })
            ->count();

        return array_merge([
            'workOrders'      => $allTickets,
            'countTotal'      => $allTickets->count(),
            'countInProgress' => $allTickets->where('status', 'in_progress')->count(),
            'countCompleted'  => $allTickets->where('status', 'completed')->count(),
            'countRejected'   => $countRejected,
            'countDelayed'    => $countDelayed,
            // Pending query terpisah karena statusnya beda dengan Base Query
            'countPending'    => WorkOrderGeneralAffair::whereIn('status', ['pending'])->count(),
            'countWaitingApprovalSpv' => WorkOrderGeneralAffair::where('status', 'waiting_approval_spv')->count(),
            'countWaitingApprovalGA'  => WorkOrderGeneralAffair::where('status', 'waiting_approval_ga')->count(),
            'filterMonth'     => $request->input('filter_month', date('Y-m')),
        ], $chartData, $groupedStats, $perfStats);
    }

    private function prepareGanttChart($tickets)
    {
        $data = [];
        $links = [];

        // REVISI: Jangan membuang tiket yang target date-nya NULL.
        // Sebaiknya tetap ditampilkan dengan durasi default agar user sadar ada tiket tersebut.
        $groupedByDivision = $tickets->groupBy(function ($ticket) {
            return $ticket->department ?? $ticket->user->divisi ?? 'General';
        });

        foreach ($groupedByDivision as $divisionName => $divisionTickets) {
            // Create safe ID
            $divisionId = 'div_' . preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($divisionName));

            // Parent (Division)
            $data[] = [
                'id' => $divisionId,
                'text' => $divisionName,
                'type' => 'project',
                'open' => true,
            ];

            foreach ($divisionTickets as $ticket) {
                // Tentukan Warna & Progress
                $color = '#3db9d3'; // Default: Biru
                $progress = 0;

                if ($ticket->status === 'completed') {
                    $color = '#28a745'; // Hijau
                    $progress = 1;
                } elseif ($ticket->status === 'in_progress') {
                    $color = '#ffc107'; // Kuning
                    $progress = 0.4;
                } elseif ($ticket->status === 'approved') {
                    $color = '#17a2b8'; // Cyan
                    $progress = 0.1;
                }

                // --- LOGIKA TANGGAL AMAN (FALLBACK) ---
                // Start: Actual Start -> Created At -> Now
                $start = $ticket->actual_start_date
                    ? Carbon::parse($ticket->actual_start_date)
                    : Carbon::parse($ticket->created_at);

                // End: Actual End -> Target -> Start + 1 Hari
                if ($ticket->actual_completion_date) {
                    $end = Carbon::parse($ticket->actual_completion_date);
                } elseif ($ticket->target_completion_date) {
                    $end = Carbon::parse($ticket->target_completion_date);
                } else {
                    $end = $start->copy()->addDays(1); // Default 1 hari jika target null
                }

                // Pastikan durasi minimal 1 hari (dhtmlx tidak suka durasi 0 atau negatif)
                $duration = $start->diffInDays($end);
                if ($duration <= 0) $duration = 1;

                // Child (Ticket)
                $data[] = [
                    'id' => $ticket->id,
                    'text' => $ticket->ticket_num . ' - ' . Str::limit($ticket->description, 30),
                    'start_date' => $start->format('Y-m-d'),
                    'duration' => (int) $duration,
                    'progress' => $progress,
                    'parent' => $divisionId,
                    'color' => $color,
                    // Metadata untuk Tooltip
                    'owner' => $ticket->user->name ?? 'N/A',
                    'division' => $divisionName,
                    'ticket_num' => $ticket->ticket_num,
                    'status' => $ticket->status,
                ];
            }
        }

        // Handle Empty Data
        if (empty($data)) {
            $data[] = [
                'id' => 'div_empty',
                'text' => 'Tidak ada data untuk ditampilkan',
                'type' => 'project',
                'open' => true,
            ];
        }

        return [
            'tasks' => [
                'data' => $data,
                'links' => $links
            ]
        ];
    }

    // METHOD 'formatGanttData' DIHAPUS KARENA TIDAK DIGUNAKAN (DEAD CODE)

    private function prepareGroupedStats($tickets)
    {
        $formatForTable = function ($grouped) {
            return $grouped->map(fn($list, $key) => (object)['label' => $key, 'total' => $list->count()])
                ->sortByDesc('total')->values();
        };

        // 1. Chart Phase
        $deptGroup = $tickets->groupBy(fn($i) => $i->department ?? 'Unassigned');
        $chartDataPhase = [
            'labels' => $deptGroup->keys()->toArray(),
            'data'   => $deptGroup->map->count()->values()->toArray(),
            'colors' => array_fill(0, $deptGroup->count(), '#eab308')
        ];

        // 2. Lokasi
        $locGroup = $tickets->groupBy(fn($i) => $i->plantInfo->name ?? 'Unknown');
        $locData = $formatForTable($locGroup);

        // 3. Dept Table
        $deptData = $formatForTable($deptGroup);

        // 4. Parameter
        $paramGroup = $tickets->groupBy(fn($i) => $i->parameter_permintaan ?? 'Lainnya');
        $paramData = $formatForTable($paramGroup);

        // 5. Bobot
        $catGroup = $tickets->groupBy('category')->map->count();
        $chartBobotValues = [
            $catGroup['HIGH'] ?? $catGroup['BERAT'] ?? 0,
            $catGroup['MEDIUM'] ?? $catGroup['SEDANG'] ?? 0,
            $catGroup['LOW'] ?? $catGroup['RINGAN'] ?? 0
        ];

        return [
            'chartDataPhase'   => $chartDataPhase,
            'locData'          => $locData,
            'deptData'         => $deptData,
            'paramData'        => $paramData,
            'chartLocLabels'   => $locData->pluck('label')->toArray(),
            'chartLocValues'   => $locData->pluck('total')->toArray(),
            'chartDeptLabels'  => $deptData->pluck('label')->toArray(),
            'chartDeptValues'  => $deptData->pluck('total')->toArray(),
            'chartParamLabels' => $paramData->pluck('label')->toArray(),
            'chartParamValues' => $paramData->pluck('total')->toArray(),
            'chartBobotLabels' => ['Berat (High)', 'Sedang (Medium)', 'Ringan (Low)'],
            'chartBobotValues' => $chartBobotValues,
        ];
    }

    private function calculatePerformance($filterMonth)
    {
        $year  = substr($filterMonth, 0, 4);
        $month = substr($filterMonth, 5, 2);

        // Query terpisah diperlukan di sini karena filternya beda (Target Date vs Created Date)
        $query = WorkOrderGeneralAffair::whereIn('status', ['in_progress', 'completed', 'approved'])
            ->where(function ($q) use ($year, $month) {
                $q->whereYear('target_completion_date', $year)
                    ->whereMonth('target_completion_date', $month)
                    ->orWhere(function ($sub) use ($year, $month) {
                        $sub->whereNull('target_completion_date')
                            ->whereYear('created_at', $year)
                            ->whereMonth('created_at', $month);
                    });
            });

        $total = $query->count();
        $completed = (clone $query)->where('status', 'completed')->count();

        return [
            'perfTotal'      => $total,
            'perfCompleted'  => $completed,
            'perfPercentage' => $total > 0 ? round(($completed / $total) * 100) : 0,
        ];
    }
}
