<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Laporan Rekap Barang Rusak</title>
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Barang Rusak</x:Name>
                    <x:WorksheetOptions>
                        @php echo '<' . 'x:DisplayGridlines/>'; @endphp
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
            <td colspan="8" class="title-main">REKAP LAPORAN KERUSAKAN FASILITAS &amp; BARANG RUSAK</td>
        </tr>
        <tr>
            <td colspan="8" class="title-sub">SMK NEGERI 1 BANTUL &bull; Periode: {{ $period }}</td>
        </tr>
        <tr>
            <td colspan="8"></td>
        </tr>
        <thead>
            <tr>
                <th style="width: 40px;">No</th>
                <th style="width: 120px;">Tanggal Lapor</th>
                <th style="width: 160px;">Ruangan</th>
                <th style="width: 160px;">Fasilitas / Barang</th>
                <th style="width: 240px;">Deskripsi Kerusakan</th>
                <th style="width: 150px;">Pelapor</th>
                <th style="width: 130px;">Status Verifikasi</th>
                <th style="width: 140px;">Status Perbaikan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($complaints as $index => $complaint)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $complaint->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $complaint->room->name }}</td>
                    <td><strong>{{ $complaint->facility_name }}</strong></td>
                    <td>{{ $complaint->description }}</td>
                    <td>{{ $complaint->reporter?->name ?? 'Anonim' }}</td>
                    <td class="text-center" style="font-weight: bold; color: {{ $complaint->approval_status === 'approved' ? '#166534' : ($complaint->approval_status === 'rejected' ? '#991b1b' : '#854d0e') }};">
                        {{ match($complaint->approval_status) {'approved' => 'Disetujui', 'rejected' => 'Ditolak', default => 'Pending'} }}
                    </td>
                    <td class="text-center" style="font-weight: bold; color: {{ $complaint->status_repair === 'Sudah Diperbaiki' ? '#166534' : ($complaint->status_repair === 'Menunggu Spare Part' ? '#d97706' : '#dc2626') }};">
                        {{ $complaint->status_repair }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px; color: #64748b;">
                        Tidak ada laporan kerusakan pada periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

