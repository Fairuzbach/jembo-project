<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\GeneralAffair\Category;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        // 1. Ambil Data Pendukung
        $roles = Role::where('name', '!=', 'Super Admin')->get(); // Jangan tampilkan role Super Admin di filter
        $parameters = config('workorder.parameters');
        // $categories = config('workorder.categories');

        // 2. Konfigurasi Sorting
        $sortField = $request->get('sort', 'created_at'); // Default sort
        $sortDirection = $request->get('direction', 'desc'); // Default direction

        $divisions = User::select('divisi')
            ->distinct()
            ->whereNotNull('divisi')
            ->where('divisi', '!=', '')
            ->orderBy('divisi')
            ->pluck('divisi');

        // TAMBAHKAN 'divisi' KE SINI
        $allowedSorts = ['name', 'email', 'created_at', 'recent', 'divisi'];

        // Validasi Sort Field
        if (! in_array($sortField, $allowedSorts)) {
            $sortField = 'created_at';
        }

        // 3. Build base query tanpa pagination untuk menghitung total
        $baseQuery = User::with('roles')
            // Filter: Search (Nama, Email, NIK, dan DIVISI)
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = $request->search;
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('nik', 'like', "%{$term}%")
                        ->orWhere('divisi', 'like', "%{$term}%"); // Tambahkan pencarian divisi juga
                });
            })
            // Filter: Role
            ->when($request->filled('role'), function ($query) use ($request) {
                // Menggunakan scope bawaan Spatie agar lebih rapi
                $query->role($request->role);
            })
            // Filter: Divisi
            ->when($request->filled('divisi'), function ($query) use ($request) {
                $query->where('divisi', $request->divisi);
            })
            // Filter Security: Sembunyikan akun Super Admin agar tidak bisa diedit via menu ini
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'Super Admin');
            });

        // Hitung total dan active sebelum pagination
        $totalUsers = $baseQuery->count();
        $activeCount = (clone $baseQuery)->where('is_active', true)->count();

        // Apply sorting dan pagination
        $users = $baseQuery
            // Logic Sorting
            ->when($sortField === 'recent', function ($q) {
                $q->latest();
            })
            ->when($sortField !== 'recent', function ($q) use ($sortField, $sortDirection) {
                $q->orderBy($sortField, $sortDirection);
            })
            ->paginate(10)
            ->withQueryString();
        // 4. Return View
        $filters = $request->only(['search', 'role', 'divisi', 'sort', 'direction']);
        $categoriesDB = \App\Models\GeneralAffair\Category::latest()->get();

        return view('admin.users.index', compact('users', 'roles', 'divisions', 'filters', 'parameters', 'categoriesDB', 'activeCount', 'totalUsers'));
    }

    public function updateRole(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'role' => 'required|exists:roles,name',
        ]);

        $user->syncRoles([$request->role]);

        return redirect()->back()->with('success', 'Role user berhasil diupdate!');
    }

    public function destroy(User $user)
    {
        // Proteksi: Jangan biarkan menghapus diri sendiri atau Super Admin
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak bisa menghapus akun Anda sendiri.');
        }

        if ($user->hasRole('Super Admin')) {
            return back()->with('error', 'Akun Super Admin tidak boleh dihapus.');
        }

        // Hapus
        $user->delete();

        return back()->with('success', "User {$user->name} berhasil dihapus.");
    }

    /**
     * Reset Password (Opsional - Fitur Tombol Edit)
     */
    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|min:6|confirmed', // butuh input name="password_confirmation"
        ]);

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return back()->with('success', "Password untuk {$user->name} berhasil direset.");
    }

    public function storeCategory(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
            'color' => 'required|string',
            'status' => 'required|in:active,inactive',
        ]);

        // 2. Simpan ke Database
        // Pastikan model Category di-import: use App\Models\Category; (atau GeneralAffair\Category sesuai struktur Anda)
        \App\Models\GeneralAffair\Category::create([
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color,
            'status' => $request->status,
        ]);

        // 3. Kembali ke halaman sebelumnya
        return back()->with('success', 'Kategori berhasil ditambahkan!');
    }
    public function destroyCategory($id)
    {
        // Cari kategori berdasarkan ID
        $category = Category::findOrFail($id);

        // Hapus
        $category->delete();

        return back()->with('success', 'Kategori berhasil dihapus.');
    }
}
