<x-app-layout>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">
        <div class="bg-white shadow-sm border-b border-slate-200">
            <div class="container mx-auto px-6 py-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800">User Management</h1>
                        <p class="text-slate-500 mt-1">Kelola pengguna dan permissions sistem</p>
                    </div>
                    <div class="flex gap-3">
                        <button onclick="toggleModal('categoryModal')"
                            class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-all duration-200 shadow-lg shadow-indigo-600/30 hover:shadow-xl hover:shadow-indigo-600/40 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Kategori Permintaan
                        </button>
                        <button
                            class="px-5 py-2.5 bg-white hover:bg-slate-50 text-slate-700 rounded-lg font-medium transition-all duration-200 border border-slate-300 shadow-sm flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export Data
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="container mx-auto px-6 py-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div
                    class="group relative bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:scale-105 border border-blue-200">
                    <div
                        class="absolute inset-0 bg-gradient-to-br from-blue-400 to-blue-600 opacity-0 group-hover:opacity-10 rounded-2xl transition-opacity duration-300">
                    </div>
                    <div class="relative flex items-center justify-between">
                        <div>
                            <p class="text-blue-600 text-sm font-semibold uppercase tracking-wide">Total Users</p>
                            <h3 class="text-4xl font-bold text-blue-900 mt-2">{{ $totalUsers }}</h3>
                        </div>
                        <div
                            class="w-16 h-16 bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-shadow">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-blue-200">
                        <div class="w-full bg-blue-200 rounded-full h-2">
                            <div class="bg-gradient-to-r from-blue-400 to-blue-600 h-2 rounded-full" style="width: 75%">
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="group relative bg-gradient-to-br from-purple-50 to-purple-100 rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:scale-105 border border-purple-200">
                    <div
                        class="absolute inset-0 bg-gradient-to-br from-purple-400 to-purple-600 opacity-0 group-hover:opacity-10 rounded-2xl transition-opacity duration-300">
                    </div>
                    <div class="relative flex items-center justify-between">
                        <div>
                            <p class="text-purple-600 text-sm font-semibold uppercase tracking-wide">Roles</p>
                            <h3 class="text-4xl font-bold text-purple-900 mt-2">{{ count($roles) }}</h3>
                        </div>
                        <div
                            class="w-16 h-16 bg-gradient-to-br from-purple-400 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-shadow">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-purple-200">
                        <div class="w-full bg-purple-200 rounded-full h-2">
                            <div class="bg-gradient-to-r from-purple-400 to-purple-600 h-2 rounded-full"
                                style="width: 60%"></div>
                        </div>
                    </div>
                </div>

                <div
                    class="group relative bg-gradient-to-br from-green-50 to-green-100 rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:scale-105 border border-green-200">
                    <div
                        class="absolute inset-0 bg-gradient-to-br from-green-400 to-green-600 opacity-0 group-hover:opacity-10 rounded-2xl transition-opacity duration-300">
                    </div>
                    <div class="relative flex items-center justify-between">
                        <div>
                            <p class="text-green-600 text-sm font-semibold uppercase tracking-wide">Active Users</p>
                            <h3 class="text-4xl font-bold text-green-900 mt-2">{{ $activeCount }}</h3>
                        </div>
                        <div
                            class="w-16 h-16 bg-gradient-to-br from-green-400 to-green-600 rounded-2xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-shadow">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-green-200">
                        <div class="w-full bg-green-200 rounded-full h-2">
                            <div class="bg-gradient-to-r from-green-400 to-green-600 h-2 rounded-full"
                                style="width: 85%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
                <form method="GET" action="{{ route('users.index') }}">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-slate-400"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                                placeholder="Search users..."
                                class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200">
                        </div>
                        <select name="divisi"
                            class="px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                            onchange="this.form.submit()">
                            <option value="">Semua Divisi</option>
                            @foreach ($divisions as $div)
                                <option value="{{ $div }}"
                                    {{ ($filters['divisi'] ?? '') === $div ? 'selected' : '' }}>
                                    {{ $div }}
                                </option>
                            @endforeach
                        </select>
                        <div class="flex gap-2">
                            <select name="sort"
                                class="px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                                onchange="this.form.submit()">
                                <option value="name" {{ ($filters['sort'] ?? '') === 'name' ? 'selected' : '' }}>Sort
                                    by: Name</option>
                                <option value="email" {{ ($filters['sort'] ?? '') === 'email' ? 'selected' : '' }}>
                                    Sort
                                    by: Email</option>
                                <option value="created_at"
                                    {{ ($filters['sort'] ?? '') === 'created_at' ? 'selected' : '' }}>Sort by: Dibuat
                                </option>
                                <option value="recent" {{ ($filters['sort'] ?? '') === 'recent' ? 'selected' : '' }}>
                                    Sort by: Recent</option>
                            </select>
                            <select name="direction"
                                class="px-3 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                                onchange="this.form.submit()">
                                <option value="asc"
                                    {{ ($filters['direction'] ?? 'asc') === 'asc' ? 'selected' : '' }}>Asc</option>
                                <option value="desc"
                                    {{ ($filters['direction'] ?? '') === 'desc' ? 'selected' : '' }}>Desc</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                    User
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                    Divisi</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                    NIK / Email</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                    Current Role</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                    Change Role</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach ($users as $user)
                                <tr class="hover:bg-slate-50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-semibold text-sm shadow-md">
                                                {{ strtoupper(substr($user->name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <div class="font-semibold text-slate-800">{{ $user->name }}</div>
                                                <div class="text-xs text-slate-500">ID: #{{ $user->id }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($user->divisi)
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $user->divisi }}
                                            </span>
                                        @else
                                            <span class="text-slate-400 text-xs italic">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col">
                                            <div class="flex items-center gap-2 mb-1">
                                                <svg class="w-3 h-3 text-slate-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                                                </svg>
                                                <span
                                                    class="text-slate-700 font-mono text-xs">{{ $user->nik ?? '-' }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <svg class="w-3 h-3 text-slate-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                                <span class="text-slate-600 text-xs">{{ $user->email }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($user->is_active)
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 border border-green-200">
                                                <span class="w-2 h-2 bg-green-500 rounded-full mr-1.5"></span>
                                                Active
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 border border-red-200">
                                                <span class="w-2 h-2 bg-red-500 rounded-full mr-1.5"></span>
                                                Inactive
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($user->roles as $role)
                                                <span
                                                    class="px-3 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-700 border border-indigo-200">
                                                    {{ $role->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <form action="{{ route('users.update-role', $user->id) }}" method="POST"
                                            class="flex items-center gap-2">
                                            @csrf
                                            @method('PUT')
                                            <select name="role"
                                                class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200">
                                                @foreach ($roles as $role)
                                                    <option value="{{ $role->name }}"
                                                        {{ $user->hasRole($role->name) ? 'selected' : '' }}>
                                                        {{ $role->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit"
                                                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-all duration-200 shadow-sm hover:shadow-md">
                                                Save
                                            </button>
                                        </form>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <button
                                                onclick="openResetModal('{{ $user->id }}', '{{ $user->name }}')"
                                                class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition-all duration-200"
                                                title="Reset Password / Edit User">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                </svg>
                                            </button>

                                            <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                                                onsubmit="return confirm('Apakah Anda yakin ingin menghapus user {{ $user->name }}? Data yang sudah dihapus tidak bisa dikembalikan.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-all duration-200"
                                                    title="Hapus User">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>

    <div id="categoryModal"
        class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">

            {{-- HEADER MODAL --}}
            <div
                class="sticky top-0 bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between rounded-t-2xl z-10">
                <h3 class="text-xl font-bold text-slate-800">Kelola Kategori Permintaan</h3>
                <button onclick="toggleModal('categoryModal')"
                    class="p-2 hover:bg-slate-100 rounded-lg transition-colors duration-200">
                    <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-6">
                {{-- FORM TAMBAH KATEGORI --}}
                <div
                    class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl p-6 mb-6 border border-indigo-100">
                    <h4 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Tambah Kategori Baru
                    </h4>

                    {{-- Form ini akan dikembangkan nanti, sementara dummy # --}}
                    <form action="{{ route('users.categories.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Nama Kategori</label>
                            <input type="text" name="name" required
                                class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                                placeholder="Contoh: Kebersihan, Pemeliharaan">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Deskripsi</label>
                            <textarea name="description" rows="3"
                                class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                                placeholder="Deskripsi singkat kategori..."></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Icon/Color</label>
                                <select name="color"
                                    class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200">
                                    <option value="blue">Blue</option>
                                    <option value="green">Green</option>
                                    <option value="red">Red</option>
                                    <option value="yellow">Yellow</option>
                                    <option value="purple">Purple</option>
                                    <option value="indigo">Indigo</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Status</label>
                                <select name="status"
                                    class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit"
                            class="w-full px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-all duration-200 shadow-lg shadow-indigo-600/30 hover:shadow-xl hover:shadow-indigo-600/40">
                            Simpan Kategori
                        </button>
                    </form>
                </div>

                {{-- DAFTAR KATEGORI (DARI DATABASE) --}}
                <div>
                    <h4 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                        Kategori Tersedia
                    </h4>

                    <div class="space-y-3 max-h-64 overflow-y-auto pr-2 custom-scrollbar">
                        @forelse($categoriesDB as $cat)
                            <div
                                class="flex items-center justify-between p-4 bg-white border border-slate-200 rounded-lg hover:shadow-md transition-all duration-200 group">
                                {{-- Kiri: Info --}}
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 bg-{{ $cat->color ?? 'blue' }}-100 rounded-lg flex items-center justify-center transition-colors">
                                        <span class="text-{{ $cat->color ?? 'blue' }}-600 font-semibold text-sm">
                                            {{ strtoupper(substr($cat->name, 0, 2)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <h5 class="font-semibold text-slate-800">{{ $cat->name }}</h5>
                                        <p class="text-xs text-slate-500">{{ Str::limit($cat->description, 40) }}</p>
                                    </div>
                                </div>

                                {{-- Kanan: Action --}}
                                <div class="flex items-center gap-3">
                                    <span
                                        class="px-3 py-1 text-xs font-semibold rounded-full {{ $cat->status == 'active' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500' }}">
                                        {{ ucfirst($cat->status) }}
                                    </span>

                                    {{-- Tombol Delete --}}
                                    <form action="{{ route('users.categories.destroy', $cat->id) }}" method="POST"
                                        onsubmit="return confirm('Hapus kategori ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all opacity-0 group-hover:opacity-100 focus:opacity-100">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div
                                class="text-center py-6 text-slate-400 border border-dashed border-slate-300 rounded-lg">
                                <p>Belum ada kategori.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="resetModal"
        class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Reset Password</h3>
            <p class="text-sm text-slate-500 mb-4">Masukkan password baru untuk user: <span id="resetUserName"
                    class="font-bold text-slate-800"></span></p>

            <form id="resetForm" method="POST" action="">
                @csrf
                @method('PUT')

                <div class="space-y-3">
                    <div>
                        <input type="password" name="password" placeholder="Password Baru" required
                            class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <div>
                        <input type="password" name="password_confirmation" placeholder="Konfirmasi Password"
                            required class="w-full px-4 py-2 border rounded-lg">
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" onclick="document.getElementById('resetModal').classList.add('hidden')"
                        class="px-4 py-2 text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
                    <button type="submit"
                        class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg">Reset
                        Password</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openResetModal(userId, userName) {
            // Set nama user di text
            document.getElementById('resetUserName').innerText = userName;

            // Set action form dinamis

            let url = "{{ route('users.reset-password', ':id') }}";
            url = url.replace(':id', userId);
            document.getElementById('resetForm').action = url;

            // Buka modal
            document.getElementById('resetModal').classList.remove('hidden');
        }
    </script>
    <script>
        function toggleModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.toggle('hidden');
            } else {
                console.error('Modal dengan ID ' + modalId + ' tidak ditemukan!');
            }
        }

        // Close modal when clicking outside
        document.getElementById('categoryModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                toggleModal('categoryModal');
            }
        });

        // Close modal with ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('categoryModal');
                if (modal && !modal.classList.contains('hidden')) {
                    toggleModal('categoryModal');
                }
            }
        });
    </script>

    <style>
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        tbody tr {
            animation: slideIn 0.3s ease-out;
        }

        /* Custom scrollbar */
        .overflow-y-auto::-webkit-scrollbar {
            width: 8px;
        }

        .overflow-y-auto::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }

        .overflow-y-auto::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .overflow-y-auto::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</x-app-layout>
