<?php

namespace App\Http\Controllers\Engineering;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\OperatorImport;

class OperatorController extends Controller
{
    public function searchOperator(Request $request)
    {
        $nikInput = trim($request->query('nik'));

        if (empty($nikInput)) {
            return response()->json(['success' => false]);
        }

        // Kemungkinan 1: Cari persis seperti input (0605)
        // Kemungkinan 2: Jika di DB tersimpan tanpa nol (605)
        // Kemungkinan 3: Jika di DB tersimpan dengan nol tapi input tanpa nol
        $operator = \App\Models\Operator::where('nik', $nikInput)
            ->orWhere('nik', (int)$nikInput)
            ->orWhere('nik', str_pad($nikInput, 4, '0', STR_PAD_LEFT))
            ->first();

        if ($operator) {
            return response()->json([
                'success' => true,
                'name'    => $operator->name
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Operator tidak ditemukan di Database'
        ]);
    }

    public function importOperator(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|mimes:xlsx,xls'
        ]);

        Excel::import(new OperatorImport, $request->file('file_excel'));

        return redirect()->back()->with('success', 'Data Operator Berhasil Diimport!');
    }
}
