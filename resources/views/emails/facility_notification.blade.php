<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            line-height: 1.6;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .wrapper {
            width: 100%;
            padding: 20px 0;
        }

        .box {
            background: #fff;
            padding: 0;
            border-radius: 8px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            padding: 20px;
            text-align: center;
            color: #fff;
            font-weight: bold;
            font-size: 18px;
            letter-spacing: 1px;
        }

        .header-blue {
            background: #3b82f6;
        }

        /* FACILITY COLOR */
        .header-green {
            background: #10b981;
        }

        .header-red {
            background: #ef4444;
        }

        .header-orange {
            background: #f59e0b;
        }

        .content {
            padding: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 14px;
        }

        td {
            padding: 12px 8px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            color: #555;
            width: 35%;
        }

        .val {
            color: #000;
            font-weight: 500;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .footer {
            background: #f9fafb;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #888;
            border-top: 1px solid #eee;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3b82f6;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin-top: 20px;
        }

        .btn:hover {
            background-color: #2563eb;
        }
    </style>
</head>

<body>
    @php
        // LOGIKA WARNA HEADER
        $headerClass = 'header-blue';
        if ($type == 'need_approval') {
            $headerClass = 'header-orange';
        }
        if ($type == 'fh_new') {
            // Tiket masuk ke Facility Admin
            $headerClass = 'header-blue';
        }
        if ($ticket->status == 'completed' || $ticket->status == 'pending') {
            $headerClass = 'header-green';
        }
        if (in_array($ticket->status, ['rejected', 'cancelled'])) {
            $headerClass = 'header-red';
        }

        $statusLabel = ucwords(str_replace('_', ' ', $ticket->status));
    @endphp

    <div class="wrapper">
        <div class="box">
            <div class="header {{ $headerClass }}">
                @if ($type == 'need_approval')
                    PERMINTAAN APPROVAL FACILITY
                @elseif($type == 'fh_new')
                    TIKET BARU MASUK (FACILITY)
                @else
                    NOTIFIKASI FACILITY WORK ORDER
                @endif
            </div>

            <div class="content">
                {{-- PESAN PEMBUKA --}}
                @if ($type == 'need_approval')
                    <p>Halo Bapak/Ibu Pimpinan,</p>
                    <p>Terdapat permintaan WO Facility baru yang <strong>membutuhkan persetujuan</strong> Anda.</p>
                @elseif($type == 'fh_new')
                    <p>Halo Admin Facility,</p>
                    <p>Terdapat tiket baru yang telah disetujui Manager dan <strong>siap untuk diproses</strong>.</p>
                @elseif($type == 'created_info')
                    <p>Halo <strong>{{ $ticket->requester_name }}</strong>,</p>
                    <p>Tiket permintaan Anda berhasil dibuat dan sedang menunggu persetujuan atasan.</p>
                @else
                    <p>Halo <strong>{{ $ticket->requester_name }}</strong>,</p>
                    <p>Berikut adalah update status terbaru tiket Anda:</p>
                @endif

                <table>
                    <tr>
                        <td class="label">Nomor Tiket</td>
                        <td class="val" style="font-family: monospace; font-size: 16px;">{{ $ticket->ticket_num }}</td>
                    </tr>
                    <tr>
                        <td class="label">Pelapor</td>
                        <td class="val">{{ $ticket->requester_name }}</td>
                    </tr>
                    <tr>
                        <td class="label">Divisi/Plant</td>
                        <td class="val">{{ $ticket->plant }}</td>
                    </tr>
                    <tr>
                        <td class="label">Kategori</td>
                        <td class="val">{{ $ticket->category }}</td>
                    </tr>
                    <tr>
                        <td class="label">Deskripsi</td>
                        <td class="val">{{ $ticket->description }}</td>
                    </tr>
                    <tr>
                        <td class="label">Status</td>
                        <td class="val">
                            <span class="status-badge" style="background: #eee;">{{ $statusLabel }}</span>
                        </td>
                    </tr>
                </table>

                <div style="text-align: center;">
                    <a href="{{ route('fh.index') }}" class="btn">
                        @if ($type == 'need_approval')
                            Login untuk Approve
                        @elseif($type == 'fh_new')
                            Login untuk Proses
                        @else
                            Lihat Detail Tiket
                        @endif
                    </a>
                </div>
            </div>

            <div class="footer">
                &copy; {{ date('Y') }} PT Jembo Cable Company Tbk.<br>
                Sistem Otomatis Facility Work Order
            </div>
        </div>
    </div>
</body>

</html>
