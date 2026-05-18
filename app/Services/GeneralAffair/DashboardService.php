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
        $isOrangGa = strtolower($user->divisi) === 'general affair';
        $isAdminGa = in_array($user->role, ['ga.admin', 'super.ga.admin', 'super.admin']);
        if (!$isOrangGa && !$isAdminGa) {
            abort(403, 'Unauthorized access to General Affair dashboard.');
        }

        $backlogTickets = WorkOrderGeneralAffair::whereIn('status', [
            'pending',
            'approved',
            'in_progress',
            'waiting_approval_ga',
            'waiting_approval'
        ])->get();
        $realPending = $backlogTickets->whereIn('status', ['pending', 'approved'])->count();
        $realWaitingGA = $backlogTickets->where('status', 'waiting_approval_ga')->count();
        $realInProgress = $backlogTickets->where('status', 'in_progress')->count();
        $countDelayed = $backlogTickets->filter(function ($ticket) {
            return $ticket->status === 'in_progress'
                && $ticket->target_completion_date
                && \Carbon\Carbon::parse($ticket->target_completion_date)->isPast();
        })->count();
        $reportQuery = WorkOrderGeneralAffair::query();
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $reportQuery->whereDate('created_at', '>=', $request->start_date)
                ->whereDate('created_at', '<=', $request->end_date);
        }
        $reportTickets  = $reportQuery->with(['user', 'plantInfo'])->orderBy('created_at', 'desc')->get();
        $countTotal     = $reportTickets->count();
        $countCompleted = $reportTickets->where('status', 'completed')->count();
        $countRejected  = $reportTickets->where('status', 'rejected')->count();
        $countCancelled = $reportTickets->where('status', 'cancelled')->count();
        $displayPending = $realPending;
        $chartData    = $this->prepareGanttChart($reportTickets);
        $groupedStats = $this->prepareGroupedStats($reportTickets);
        $perfStats    = $this->calculatePerformance($request->input('filter_month', date('Y-m')));

        return array_merge([
            'workOrders'            => $reportTickets,
            'countPending'          => $displayPending,
            'countInProgress'       => $realInProgress,
            'countDelayed'          => $countDelayed,
            'countWaitingApprovalGA' => $realWaitingGA,
            'countTotal'            => $countTotal,
            'countCompleted'        => $countCompleted,
            'countRejected'         => $countRejected,
            'countCancelled'        => $countCancelled,
            'filterMonth'           => $request->input('filter_month', date('Y-m')),
            'tasks'                 => $chartData,
        ], $groupedStats, $perfStats);
    }

    public function prepareGanttChart($tickets)
    {
        $data = [];
        $links = [];
        $groupedByDivision = $tickets->groupBy(function ($ticket) {
            return $ticket->department ?? $ticket->user->divisi ?? 'General';
        });

        foreach ($groupedByDivision as $divisionName => $divisionTickets) {
            $divisionId = 'div_' . preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($divisionName));

            $firstTicket = $divisionTickets->sortBy(function ($t) {
                return $t->actual_start_date ?? $t->created_at;
            })->first();
            $projectStartDate = $firstTicket
                ? Carbon::parse($firstTicket->actual_start_date ?? $firstTicket->created_at)
                : Carbon::now();

            $data[] = [
                'id' => $divisionId,
                'text' => $divisionName,
                'type' => 'project',
                'open' => true,
                'start_date' => $projectStartDate->toDateString(),
            ];

            foreach ($divisionTickets as $ticket) {
                $color = '#3db9d3';
                $progress = 0;

                if ($ticket->status === 'completed') {
                    $color = '#28a745';
                    $progress = 1;
                } elseif ($ticket->status === 'in_progress') {
                    $color = '#ffc107';
                    $progress = 0.4;
                } elseif ($ticket->status === 'approved') {
                    $color = '#17a2b8';
                    $progress = 0.1;
                }


                $start = $ticket->actual_start_date
                    ? Carbon::parse($ticket->actual_start_date)
                    : Carbon::parse($ticket->created_at);


                if ($ticket->actual_completion_date) {
                    $end = Carbon::parse($ticket->actual_completion_date);
                } elseif ($ticket->target_completion_date) {
                    $end = Carbon::parse($ticket->target_completion_date);
                } else {
                    $end = $start->copy()->addDays(1);
                }


                $duration = $start->diffInDays($end);
                if ($duration <= 0) $duration = 1;


                $data[] = [
                    'id' => $ticket->id,
                    'text' => $ticket->ticket_num . ' - ' . Str::limit($ticket->description, 30),
                    'start_date' => $start->format('Y-m-d'),
                    'duration' => (int) $duration,
                    'progress' => $progress,
                    'parent' => $divisionId,
                    'color' => $color,
                    'type' => 'task',

                    'owner' => $ticket->user->name ?? 'N/A',
                    'division' => $divisionName,
                    'ticket_num' => $ticket->ticket_num,
                    'status' => $ticket->status,
                ];
            }
        }


        if (empty($data)) {
            $data[] = [
                'id' => 'div_empty',
                'text' => 'Tidak ada data untuk ditampilkan',
                'type' => 'project',
                'open' => true,
                'start_date' => Carbon::now()->format('Y-m-d'),
            ];
        }

        return [
            'data' => $data,
            'links' => $links
        ];
    }

    private function prepareGroupedStats($tickets)
    {
        // ---------------------------------------------------------
        // HELPER: Hitung Persentase Detail
        // ---------------------------------------------------------
        $calculatePerformanceDetail = function ($groupedList) {
            $stats = [];
            foreach ($groupedList as $key => $list) {
                $totalValid = $list->whereNotIn('status', ['cancelled', 'rejected'])->count();
                $completed  = $list->where('status', 'completed')->count();
                $pending    = $list->whereIn('status', ['pending', 'approved', 'waiting_approval_ga', 'waiting_approval'])->count();
                $inProgress = $list->where('status', 'in_progress')->count();

                $percentage = $totalValid > 0 ? round(($completed / $totalValid) * 100) : 0;

                $stats[] = [
                    'label'      => $key,
                    'total'      => $list->count(),
                    'totalValid' => $totalValid,
                    'completed'  => $completed,
                    'uncompleted' => $pending + $inProgress, // Gabungan yang belum selesai
                    'percentage' => $percentage
                ];
            }
            return collect($stats)->sortByDesc('total')->values();
        };

        // 1. STATISTIK KESELURUHAN (Pengganti Lokasi)
        $totalValidOverall = $tickets->whereNotIn('status', ['cancelled', 'rejected'])->count();
        $completedOverall  = $tickets->where('status', 'completed')->count();
        $inProgressOverall = $tickets->where('status', 'in_progress')->count();

        // KITA PISAHKAN PENDING DAN WAITING APPROVAL DI SINI:
        $pendingOverall    = $tickets->whereIn('status', ['pending', 'approved'])->count();
        $waitingOverall    = $tickets->whereIn('status', ['waiting_approval', 'waiting_approval_ga'])->count();

        $overallStats = [
            'totalValid' => $totalValidOverall,
            'completed'  => $completedOverall,
            'inProgress' => $inProgressOverall,
            'pending'    => $pendingOverall,
            'waiting'    => $waitingOverall, // <-- Tambahan Baru

            'completedPct'  => $totalValidOverall > 0 ? round(($completedOverall / $totalValidOverall) * 100) : 0,
            'inProgressPct' => $totalValidOverall > 0 ? round(($inProgressOverall / $totalValidOverall) * 100) : 0,
            'pendingPct'    => $totalValidOverall > 0 ? round(($pendingOverall / $totalValidOverall) * 100) : 0,
            'waitingPct'    => $totalValidOverall > 0 ? round(($waitingOverall / $totalValidOverall) * 100) : 0, // <-- Tambahan Baru
        ];

        // 2. STATISTIK DEPARTMENT
        $deptGroup = $tickets->groupBy(fn($i) => $i->department ?? 'Unassigned');
        $deptData  = $calculatePerformanceDetail($deptGroup);

        // 3. STATISTIK PARAMETER
        $paramGroup = $tickets->groupBy(fn($i) => $i->parameter_permintaan ?? 'Lainnya');
        $paramData  = $calculatePerformanceDetail($paramGroup);

        // 4. STATISTIK BOBOT
        $catGroup = $tickets->groupBy(fn($item) => strtoupper($item->category))->map->count();

        return [
            // Kirim Status Keseluruhan (Ganti Lokasi)
            'overallStats'     => $overallStats,

            'chartDeptLabels'  => $deptData->pluck('label')->toArray(),
            'chartDeptValues'  => $deptData->pluck('total')->toArray(),

            // Kirim Data Parameter Lengkap
            'chartParamLabels' => $paramData->pluck('label')->toArray(),
            'chartParamRaw'    => $paramData->toArray(), // Penting untuk JS!

            'chartBobotLabels' => ['Berat (High)', 'Sedang (Medium)', 'Ringan (Low)'],
            'chartBobotValues' => [
                $catGroup['HIGH'] ?? $catGroup['BERAT'] ?? 0,
                $catGroup['MEDIUM'] ?? $catGroup['SEDANG'] ?? 0,
                $catGroup['LOW'] ?? $catGroup['RINGAN'] ?? 0
            ],
        ];
    }

    private function calculatePerformance($filterMonth)
    {
        $year  = substr($filterMonth, 0, 4);
        $month = substr($filterMonth, 5, 2);

        // Ambil data dalam satu kali query aggregate
        $stats = WorkOrderGeneralAffair::query()
            ->where(function ($q) use ($year, $month) {
                $q->whereYear('target_completion_date', $year)
                    ->whereMonth('target_completion_date', $month)
                    // Fallback jika target null, gunakan created_at
                    ->orWhere(function ($sub) use ($year, $month) {
                        $sub->whereNull('target_completion_date')
                            ->whereYear('created_at', $year)
                            ->whereMonth('created_at', $month);
                    });
            })
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed')
            ->first();

        $total = $stats->total ?? 0;
        $completed = $stats->completed ?? 0; // Hasil SUM bisa null jika total 0, jadi perlu coalescing
        $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;

        return [
            'perfTotal'      => $total,
            'perfCompleted'  => (int) $completed,
            'perfPercentage' => $percentage,
        ];
    }
}
