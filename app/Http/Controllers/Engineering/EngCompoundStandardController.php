<?php

namespace App\Http\Controllers\Engineering;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Engineering\EngCompoundStandard;

class EngCompoundStandardController extends Controller
{
    public function standardsIndex()
    {
        // Mengambil semua data standar, diurutkan berdasarkan Plant dan Mesin
        $standards = EngCompoundStandard::orderBy('plant', 'asc')
            ->orderBy('kode_mesin', 'asc')
            ->orderBy('proses', 'desc') // desc supaya Drawing tampil duluan dari Annealing
            ->get();

        return view('Division.Engineering.standards-index', compact('standards'));
    }

    // Fungsi untuk mengupdate data standar ke database
    public function standardsUpdate(Request $request, $id)
    {
        $standard = EngCompoundStandard::findOrFail($id);

        // Update hanya kolom-kolom nilainya saja
        $standard->update([
            'std_tipe'        => $request->std_tipe,
            'std_supplier'    => $request->std_supplier,
            'std_warna'       => $request->std_warna,
            'std_konsentrasi' => $request->std_konsentrasi,
            'std_ph'          => $request->std_ph,
            'std_temp'        => $request->std_temp,
        ]);

        return redirect()->back()->with('success', 'Nilai standar berhasil diperbarui!');
    }
}
