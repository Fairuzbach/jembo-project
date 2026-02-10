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

    private function prepareGanttChart($tickets)
    {
        $data = [];
        $links = [];
        $groupedByDivision = $tickets->groupBy(function ($ticket) {
            return $ticket->department ?? $ticket->user->divisi ?? 'General';
        });

        foreach ($groupedByDivision as $divisionName => $divisionTickets) {
            $divisionId = 'div_' . preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($divisionName));
            $data[] = [
                'id' => $divisionId,
                'text' => $divisionName,
                'type' => 'project',
                'open' => true,
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
            ];
        }

        return [
            'data' => $data,
            'links' => $links
        ];
    }

    private function prepareGroupedStats($tickets)
    {
        // Helper function untuk format data chart
        $formatForTable = function ($grouped) {
            return $grouped->map(fn($list, $key) => (object)['label' => $key, 'total' => $list->count()])
                ->sortByDesc('total')->values();
        };

        // ---------------------------------------------------------
        // 1. STATISTIK DEPARTMENT (PENGIRIM)
        // ---------------------------------------------------------
        $deptGroup = $tickets->groupBy(fn($i) => $i->department ?? 'Unassigned');
        $deptData  = $formatForTable($deptGroup);

        // ---------------------------------------------------------
        // 2. STATISTIK LOKASI (PLANT) - FIX RELASI
        // ---------------------------------------------------------
        $locGroup = $tickets
            ->filter(function ($ticket) {
                return !empty($ticket->plant); // Filter yang plant-nya kosong
            })
            ->groupBy(function ($ticket) {
                // Pastikan relasi di Model bernama 'plantInfo' atau 'plant_info'
                // Gunakan Null Coalescing Operator (??) untuk fallback
                $plantName = $ticket->plantInfo->name ?? $ticket->plant_info->name ?? ('Unknown Plant (' . $ticket->plant . ')');
                return trim($plantName);
            });
        $locData = $formatForTable($locGroup);

        // ---------------------------------------------------------
        // 3. STATISTIK PARAMETER (JENIS PERMINTAAN)
        // ---------------------------------------------------------
        $paramGroup = $tickets->groupBy(fn($i) => $i->parameter_permintaan ?? 'Lainnya');
        $paramData  = $formatForTable($paramGroup);

        // ---------------------------------------------------------
        // 4. STATISTIK BOBOT (CATEGORY)
        // ---------------------------------------------------------
        $catGroup = $tickets->groupBy('category')->map->count();
        $chartBobotValues = [
            $catGroup['HIGH'] ?? $catGroup['BERAT'] ?? 0,
            $catGroup['MEDIUM'] ?? $catGroup['SEDANG'] ?? 0,
            $catGroup['LOW'] ?? $catGroup['RINGAN'] ?? 0
        ];

        return [
            // Kirim Data Mentah untuk Tabel (Opsional)
            'locData'          => $locData,
            'deptData'         => $deptData,
            'paramData'        => $paramData,

            // Kirim Data Array untuk Chart.js
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
        $query = WorkOrderGeneralAffair::query()
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
        $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;
        return [
            'perfTotal'      => $total,
            'perfCompleted'  => $completed,
            'perfPercentage' => $percentage,
        ];
    }
}
