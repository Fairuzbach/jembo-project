<?php

namespace App\Http\Controllers\Engineering;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Engineering\WorkOrderEngineering;
use App\Models\Engineering\Plant;
use App\Models\Engineering\EngineerTech;
use App\Models\Engineering\ImprovementStatus;
use App\Models\Engineering\ParameterImprovement;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class WorkOrderEngineeringController extends Controller
{

    public function index(Request $request)
    {
        // $queryUser = WorkOrderEngineering::query();
        $query = WorkOrderEngineering::latest();
        $user = Auth::user();
        $statsQuery = WorkOrderEngineering::query();

        if ($user->role !== 'eng.admin') {
            $query->where('requester_id', $user->id);
            $statsQuery->where('requester_id', $user->id);
        }
        $countTotal = (clone $statsQuery)->count();
        $countPending = (clone $statsQuery)->where('improvement_status', 'OPEN')->count();
        $countInProgress = (clone $statsQuery)->where('improvement_status', 'WIP')->count();
        $countCompleted = (clone $statsQuery)->where('improvement_status', 'CLOSED')->count();

        // 1. SEARCH
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_num', 'like', '%' . $search . '%')
                    ->orWhere('machine_name', 'like', '%' . $search . '%')->orWhere('plant', 'like', '%' . $search . '%');
            });
        }

        // 2. FILTER STATUS (Disesuaikan dengan improvement_status)
        if ($request->filled('improvement_status')) {
            $query->where('improvement_status', $request->improvement_status);
        }
        // Fallback untuk jaga-jaga jika ada link lama yang pakai work_status
        elseif ($request->filled('work_status')) {
            $query->where('improvement_status', $request->work_status);
        }

        // 3. PAGINATION
        $workOrders = $query->with('requester')
            ->paginate(10)
            ->withQueryString();

        // Data Pendukung
        $plants = Plant::with('machines')->get();
        $technicians = EngineerTech::all();
        // $improvementStatuses = ImprovementStatus::all();
        $improvementParameters = ParameterImprovement::all();

        return view('Division.Engineering.Engineering', compact(
            'workOrders',
            'plants',
            'technicians',
            'improvementParameters',
            'countTotal',
            'countPending',
            'countInProgress',
            'countCompleted'
        ));
    }

    public function storeCompound(Request $request) {}
    public function store(Request $request)
    {
        $request->validate([
            'report_date' => 'required|date',
            'report_time' => 'required',
            'plant' => 'required|string',
            'engineer_tech' => 'required|array|min:1|max:5',
            'plant' => 'required|string',
            'machine_name' => 'required|string',
            'damaged_part' => 'required|string',
            'improvement_parameters' => 'required|string',
            'kerusakan_detail' => 'required|string',
            'priority' => 'nullable',
            'photo' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'initial_status' => 'required|in:OPEN,WIP,CLOSED',
        ], [
            'engineer_tech.required' => 'Wajib memilih minimal 1 engineer (Nama sendiri)'
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('work_orders', 'public');
        }

        $engineerString = implode(',', $request->engineer_tech);

        $dateCode = date('Ymd');
        $prefix = 'engIO-' . $dateCode . '-';
        $lastWorkOrder = WorkOrderEngineering::where('ticket_num', 'like', $prefix . '%')->orderBy('id', 'desc')->first();

        if ($lastWorkOrder) {
            $lastNumber = (int) substr($lastWorkOrder->ticket_num, -3);
            $newSequence = $lastNumber + 1;
        } else {
            $newSequence = 0;
        }
        $ticketNum = $prefix . sprintf('%03d', $newSequence);

        $finishedDate = null;
        if ($request->initial_status == 'CLOSED') {
            $finishedDate = Carbon::now();
        }

        WorkOrderEngineering::create([
            'requester_id' => auth()->id(),
            'ticket_num' => $ticketNum,
            'report_date' => $request->report_date,
            'report_time' => $request->report_time,
            'engineer_tech' => $engineerString,
            'plant' => $request->plant,
            'machine_name' => $request->machine_name,
            'damaged_part' => $request->damaged_part,
            'improvement_parameters' => $request->improvement_parameters,
            'kerusakan' => $request->damaged_part,
            'kerusakan_detail' => $request->kerusakan_detail,
            'priority' => $request->priority ?? 'medium',

            // Set Default Status
            'improvement_status' => $request->initial_status,
            'finished_date' => $finishedDate,

            'photo_path' => $photoPath,
        ]);

        return redirect()->route('engineering.wo.index')->with('success', 'Laporan berhasil dibuat dengan status !' . $request->initial_status);
    }
    public function updateStatus(Request $request, $id)
    {
        $ticket = WorkOrderEngineering::findOrFail($id);
        $user = Auth::user();

        //ROLE USER LOGIC
        if ($ticket->requester_id == $user->id && $user->role !== 'eng.admin') {
            $request->validate([
                'status' => 'required|in:WIP,CLOSED'
            ]);
            $ticket->improvement_status = $request->status;

            if ($request->status == 'CLOSED') {
                $ticket->finished_date = Carbon::now();
            } else {
                $ticket->finished_date = null;
            }
            $ticket->save();
            return redirect()->back()->with('success', 'Status laporan berhasil diupdate !' . $request->status);
        }

        //ROLE ENG.ADMIN LOGIC
        if ($user->role == 'eng.admin') {
            $ticket->improvement_status = $request->status;
            if ($request->status == 'CLOSED') {
                $ticket->finished_date = Carbon::now();
            } else if ($request->status == 'WIP' || $request->status == 'OPEN') {
                $ticket->finished_date = null;
            }
            $ticket->save();
            return redirect()->back()->with('success', 'Status telah diperbarui oleh Admin!');
        }

        if ($request->action == 'cancel') {
            if ($ticket->requester_id == $user->id && $ticket->improvement_status == 'pending') {
                $ticket->improvement_status = 'cancelled';
                $ticket->save();
                return redirect()->back()->with('success', 'Report berhasil dibatalkan!');
            }
            abort(403, 'Aksi tidak diizinkan');
        }

        if ($user->role == 'eng.admin') {
            $request->validate([
                'status' => 'required|in:OPEN,WIP,CLOSED,CANCELLED'
            ]);
            $ticket->improvement_status = $request->status;
            $ticket->save();

            return redirect()->back()->with('success', 'Tiket berhasil diperbarui!');
        }
        abort(403, 'Anda tidak memiliki akses.');
    }

    public function update(Request $request, WorkOrderEngineering $workOrder)
    {
        // PERBAIKAN DI SINI: Sesuaikan validasi dengan input view (improvement_status)
        $request->validate([
            'improvement_status' => 'required|in:OPEN,WIP,CLOSED,CANCELLED',
            'finished_date' => 'nullable|date',
            'start_time' => 'required',
            'end_time' => 'nullable',
            'engineer_tech' => 'nullable|string|max:255',
            'maintenance_note' => 'nullable|string',
            'repair_solution' => 'required|string',
            'sparepart' => 'nullable|string',
        ]);

        $workOrder->update([
            // Sesuaikan mapping input ke database
            'improvement_status' => $request->improvement_status,

            'finished_date' => $request->finished_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'engineer_tech' => $request->engineer_tech,
            'maintenance_note' => $request->maintenance_note,
            'repair_solution' => $request->repair_solution,
            'sparepart' => $request->sparepart,
        ]);

        return redirect()->route('engineering.wo.index')->with('success', 'Status laporan #' . $workOrder->ticket_num . ' berhasil diperbarui!');
    }

    public function export(Request $request)
    {
        if ($request->filled('ticket_ids')) {
            $ids = explode(',', $request->ticket_ids);
            $data = WorkOrderEngineering::with('requester')
                ->whereIn('id', $ids)
                ->orderBy('report_date', 'asc')
                ->get();
            $fileName = 'Laporan_engIO_Selected_' . date('Ymd_His') . '.csv';
        } else {
            $request->validate([
                'start_date' => 'required|date',
                'end_date'   => 'required|date|after_or_equal:start_date',
            ]);

            $startDate = $request->start_date;
            $endDate = $request->end_date;

            $data = WorkOrderEngineering::with('requester')
                ->whereBetween('report_date', [$startDate, $endDate])
                ->orderBy('report_date', 'asc')
                ->orderBy('report_time', 'asc')
                ->get();

            $fileName = 'Laporan_engIO_' . $startDate . '_sd_' . $endDate . '.csv';
        }

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = [
            'No Tiket',
            'Tanggal Lapor',
            'Jam',
            'ID Pelapor',
            'Nama Pelapor',
            'Divisi Pelapor',
            'Plant',
            'Mesin',
            'Request',
            'Prioritas',
            'Status Improvement',
            'Engineer Tech',
            'Uraian Improvement',
            'Sparepart',
            'Tanggal Selesai'
        ];

        $callback = function () use ($data, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($data as $row) {
                fputcsv($file, [
                    $row->ticket_num,
                    \Carbon\Carbon::parse($row->report_date)->format('Y-m-d'),
                    \Carbon\Carbon::parse($row->report_time)->format('H:i'),
                    $row->requester_id,
                    $row->requester->name ?? 'NO NAME',
                    $row->requester->divisi,
                    $row->plant,
                    $row->machine_name,
                    $row->damaged_part,
                    $row->priority,

                    // Pastikan export mengambil kolom improvement_status
                    $row->improvement_status,

                    $row->engineer_tech,
                    $row->kerusakan_detail,
                    $row->sparepart,
                    $row->finished_date ? \Carbon\Carbon::parse($row->finished_date)->format('Y-m-d') : '',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
