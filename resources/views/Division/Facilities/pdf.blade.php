<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Work Order - {{ $ticket->ticket_num }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #334155;
            line-height: 1.5;
            font-size: 13px;
            margin: 0;
            padding: 0;
        }

        .container {
            padding: 20px 30px;
        }

        /* HEADER */
        .header {
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .title {
            font-size: 22px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 5px 0;
        }

        .subtitle {
            font-size: 14px;
            color: #64748b;
            margin: 0;
        }

        /* BADGES */
        .badge-container {
            margin-bottom: 20px;
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin-right: 10px;
        }

        .badge-status {
            background-color: #dbeafe;
            color: #1d4ed8;
        }

        /* Biru default */
        .badge-status.completed {
            background-color: #dcfce3;
            color: #15803d;
        }

        .badge-status.waiting {
            background-color: #fef9c3;
            color: #a16207;
        }

        .badge-status.rejected {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        .badge-plant {
            background-color: #f1f5f9;
            color: #475569;
        }

        /* GRID INFO BOX (Mewakili bg-slate-50) */
        .info-box {
            background-color: #f8fafc;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            width: 100%;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 8px 5px;
            width: 50%;
            vertical-align: top;
        }

        .info-label {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: 14px;
            font-weight: bold;
            color: #1e293b;
            margin: 0;
        }

        /* SECTION HEADERS */
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            margin: 20px 0 10px 0;
        }

        /* BOX CONTENT (Description, Technicians, Rejection) */
        .content-box {
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            color: #475569;
        }

        .content-box-blue {
            border: 1px solid #dbeafe;
            background-color: #eff6ff;
        }

        .content-box-red {
            border: 1px solid #fecaca;
            background-color: #fef2f2;
            color: #b91c1c;
        }

        .tech-item {
            margin-bottom: 5px;
            font-weight: bold;
            color: #1e40af;
        }

        /* MACHINE DETAIL */
        .machine-box {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            padding: 12px 15px;
            border-radius: 8px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        /* ATTACHMENT (Full Size) */
        .attachment-container {
            margin-top: 10px;
            text-align: center;
        }

        .attachment-img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
        }
    </style>
</head>

<body>

    <div class="container">
        {{-- Header --}}
        <div class="header">
            <h1 class="title">Detail Work Order</h1>
            <p class="subtitle">{{ $ticket->ticket_num ?? '-' }}</p>
        </div>

        {{-- Badges --}}
        @php
            $statusClass = 'badge-status';
            if (str_contains($ticket->status, 'waiting')) {
                $statusClass .= ' waiting';
            } elseif ($ticket->status == 'completed') {
                $statusClass .= ' completed';
            } elseif ($ticket->status == 'rejected') {
                $statusClass .= ' rejected';
            }
        @endphp

        <div class="badge-container">
            <span class="badge {{ $statusClass }}">
                {{ str_replace('_', ' ', $ticket->status) }}
            </span>
            <span class="badge badge-plant">{{ $ticket->plant ?? '-' }}</span>
        </div>

        {{-- Grid Info --}}
        <div class="info-box">
            <table class="info-table">
                <tr>
                    <td>
                        <div class="info-label">Requester</div>
                        <div class="info-value">{{ $ticket->requester_name }}</div>
                    </td>
                    <td>
                        <div class="info-label">Category</div>
                        <div class="info-value">{{ $ticket->category }}</div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="info-label">Date Created</div>
                        <div class="info-value">{{ \Carbon\Carbon::parse($ticket->created_at)->format('d/m/Y') }}</div>
                    </td>
                    <td>
                        <div class="info-label">Target Date</div>
                        <div class="info-value">
                            {{ $ticket->target_completion_date ? \Carbon\Carbon::parse($ticket->target_completion_date)->format('d/m/Y') : '-' }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Description --}}
        <div class="section-title">Description</div>
        <div class="content-box">
            {!! nl2br(e($ticket->description)) !!}
        </div>

        {{-- Technicians --}}
        @if ($ticket->technicians && $ticket->technicians->count() > 0)
            <div class="section-title">Dikerjakan Oleh</div>
            <div class="content-box content-box-blue">
                @foreach ($ticket->technicians as $tech)
                    <div class="tech-item">• {{ $tech->name }}</div>
                @endforeach
            </div>
        @endif

        {{-- Rejection Reason --}}
        @if ($ticket->status === 'rejected' && $ticket->rejection_reason)
            <div class="section-title" style="color: #b91c1c;">Alasan Penolakan</div>
            <div class="content-box content-box-red">
                {!! nl2br(e($ticket->rejection_reason)) !!}
            </div>
        @endif

        {{-- Machine Detail --}}
        @if ($ticket->machine_id || $ticket->new_machine_name)
            <div class="section-title">Machine Detail</div>
            <div class="machine-box">
                ⚙️ {{ $ticket->new_machine_name ?: 'Machine Name: ' . $ticket->machine_name }}
            </div>
        @endif

        {{-- Attachment --}}
        @if ($ticket->photo_path)
            @php
                $ext = strtolower(pathinfo($ticket->photo_path, PATHINFO_EXTENSION));
                $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);
            @endphp

            <div class="section-title">Attachment</div>

            @if ($isImage)
                {{-- PENTING: Untuk PDF, pastikan menggunakan path absolut server menggunakan public_path() --}}
                <div class="attachment-container">
                    <img src="{{ public_path('storage/' . $ticket->photo_path) }}" class="attachment-img"
                        alt="Attachment">
                </div>
            @else
                <div class="content-box">
                    Lampiran berupa file <b>{{ strtoupper($ext) }}</b>. Silakan lihat langsung melalui sistem aplikasi
                    untuk mengunduh dokumen ini.
                </div>
            @endif
        @endif

    </div>

</body>

</html>
