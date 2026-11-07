<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Laporan Penggunaan Ruangan</title>
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Laporan Penggunaan</x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style>
        body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; }
        table { border-collapse: collapse; width: 100%; }
        th { background-color: #0f172a; color: #ffffff; font-weight: bold; border: 1px solid #334155; padding: 8px 6px; text-align: center; }
        td { border: 1px solid #cbd5e1; padding: 6px 8px; vertical-align: middle; }
        .text-center { text-align: center; }
        .title-main { font-size: 16pt; font-weight: bold; color: #0f172a; text-align: center; }
        .title-sub { font-size: 11pt; color: #475569; text-align: center; margin-bottom: 12px; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="9" class="title-main">LAPORAN PENGGUNAAN RUANGAN &amp; INFRASTRUKTUR</td>
        </tr>
        <tr>
            <td colspan="9" class="title-sub">SMK NEGERI 1 BANTUL &bull; Periode: {{ $period }}</td>
        </tr>
        <tr>
            <td colspan="9"></td>
        </tr>
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th style="width: 110px;">Tanggal</th>
                <th style="width: 100px;">Jam</th>
                <th style="width: 160px;">Ruangan</th>
                <th style="width: 220px;">Nama Agenda</th>
                <th style="width: 160px;">Peminjam</th>
                <th style="width: 100px;">Peran</th>
                <th style="width: 110px;">Status Persetujuan</th>
                <th style="width: 130px;">Status Terlaksana</th>
            </tr>
        </thead>
        <tbody>
            @forelse($events as $index => $event)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($event->date)->format('d/m/Y') }}</td>
                    <td class="text-center">{{ substr($event->start_time, 0, 5) }} - {{ substr($event->end_time, 0, 5) }}</td>
                    <td>{{ $event->room->name }}</td>
                    <td>{{ $event->title }}</td>
                    <td>{{ $event->borrower_name ?: $event->user->name }}</td>
                    <td class="text-center">{{ match($event->user->role?->name) {'guru' => 'Guru', 'organisasi' => 'Organisasi', 'admin' => 'Admin', default => 'Pengguna'} }}</td>
                    <td class="text-center" style="font-weight: bold; color: {{ $event->status === 'approved' ? '#166534' : ($event->status === 'rejected' ? '#991b1b' : '#854d0e') }};">
                        {{ match($event->status) {'approved' => 'Disetujui', 'rejected' => 'Ditolak', default => 'Pending'} }}
                    </td>
                    <td class="text-center">
                        {{ $event->executionStatus() }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 20px; color: #64748b;">
                        Tidak ada data agenda pada periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

