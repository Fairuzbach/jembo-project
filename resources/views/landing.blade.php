<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>JEMBO Work Management</title>
    <link rel="icon" href="{{ asset('logo.webp') }}" type="image/webp">
    {{-- 1. Load Font Google (Inter) agar terlihat modern --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">


    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        /* Animasi Custom untuk Entrance (Fade In Up) */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes float {

            0%,
            100% {
                transform: translate(0, 0) scale(1);
            }

            33% {
                transform: translate(30px, -50px) scale(1.1);
            }

            66% {
                transform: translate(-20px, 20px) scale(0.9);
            }
        }

        @keyframes slideInCard {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes pulse-subtle {

            0%,
            100% {
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4);
            }

            50% {
                box-shadow: 0 0 0 8px rgba(59, 130, 246, 0);
            }
        }

        .animate-blob {
            animation: float 10s infinite ease-in-out;
        }

        .animation-delay-2000 {
            animation-delay: 2s;
        }

        .animation-delay-4000 {
            animation-delay: 4s;
        }

        /* 2. Animasi Teks Berkilau (Shimmer) */
        @keyframes shine {
            to {
                background-position: 200% center;
            }
        }

        .animate-text-shimmer {
            background-size: 200% auto;
            animation: shine 3s linear infinite;
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.8s ease-out forwards;
        }

        .animate-slide-in-card {
            animation: slideInCard 0.6s ease-out forwards;
        }

        .animate-pulse-subtle {
            animation: pulse-subtle 2s infinite;
        }

        /* Delay utility classes */
        .delay-100 {
            animation-delay: 0.1s;
        }

        .delay-200 {
            animation-delay: 0.2s;
        }

        .delay-300 {
            animation-delay: 0.3s;
        }

        .delay-400 {
            animation-delay: 0.4s;
        }

        .delay-500 {
            animation-delay: 0.5s;
        }

        .delay-600 {
            animation-delay: 0.6s;
        }

        /* Initial state hidden */
        .opacity-0-start {
            opacity: 0;
        }

        /* Card stagger animation */
        .card-item:nth-child(1) {
            animation: slideInCard 0.6s ease-out 0.1s forwards;
        }

        .card-item:nth-child(2) {
            animation: slideInCard 0.6s ease-out 0.2s forwards;
        }

        .card-item:nth-child(3) {
            animation: slideInCard 0.6s ease-out 0.3s forwards;
        }

        .card-item:nth-child(4) {
            animation: slideInCard 0.6s ease-out 0.4s forwards;
        }

        .opacity-0-start {
            opacity: 0;
        }

        /* Loading Spinner Animation */
        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        @keyframes fadeInScaleLoading {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .spinner {
            animation: spin 1s linear infinite;
        }

        .loading-overlay {
            animation: fadeInScaleLoading 0.3s ease-out;
        }

        .loading-text {
            animation: fadeInUp 0.6s ease-out 0.2s forwards;
            opacity: 0;
        }
    </style>
</head>

<body class="antialiased bg-slate-50 text-slate-900 font-sans selection:bg-indigo-500 selection:text-white">

    <x-navbar />
    <div class="min-h-screen relative overflow-hidden flex flex-col justify-center py-12 sm:py-24 pt-24">

        {{-- 3. Background Decoration (Blobs Cahaya) --}}
        <div
            class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-blue-400/20 rounded-full blur-[100px] pointer-events-none animate-blob">
        </div>
        <div
            class="absolute bottom-[-10%] right-[-10%] w-96 h-96 bg-purple-400/20 rounded-full blur-[100px] pointer-events-none animate-blob animation-delay-2000">
        </div>
        <div
            class="absolute top-[20%] right-[10%] w-72 h-72 bg-emerald-400/20 rounded-full blur-[80px] pointer-events-none animate-blob animation-delay-4000">
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 w-full">

            {{-- 4. Header Section --}}
            <div class="text-center max-w-3xl mx-auto mb-16 animate-fade-in-up opacity-0-start">
                {{-- Judul Besar --}}
                <h1 class="text-4xl md:text-6xl font-extrabold tracking-tight text-slate-900 mb-6 leading-tight">
                    JEMBO Work <span
                        class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 via-indigo-500 to-blue-600 animate-text-shimmer">Management</span>
                </h1>
            </div>

            {{-- 5. Cards Grid (Data Divisi) --}}
            <div class="p-6">
                {{-- 1. Definisikan variable dengan nama $divisions --}}
                @php
                    // GANTI NAMA DARI $menuItems KE $divisions
                    $divisions = [
                        [
                            'id' => 'generalAffair',
                            'name' => 'General Affair',
                            'title' => 'Work Order General Affair',
                            'desc' => 'Layanan • Fasilitas • Operasional',
                            'badge' => 'GA',
                            'badge_color' => 'bg-purple-100 text-purple-700',
                            'icon' =>
                                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />',
                            'color' => 'from-purple-500 to-pink-400',
                            'color_light' => 'from-purple-50 to-pink-50',
                            'shadow' => 'shadow-purple-500/20',
                            'bg_hover' => 'group-hover:text-purple-600',

                            // Route Updated:
                            'btn_link' => route('ga.index'),
                        ],
                        [
                            'id' => 'engineering',
                            'name' => 'Engineering',
                            'title' => 'Engineering Improvement Order',
                            'desc' => 'Upgrade • Efisiensi • Optimasi',
                            'badge' => 'EIO',
                            'badge_color' => 'bg-orange-100 text-orange-700',
                            'icon' =>
                                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />',
                            'color' => 'from-orange-500 to-red-400',
                            'color_light' => 'from-orange-50 to-red-50',
                            'shadow' => 'shadow-orange-500/20',
                            'bg_hover' => 'group-hover:text-orange-600',

                            // Route Updated:
                            'btn_link' => route('eng.index'),
                        ],
                        [
                            'id' => 'facility',
                            'name' => 'Facility',
                            'title' => 'Work Order Facility',
                            'desc' => 'Instalasi • Modifikasi • Fabrikasi',
                            'badge' => 'FH',
                            'badge_color' => 'bg-emerald-100 text-emerald-700',

                            // Icon Fixed (Closed Tag):
                            'icon' =>
                                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />',

                            'color' => 'from-emerald-500 to-green-400',
                            'color_light' => 'from-emerald-50 to-green-50',
                            'shadow' => 'shadow-emerald-500/20',
                            'bg_hover' => 'group-hover:text-emerald-600',

                            // Route Updated:
                            'btn_link' => route('fh.index'),
                        ],
                    ];
                @endphp

                {{-- UBAH DI SINI: Ganti grid menjadi flex, tambahkan justify-center --}}
                <div class="flex flex-wrap justify-center gap-6 mb-8 items-stretch">
                    @foreach ($divisions as $item)
                        {{-- Wrapper Alpine.js untuk Efek Tilt 3D --}}
                        {{-- UBAH DI SINI: Tambahkan w-full sm:w-80 agar ukuran card konsisten seperti grid --}}
                        <div x-data="{
                            transform: 'perspective(1000px) rotateX(0deg) rotateY(0deg) scale(1)',
                            handleMove(e) {
                                const el = this.$refs.card;
                                const { left, top, width, height } = el.getBoundingClientRect();
                                const x = e.clientX - left;
                                const y = e.clientY - top;
                        
                                // Hitung rotasi (maksimal 10 derajat)
                                const xPct = x / width - 0.5;
                                const yPct = y / height - 0.5;
                                const rotateY = xPct * 20;
                                const rotateX = yPct * -20;
                        
                                this.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.05)`;
                            },
                            handleLeave() {
                                this.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) scale(1)';
                            }
                        }" class="card-item h-full opacity-0-start w-full sm:w-72 lg:w-80">
                            {{-- <--- PERHATIKAN CLASS WIDTH INI --}}

                            <a href="{{ $item['btn_link'] }}"
                                @click="document.getElementById('loadingOverlay').classList.remove('hidden')"
                                class="group block h-full">

                                {{-- ... (Isi card biarkan sama seperti sebelumnya) ... --}}
                                <div x-ref="card" @mousemove="handleMove($event)" @mouseleave="handleLeave()"
                                    :style="`transform: ${transform}; transition: transform 0.1s ease-out;`"
                                    class="bg-gradient-to-br {{ $item['color_light'] }} rounded-2xl shadow-lg border-2 border-white p-6 h-full flex flex-col justify-between relative overflow-hidden z-10 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300">

                                    {{-- Overlay gradient background --}}
                                    <div
                                        class="absolute inset-0 bg-gradient-to-br {{ $item['color'] }} opacity-0 group-hover:opacity-5 transition-opacity duration-500 pointer-events-none">
                                    </div>

                                    {{-- Efek Kilau Putih (Glare) saat hover --}}
                                    <div
                                        class="absolute inset-0 bg-gradient-to-tr from-white/50 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none">
                                    </div>

                                    {{-- Badge dengan code divisi --}}
                                    <div class="flex items-start justify-between gap-4 relative z-20 mb-2">
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-lg {{ $item['badge_color'] }} text-xs font-bold uppercase tracking-wider">
                                            {{ $item['badge'] }}
                                        </span>
                                        <div class="flex-1"></div>
                                    </div>

                                    {{-- Icon lebih besar --}}
                                    <div
                                        class="w-16 h-16 rounded-xl bg-gradient-to-br {{ $item['color'] }} {{ $item['shadow'] }} text-white flex items-center justify-center flex-shrink-0 shadow-lg group-hover:scale-125 transition-transform duration-300 mb-4 relative z-20">
                                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            {!! $item['icon'] !!}
                                        </svg>
                                    </div>

                                    {{-- Text Section --}}
                                    <div class="w-full relative z-20 mb-4 flex-grow">
                                        <h3
                                            class="font-bold text-xl text-gray-800 mb-1 leading-tight {{ $item['bg_hover'] }} transition-colors">
                                            {{ $item['name'] }}
                                        </h3>
                                        <p class="text-sm text-gray-600 leading-relaxed font-medium">
                                            {{ $item['title'] }}
                                        </p>
                                        <p class="text-xs text-gray-500 leading-relaxed mt-2">
                                            {{ $item['desc'] }}
                                        </p>
                                    </div>

                                    {{-- Action Button --}}
                                    <div
                                        class="flex items-center justify-between mt-4 relative z-20 pt-4 border-t border-gray-200/50 group-hover:border-gray-300/70 transition-colors">
                                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Buka
                                            Sekarang</span>
                                        <div
                                            class="w-8 h-8 rounded-full bg-gradient-to-br {{ $item['color'] }} text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-x-2 group-hover:translate-x-0 shadow-md">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>



        {{-- 6. Footer Note --}}
        <div class="text-center mt-20 text-slate-400 text-sm animate-fade-in-up opacity-0-start delay-300">
            <p>&copy; {{ date('Y') }} Fairuz Bachri. All rights reserved. </p>
        </div>

    </div>
    </div>

    {{-- Loading Overlay --}}
    <div id="loadingOverlay"
        class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center loading-overlay">
        <div class="bg-white rounded-3xl shadow-2xl p-8 flex flex-col items-center gap-6">
            {{-- Spinner --}}
            <div class="relative w-20 h-20">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-500 to-indigo-500 rounded-full opacity-20">
                </div>
                <svg class="w-20 h-20 text-blue-600 spinner" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
            </div>

            {{-- Loading Text --}}
            <div class="text-center loading-text">
                <h3 class="text-lg font-bold text-gray-800 mb-1">Membuka Work Order</h3>
                <p class="text-sm text-gray-500">Mohon tunggu sebentar...</p>
            </div>
        </div>
    </div>
</body>

</html>
