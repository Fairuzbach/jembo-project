<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f4f7f6;
            color: #334155;
        }

        .email-wrapper {
            width: 100%;
            padding: 20px 0;
        }

        .email-content {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .header {
            background: #1e293b;
            padding: 20px;
            text-align: center;
            color: #fff;
        }

        .body {
            padding: 25px;
        }

        .periode-box {
            background: #f1f5f9;
            padding: 10px;
            border-radius: 6px;
            text-align: center;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .main-stats {
            width: 100%;
            margin-bottom: 25px;
            border-top: 4px solid {{ $dataLaporan['theme_color'] }};
            background: #f8fafc;
        }

        .main-stats td {
            padding: 15px;
            text-align: center;
        }

        .stat-val {
            display: block;
            font-size: 22px;
            font-weight: bold;
        }

        .stat-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #64748b;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin: 20px 0 10px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px;
            color: {{ $dataLaporan['theme_color'] }};
        }

        .list-item {
            font-size: 13px;
            padding: 5px 0;
            display: flex;
            justify-content: space-between;
            border-bottom: 1px dashed #f1f5f9;
        }

        .badge {
            background: #e2e8f0;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            float: right;
        }

        .footer {
            background: #f8fafc;
            padding: 15px;
            text-align: center;
            font-size: 11px;
            color: #94a3b8;
        }
    </style>
</head>

<body>
    <div class="email-wrapper">
        <div class="email-content">
            <div class="header">
                <h2 style="margin:0">{{ strtoupper($dataLaporan['tipe_laporan']) }} REPORT</h2>
                <p style="margin:5px 0 0; font-size:12px; opacity:0.8">{{ $dataLaporan['departemen'] }}</p>
            </div>

            <div class="body">
                <p>Halo <strong>{{ $namaPimpinan }}</strong>,</p>
                <div class="periode-box">Periode: {{ $dataLaporan['periode'] }}</div>

                <table class="main-stats">
                    <tr>
                        <td><span class="stat-val">{{ $dataLaporan['total'] }}</span><span class="stat-label">Total
                                Tiket</span></td>
                        <td><span class="stat-val" style="color:#10b981">{{ $dataLaporan['selesai'] }}</span><span
                                class="stat-label">Selesai</span></td>
                        <td><span class="stat-val"
                                style="color:#f59e0b">{{ $dataLaporan['total'] - $dataLaporan['selesai'] }}</span><span
                                class="stat-label">Pending</span></td>
                    </tr>
                </table>

                @if ($dataLaporan['is_ga'])
                    <div class="section-title">📊 PEMBAGIAN TUGAS GA</div>
                    <div class="list-item">Internal Logbook (GA Team) <span
                            class="badge">{{ $dataLaporan['stats']['internal_count'] }} Tiket</span></div>
                    <div class="list-item">Request Divisi Lain (External) <span
                            class="badge">{{ $dataLaporan['stats']['external_count'] }} Tiket</span></div>

                    <div class="section-title">🏢 REQUESTER TERAKTIF (EKSTERNAL)</div>
                    @foreach ($dataLaporan['stats']['top_depts'] as $dept => $count)
                        <div class="list-item">{{ $dept }} <span class="badge">{{ $count }}
                                Request</span></div>
                    @endforeach
                @else
                    <div class="section-title">🔧 TOP KATEGORI PERBAIKAN</div>
                    @foreach ($dataLaporan['stats']['top_categories'] as $cat => $count)
                        <div class="list-item">{{ $cat }} <span class="badge">{{ $count }}
                                Kali</span></div>
                    @endforeach

                    <div class="section-title">🏭 MESIN PALING SERING REQUEST</div>
                    @foreach ($dataLaporan['stats']['top_machines'] as $machine => $count)
                        <div class="list-item">{{ $machine }} <span class="badge">{{ $count }}
                                Kali</span></div>
                    @endforeach
                    <div class="section-title">👷 KINERJA TEKNISI (TOP 5)</div>
                    @if (count($dataLaporan['stats']['top_technicians']) > 0)
                        @foreach ($dataLaporan['stats']['top_technicians'] as $name => $count)
                            <div class="list-item">{{ $name }} <span class="badge">{{ $count }}
                                    Pekerjaan</span></div>
                        @endforeach
                    @else
                        <div class="list-item" style="color: #94a3b8; font-style: italic;">Belum ada teknisi yang
                            ditugaskan.</div>
                    @endif
                    <div class="section-title">🏢 DEPARTEMEN REQUESTER TERBANYAK</div>
                    @foreach ($dataLaporan['stats']['top_depts'] as $dept => $count)
                        <div class="list-item">{{ $dept }} <span class="badge">{{ $count }}
                                Request</span></div>
                    @endforeach
                @endif

                <div style="text-align:center; margin-top:30px">
                    <a href="{{ url('/') }}"
                        style="background:{{ $dataLaporan['theme_color'] }}; color:#fff; padding:10px 20px; text-decoration:none; border-radius:5px; font-weight:bold; font-size:13px">Buka
                        Dashboard Sistem</a>
                </div>
            </div>

            <div class="footer">
                PT Jembo Cable Company Tbk.<br>Sistem Digital Work Order - Laporan Otomatis
            </div>
        </div>
    </div>
</body>

</html>
