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
    $isGAAdmin = in_array($currentUserRole, ['ga.admin', 'super.ga.admin']);
    $isTeknisAdmin = in_array($currentUserRole, ['mt.admin', 'fh.admin', 'eng.admin']);

    // Tentukan grid class untuk DESKTOP (lg ke atas)
    // Mobile kita set hardcode jadi grid-cols-2 agar lebih rapi
    if ($isGAAdmin || $isTeknisAdmin) {
        // 6 Cards
        $desktopGrid = 'lg:grid-cols-3 xl:grid-cols-6';
    } else {
        // 5 Cards
        $desktopGrid = 'lg:grid-cols-3 xl:grid-cols-5';
    }
@endphp

{{-- 
    GRID RESPONSIVE:
    - grid-cols-2 : Default Mobile (iPhone) agar hemat tempat
    - md:grid-cols-3 : Tablet
    - lg/xl : Sesuai logic PHP di atas
--}}
<div class="grid grid-cols-2 md:grid-cols-3 {{ $desktopGrid }} gap-3 md:gap-6 mb-6 md:mb-10">

    @php
        $cards = [
            [
                'title' => 'Total', // Teks dipendekkan untuk Mobile
                'full_title' => 'Total Tiket',
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

        // LOGIC KARTU (Sama seperti sebelumnya)
        if ($isGAAdmin || $isTeknisAdmin) {
            $cards[] = [
                'title' => 'Pending',
                'full_title' => 'Pending',
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
            $cards[] = [
                'title' => 'Wait SPV',
                'full_title' => 'Waiting Approval SPV',
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

        if ($isGAAdmin) {
            $cards[] = [
                'title' => 'Wait GA',
                'full_title' => 'Waiting Approval GA',
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
            $cards[] = [
                'title' => 'Wait SPV',
                'full_title' => 'Waiting Approval SPV',
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

        $cards[] = [
            'title' => 'Progress',
            'full_title' => 'On Progress',
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
            'full_title' => 'Rejected',
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
            'full_title' => 'Selesai',
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
        {{-- Card Container --}}
        <div
            class="status-card relative {{ $card['bg'] }} border-none rounded-xl md:rounded-2xl shadow-md md:shadow-lg hover:shadow-xl overflow-hidden group hover:-translate-y-1 md:hover:-translate-y-2 transition-all duration-300 border {{ $card['border'] }}">

            {{-- Gradient Background Accent (Responsive Size) --}}
            <div
                class="absolute top-0 right-0 w-20 h-20 md:w-32 md:h-32 {{ $card['accent'] }} rounded-full -mr-10 -mt-10 group-hover:scale-150 transition-transform duration-500">
            </div>

            {{-- Icon Pattern Background (Sembunyikan di HP biar ga penuh, Muncul di Tablet+) --}}
            <div
                class="hidden md:block absolute top-0 right-0 p-5 opacity-10 group-hover:opacity-20 group-hover:scale-110 transition-all duration-500">
                <svg class="w-28 h-28 {{ $card['icon_color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="{{ $card['icon_path'] }}" />
                </svg>
            </div>

            {{-- Content Wrapper (Padding Kecil di HP, Besar di PC) --}}
            <div class="p-4 md:p-7 relative z-10">

                {{-- Header Card --}}
                <div class="flex items-start md:items-center justify-between mb-2 md:mb-5">
                    {{-- Title (Responsive Font) --}}
                    <h3
                        class="text-[10px] md:text-xs font-bold {{ $card['text_color'] }} uppercase tracking-widest pl-2 md:pl-3 border-l-2 md:border-l-4 {{ in_array($card['color'], ['white', 'gray']) ? 'border-gray-500' : 'border-white/70' }} truncate max-w-[80%]">
                        <span class="md:hidden">{{ $card['title'] }}</span> {{-- Short Title di HP --}}
                        <span class="hidden md:inline">{{ $card['full_title'] }}</span> {{-- Full Title di PC --}}
                    </h3>

                    {{-- Icon Box (Kecil di HP, Besar di PC) --}}
                    <div
                        class="w-8 h-8 md:w-12 md:h-12 rounded-lg md:rounded-xl {{ $card['icon_bg'] }} flex items-center justify-center group-hover:scale-110 group-hover:rotate-6 transition-all duration-300 backdrop-blur-sm">
                        <svg class="w-4 h-4 md:w-6 md:h-6 {{ $card['icon_color'] }}" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="{{ $card['icon_path'] }}" />
                        </svg>
                    </div>
                </div>

                {{-- Value Number --}}
                <div class="flex items-baseline mt-1 md:mt-0">
                    <span
                        class="text-3xl md:text-5xl font-black {{ $card['text_color'] }} tracking-tight drop-shadow-sm">
                        {{ $card['value'] }}
                    </span>
                    <span
                        class="ml-1 md:ml-3 text-[10px] md:text-sm font-semibold {{ $card['text_color'] }} opacity-80">
                        Tiket
                    </span>
                </div>

                {{-- Badge 'Perlu Tindakan' (Hanya muncul jika value > 0 dan perlu approval) --}}
                @if (isset($card['show_approval']) && $card['show_approval'] && $card['value'] > 0)
                    <div class="mt-2 md:mt-3 pt-2 md:pt-3 border-t border-white/20">
                        <span
                            class="inline-flex items-center gap-1 px-2 py-0.5 md:px-2.5 md:py-1 rounded-full bg-white/20 backdrop-blur-sm text-[8px] md:text-[10px] font-bold {{ $card['text_color'] }} uppercase tracking-wide animate-pulse">
                            <svg class="w-2.5 h-2.5 md:w-3 md:h-3" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            Action
                        </span>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</div>
