@props([
    'countTotal',
    'countPending',
    'countInProgress',
    'countCompleted',
    'countWaitingApproval' => 0,
    'countWaitingApprovalSpv' => 0,
    'countWaitingApprovalGA' => 0,
    'countRejected' => 0,
])

@php
    $currentUserRole = Auth::user()->role ?? null;
    $isGAAdmin = $currentUserRole === 'ga.admin';
    $isTeknisAdmin = in_array($currentUserRole, ['mt.admin', 'fh.admin', 'eng.admin']);

    // Tentukan grid class berdasarkan user role
    // GA Admin: 6 cards (Total, Pending, Waiting GA, Progress, Rejected, Selesai)
    // Teknis Admin: 6 cards (Total, Pending, Waiting SPV, Progress, Rejected, Selesai)
    // Regular User: 5 cards (Total, Waiting Approval SPV, Progress, Rejected, Selesai)
    if ($isGAAdmin || $isTeknisAdmin) {
        $gridClass = 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6';
    } else {
        $gridClass = 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5';
    }
@endphp

<div class="grid {{ $gridClass }} gap-6 mb-10">

    @php
        $cards = [
            [
                'title' => 'Total Tiket',
                'value' => $countTotal,
                'color' => 'gray',
                'bg' => 'bg-gradient-to-br from-slate-100 to-slate-200',
                'icon_bg' => 'bg-slate-300/40',
                'icon_color' => 'text-slate-700',
                'text_color' => 'text-slate-800',
                'border' => 'border-slate-300',
                'accent' => 'bg-slate-200/50',
                'icon_path' =>
                    'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2',
            ],
        ];

        // Untuk admin (GA Admin dan Teknis Admin), tampilkan Pending
        if ($isGAAdmin || $isTeknisAdmin) {
            $cards[] = [
                'title' => 'Pending',
                'value' => $countPending,
                'color' => 'amber',
                'bg' => 'bg-gradient-to-br from-amber-400 via-amber-500 to-amber-600',
                'icon_bg' => 'bg-amber-300/30',
                'icon_color' => 'text-white',
                'text_color' => 'text-white',
                'border' => 'border-amber-300',
                'accent' => 'bg-amber-300/20',
                'icon_path' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            ];
        } else {
            // Untuk regular user, tampilkan Waiting Approval SPV
            $cards[] = [
                'title' => 'Waiting Approval SPV',
                'value' => $countWaitingApprovalSpv,
                'color' => 'yellow',
                'bg' => 'bg-gradient-to-br from-yellow-400 via-yellow-500 to-yellow-600',
                'icon_bg' => 'bg-yellow-300/30',
                'icon_color' => 'text-white',
                'text_color' => 'text-white',
                'border' => 'border-yellow-300',
                'accent' => 'bg-yellow-300/20',
                'icon_path' => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
            ];
        }

        // Tambahkan waiting approval cards berdasarkan role admin
        if ($isGAAdmin) {
            // GA Admin lihat Waiting Approval GA saja
            $cards[] = [
                'title' => 'Waiting Approval GA',
                'value' => $countWaitingApprovalGA,
                'color' => 'red',
                'bg' => 'bg-gradient-to-br from-[#dc2626] via-[#c81e1e] to-[#b91c1c]',
                'icon_bg' => 'bg-red-400/30',
                'icon_color' => 'text-white',
                'text_color' => 'text-white',
                'border' => 'border-red-400',
                'accent' => 'bg-red-300/20',
                'icon_path' =>
                    'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
                'show_approval' => true,
            ];
        } elseif ($isTeknisAdmin) {
            // Admin teknis (mt.admin, fh.admin, eng.admin) lihat Waiting Approval SPV
            $cards[] = [
                'title' => 'Waiting Approval SPV',
                'value' => $countWaitingApprovalSpv,
                'color' => 'yellow',
                'bg' => 'bg-gradient-to-br from-yellow-400 via-yellow-500 to-yellow-600',
                'icon_bg' => 'bg-yellow-300/30',
                'icon_color' => 'text-white',
                'text_color' => 'text-white',
                'border' => 'border-yellow-300',
                'accent' => 'bg-yellow-300/20',
                'icon_path' => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
                'show_approval' => true,
            ];
        }

        // Tambahkan sisanya untuk semua user
        $cards[] = [
            'title' => 'On Progress',
            'value' => $countInProgress,
            'color' => 'blue',
            'bg' => 'bg-gradient-to-br from-[#1e40af] via-[#1e3a8a] to-[#1e3a8a]',
            'icon_bg' => 'bg-blue-400/30',
            'icon_color' => 'text-white',
            'text_color' => 'text-white',
            'border' => 'border-blue-400',
            'accent' => 'bg-blue-300/20',
            'icon_path' =>
                'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z',
        ];

        $cards[] = [
            'title' => 'Rejected',
            'value' => $countRejected ?? 0,
            'color' => 'red',
            'bg' => 'bg-gradient-to-br from-red-500 via-red-600 to-red-700',
            'icon_bg' => 'bg-red-400/30',
            'icon_color' => 'text-white',
            'text_color' => 'text-white',
            'border' => 'border-red-400',
            'accent' => 'bg-red-300/20',
            'icon_path' => 'M6 18L18 6M6 6l12 12',
        ];

        $cards[] = [
            'title' => 'Selesai',
            'value' => $countCompleted,
            'color' => 'white',
            'bg' => 'bg-gradient-to-br from-white via-gray-50 to-gray-100',
            'icon_bg' => 'bg-white/50',
            'icon_color' => 'text-gray-700',
            'text_color' => 'text-gray-800',
            'border' => 'border-gray-300',
            'accent' => 'bg-gray-100/30',
            'icon_path' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        ];
    @endphp

    @foreach ($cards as $card)
        <div
            class="status-card relative {{ $card['bg'] }} border-none rounded-2xl shadow-lg hover:shadow-2xl overflow-hidden group hover:-translate-y-2 transition-all duration-300 border {{ $card['border'] }}">
            {{-- Gradient Background Accent --}}
            <div
                class="absolute top-0 right-0 w-32 h-32 {{ $card['accent'] }} rounded-full -mr-16 -mt-16 group-hover:scale-150 transition-transform duration-500">
            </div>

            {{-- Icon Pattern Background --}}
            <div
                class="absolute top-0 right-0 p-5 opacity-10 group-hover:opacity-20 group-hover:scale-110 transition-all duration-500">
                <svg class="w-28 h-28 {{ $card['icon_color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="{{ $card['icon_path'] }}" />
                </svg>
            </div>

            <div class="p-7 relative z-10">
                <div class="flex items-center justify-between mb-5">
                    <h3
                        class="text-xs font-bold {{ $card['text_color'] }} uppercase tracking-widest pl-3 border-l-4 {{ in_array($card['color'], ['white', 'gray']) ? 'border-gray-500' : 'border-white/70' }}">
                        {{ $card['title'] }}
                    </h3>
                    <div
                        class="w-12 h-12 rounded-xl {{ $card['icon_bg'] }} flex items-center justify-center group-hover:scale-110 group-hover:rotate-6 transition-all duration-300 backdrop-blur-sm">
                        <svg class="w-6 h-6 {{ $card['icon_color'] }}" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="{{ $card['icon_path'] }}" />
                        </svg>
                    </div>
                </div>

                <div class="flex items-baseline">
                    <span
                        class="text-5xl font-black {{ $card['text_color'] }} tracking-tight drop-shadow-sm">{{ $card['value'] }}</span>
                    <span class="ml-3 text-sm font-semibold {{ $card['text_color'] }} opacity-80">Tiket</span>
                </div>

                {{-- Badge untuk Waiting Approval --}}
                @if (isset($card['show_for']) && $card['value'] > 0)
                    <div class="mt-3 pt-3 border-t border-white/20">
                        <span
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white/20 backdrop-blur-sm text-[10px] font-bold {{ $card['text_color'] }} uppercase tracking-wide animate-pulse">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            Perlu Tindakan
                        </span>
                    </div>
                @endif
            </div>

            {{-- Shine effect on hover --}}
            <div
                class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none bg-gradient-to-tr from-transparent via-white/10 to-transparent transform -translate-x-full group-hover:translate-x-full transition-transform duration-1000">
            </div>
        </div>
    @endforeach
</div>
