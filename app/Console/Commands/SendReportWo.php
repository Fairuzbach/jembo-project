<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\Facilities\WorkOrderFacilities;
use App\Models\GeneralAffair\WorkOrderGeneralAffair;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SendReportWo extends Command
{
    protected $signature = 'email:report-wo {tipe}';
    protected $description = 'Kirim laporan rekapitulasi mendalam per departemen';

    public function handle()
    {
        $tipe = $this->argument('tipe');
        $startDate = $tipe === 'weekly' ? Carbon::now()->subDays(7) : Carbon::now()->subMonth();
        $endDate = Carbon::now();

        // ==========================================
        // 1. LOGIKA GENERAL AFFAIR (GA)
        // ==========================================
        $rekapGA = WorkOrderGeneralAffair::whereBetween('created_at', [$startDate, $endDate])->get();

        // Pisahkan Internal vs Eksternal
        $gaInternal = $rekapGA->filter(fn($item) => in_array(strtoupper($item->user->divisi ?? ''), ['GA', 'GENERAL AFFAIR']));
        $gaExternal = $rekapGA->filter(fn($item) => !in_array(strtoupper($item->user->divisi ?? ''), ['GA', 'GENERAL AFFAIR']));

        // Statistik Departemen Eksternal Teraktif
        $topDeptGa = $gaExternal->groupBy(fn($item) => $item->user->divisi ?? 'N/A')
            ->map->count()->sortDesc()->take(3);

        $dataGA = [
            'tipe_laporan' => ucfirst($tipe),
            'periode'      => $startDate->format('d M Y') . ' s/d ' . $endDate->format('d M Y'),
            'departemen'   => 'General Affair (GA)',
            'icon'         => '💼',
            'theme_color'  => '#3b82f6',
            'total'        => $rekapGA->count(),
            'selesai'      => $rekapGA->where('status', 'completed')->count(),
            'is_ga'        => true,
            'stats'        => [
                'internal_count' => $gaInternal->count(),
                'external_count' => $gaExternal->count(),
                'top_depts'      => $topDeptGa
            ]
        ];

        $this->kirimKePimpinan(['GA', 'General Affair'], $dataGA);

        // ==========================================
        // 2. LOGIKA FACILITY
        // ==========================================
        $rekapFac = WorkOrderFacilities::whereBetween('created_at', [$startDate, $endDate])->get();

        // Top 3 Kategori, Mesin, dan Dept (tetap sama)
        $topCatFac = $rekapFac->groupBy('category')->map->count()->sortDesc()->take(5);
        $topMachineFac = $rekapFac->whereNotNull('machine_name')->groupBy('machine_name')->map->count()->sortDesc()->take(5);
        $topDeptFac = $rekapFac->groupBy(fn($item) => $item->user->divisi ?? 'N/A')->map->count()->sortDesc()->take(5);

        $techStats = [];
        foreach ($rekapFac as $wo) {
            foreach ($wo->technicians as $tech) {
                $techStats[$tech->name] = ($techStats[$tech->name] ?? 0) + 1;
            }
        }
        arsort($techStats);
        $topTechFac = array_slice($techStats, 0, 5, true); // Ambil Top 5 Teknisi

        $dataFac = [
            'tipe_laporan' => ucfirst($tipe),
            'periode'      => $startDate->format('d M Y') . ' s/d ' . $endDate->format('d M Y'),
            'departemen'   => 'Facility',
            'icon'         => '🛠️',
            'theme_color'  => '#10b981',
            'total'        => $rekapFac->count(),
            'selesai'      => $rekapFac->where('status', 'completed')->count(),
            'is_ga'        => false,
            'stats'        => [
                'top_categories' => $topCatFac,
                'top_machines'   => $topMachineFac,
                'top_depts'      => $topDeptFac,
                'top_technicians' => $topTechFac
            ]
        ];

        $this->kirimKePimpinan(['FACILITY'], $dataFac);

        $this->info("Laporan mendalam berhasil dikirim.");
    }

    private function kirimKePimpinan($divisi, $data)
    {
        $pimpinan = User::whereIn('divisi', (array)$divisi)
            ->where(function ($q) {
                $q->where('job_level', 'LIKE', '%MANAGER%')
                    ->orWhere('job_level', 'LIKE', '%MGR%')
                    ->orWhere('job_level', 'LIKE', '%SUPERVISOR%')
                    ->orWhere('job_level', 'LIKE', '%SPV%');
            })->whereNotNull('email')->get();

        foreach ($pimpinan as $bos) {
            Mail::to($bos->email)->send(new \App\Mail\WoReportMail($data, $bos->name));
        }
    }
}
