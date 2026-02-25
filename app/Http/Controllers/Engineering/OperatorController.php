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
        // Ambil NIK dari query string
        $nik = $request->query('nik');

        // Cari di database
        $operator = \App\Models\Operator::where('nik', $nik)->first();

        if ($operator) {
            return response()->json([
                'success' => true,
                'name'    => $operator->name
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Operator tidak ditemukan'
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
