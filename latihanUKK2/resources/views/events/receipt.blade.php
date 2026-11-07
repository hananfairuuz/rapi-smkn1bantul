<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Surat Izin Penggunaan Ruangan #{{ $event->id }} - SMKN 1 Bantul</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: "Times New Roman", Times, serif;
            color: #111827;
            background: #f1f5f9;
            margin: 0;
            padding: 24px;
            font-size: 14px;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 40px 48px;
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        }
        .no-print-toolbar {
            max-width: 800px;
            margin: 0 auto 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            font-family: system-ui, -apple-system, sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13.5px;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid #d1d5db;
            background: #fff;
            color: #374151;
        }
        .btn-primary {
            background: #2563eb;
            color: #fff;
            border-color: #1d4ed8;
        }
        .btn-primary:hover { background: #1d4ed8; }
        .kop-surat {
            display: flex;
            align-items: center;
            gap: 20px;
            border-bottom: 3px double #111827;
            padding-bottom: 14px;
            margin-bottom: 24px;
        }
        .kop-logo {
            width: 78px;
            height: 78px;
            object-fit: contain;
        }
        .kop-text {
            text-align: center;
            flex: 1;
        }
        .kop-text h4 {
            margin: 0;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: .05em;
            text-transform: uppercase;
        }
        .kop-text h2 {
            margin: 2px 0;
            font-size: 20px;
            font-weight: 800;
            letter-spacing: .03em;
        }
        .kop-text p {
            margin: 0;
            font-size: 11.5px;
            color: #374151;
        }
        .doc-title {
            text-align: center;
            margin-bottom: 24px;
        }
        .doc-title h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 800;
            text-decoration: underline;
            letter-spacing: .04em;
        }
        .doc-title p {
            margin: 3px 0 0;
            font-size: 12px;
            color: #4b5563;
        }
        table.info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 18px 0;
        }
        table.info-table td {
            padding: 5px 4px;
            vertical-align: top;
        }
        table.info-table td.label {
            width: 220px;
            color: #374151;
        }
        table.info-table td.colon {
            width: 16px;
            text-align: center;
        }
        table.info-table td.val {
            font-weight: 700;
            color: #111827;
        }
        .badge-approved {
            display: inline-block;
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
            font-family: system-ui, sans-serif;
            font-size: 11px;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 4px;
            letter-spacing: .05em;
        }
        .rules-box {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            padding: 12px 18px;
            border-radius: 6px;
            margin: 20px 0;
            font-size: 12px;
        }
        .rules-box h5 {
            margin: 0 0 6px;
            font-size: 12.5px;
        }
        .rules-box ol {
            margin: 0;
            padding-left: 20px;
        }
        .rules-box li {
            margin-bottom: 3px;
        }
        .signature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 36px;
            text-align: center;
        }
        .signature-box {
            padding: 10px;
        }
        .signature-box .date-line {
            font-size: 12.5px;
            margin-bottom: 8px;
        }
        .stamp-box {
            display: inline-block;
            border: 2px solid #16a34a;
            border-radius: 6px;
            padding: 6px 14px;
            color: #15803d;
            font-family: system-ui, sans-serif;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            margin: 10px 0;
            transform: rotate(-3deg);
        }
        .sign-name {
            font-weight: 700;
            text-decoration: underline;
            margin-top: 14px;
            font-size: 14px;
        }
        .qr-placeholder {
            margin-top: 10px;
            display: inline-block;
            padding: 6px 10px;
            background: #f3f4f6;
            border-radius: 4px;
            font-family: monospace;
            font-size: 11px;
            color: #4b5563;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .no-print-toolbar {
                display: none !important;
            }
            .container {
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="no-print-toolbar">
    <a href="{{ route('events.index') }}" class="btn">
        &larr; Kembali ke Daftar Agenda
    </a>
    <button onclick="window.print()" class="btn btn-primary">
        <svg style="width:16px;height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
        Cetak / Simpan PDF
    </button>
</div>

<div class="container">
    <div class="kop-surat">
        <img class="kop-logo" src="{{ asset('images/school-logo.png') }}" alt="Logo SMKN 1 Bantul">
        <div class="kop-text">
            <h4>Pemerintah Daerah Istimewa Yogyakarta</h4>
            <h4>Dinas Pendidikan, Pemuda, dan Olahraga</h4>
            <h2>SMK NEGERI 1 BANTUL</h2>
            <p>Jalan Bantul No. 45, Palbapang, Bantul, D.I. Yogyakarta 55711 • Telp: (0274) 367156</p>
            <p>Laman: rapi-smkn1bantul.web.id • Pos-el: info@smkn1bantul.sch.id</p>
        </div>
    </div>

    <div class="doc-title">
        <h3>SURAT IZIN PENGGUNAAN RUANGAN &amp; INFRASTRUKTUR</h3>
        <p>Nomor: {{ sprintf('%03d', $event->id) }}/IZIN-RAPI/SMK1BTL/{{ \Carbon\Carbon::parse($event->date)->format('m/Y') }}</p>
    </div>

    <p style="margin: 0 0 10px;">
        Berdasarkan verifikasi sistem informasi pengelolaan infrastruktur (<strong>RAPI-Sekolah</strong>), Waka Sarana Prasarana / Pengelola Ruangan memberikan izin penggunaan fasilitas kepada:
    </p>

    <table class="info-table">
        <tr>
            <td class="label">Nama Peminjam / Penanggung Jawab</td>
            <td class="colon">:</td>
            <td class="val">{{ $event->borrower_name ?: $event->user->name }}</td>
        </tr>
        <tr>
            <td class="label">Akun Pemohon &amp; Peran</td>
            <td class="colon">:</td>
            <td class="val">{{ $event->user->name }} ({{ match($event->user->role?->name) {'guru' => 'Guru', 'organisasi' => 'Organisasi Siswa', 'admin' => 'Admin / Waka', default => 'Pengguna'} }})</td>
        </tr>
        <tr>
            <td class="label">Nomor WhatsApp / Kontak</td>
            <td class="colon">:</td>
            <td class="val">{{ $event->borrower_whatsapp ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label">Nama Agenda / Kegiatan</td>
            <td class="colon">:</td>
            <td class="val">{{ $event->title }}</td>
        </tr>
        <tr>
            <td class="label">Ruangan yang Dipinjam</td>
            <td class="colon">:</td>
            <td class="val">{{ $event->room->name }} {{ $event->room->location ? '('.$event->room->location.')' : '' }}</td>
        </tr>
        <tr>
            <td class="label">Kapasitas Maksimal Ruangan</td>
            <td class="colon">:</td>
            <td class="val">{{ $event->room->capacity ? $event->room->capacity . ' Orang' : 'Sesuai ketentuan' }}</td>
        </tr>
        <tr>
            <td class="label">PIC Ruangan</td>
            <td class="colon">:</td>
            <td class="val">{{ $event->room->pic?->name ?? 'Pengelola Sarpras' }}</td>
        </tr>
        <tr>
            <td class="label">Hari &amp; Tanggal Pemakaian</td>
            <td class="colon">:</td>
            <td class="val">
                {{ \Carbon\Carbon::parse($event->date)->locale('id')->translatedFormat('l, d F Y') }}
                @if($event->isMultiDay())
                    s.d. {{ \Carbon\Carbon::parse($event->end_date)->locale('id')->translatedFormat('l, d F Y') }}
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Waktu / Jam Penggunaan</td>
            <td class="colon">:</td>
            <td class="val">{{ substr($event->start_time, 0, 5) }} – {{ substr($event->end_time, 0, 5) }} WIB {{ $event->endsNextDay() ? '(+1 Hari)' : '' }}</td>
        </tr>
        <tr>
            <td class="label">Status Pengajuan</td>
            <td class="colon">:</td>
            <td class="val"><span class="badge-approved">DISETUJUI (VALID)</span></td>
        </tr>
        @if($event->description)
            <tr>
                <td class="label">Deskripsi / Fasilitas Tambahan</td>
                <td class="colon">:</td>
                <td class="val" style="font-weight: normal; color: #374151;">{{ $event->description }}</td>
            </tr>
        @endif
        @if($event->returned_at)
            <tr>
                <td class="label">Status Pengembalian Ruangan</td>
                <td class="colon">:</td>
                <td class="val" style="color: #15803d;">
                    Sudah Dikembalikan pada {{ $event->returned_at->format('d/m/Y H:i') }} WIB
                    ({{ $event->checkout_condition === 'rusak' ? 'Ada Kerusakan Dilaporkan' : 'Kondisi Baik & Rapi' }})
                </td>
            </tr>
        @endif
    </table>

    <div class="rules-box">
        <h5>Ketentuan &amp; Tata Tertib Penggunaan Ruangan:</h5>
        <ol>
            <li>Surat izin ini sah dikeluarkan oleh Sistem RAPI-Sekolah dan wajib ditunjukkan kepada petugas/satpam saat mengambil kunci ruangan.</li>
            <li>Peminjam berkewajiban menjaga kebersihan, ketertiban, dan keutuhan seluruh sarana prasarana yang ada di dalam ruangan.</li>
            <li>Dilarang mengubah atau memindahkan instalasi listrik, kabel LAN, audio, atau PC tanpa seizin PIC ruangan.</li>
            <li>Setelah kegiatan selesai, pastikan seluruh AC, lampu, dan proyektor telah dimatikan, pintu dikunci, serta melakukan <strong>konfirmasi pengembalian (check-out)</strong> di sistem.</li>
            <li>Kerusakan atau kehilangan inventaris akibat kelalaian selama pemakaian menjadi tanggung jawab pihak peminjam.</li>
        </ol>
    </div>

    <div class="signature-grid">
        <div class="signature-box">
            <div class="date-line">&nbsp;</div>
            <div>Peminjam / Penanggung Jawab,</div>
            <div style="height: 60px;"></div>
            <div class="sign-name">{{ $event->borrower_name ?: $event->user->name }}</div>
            <div style="font-size: 12px; color: #4b5563;">NIP / NIS: -</div>
        </div>
        <div class="signature-box">
            <div class="date-line">Bantul, {{ \Carbon\Carbon::parse($event->updated_at)->locale('id')->translatedFormat('d F Y') }}</div>
            <div>Waka Sarana Prasarana / Pengelola,</div>
            <div style="height: 10px;"></div>
            <div><span class="stamp-box">DISETUJUI DIGITAL<br><small style="font-size:9px; letter-spacing:0;">RAPI-SMKN 1 BANTUL</small></span></div>
            <div class="sign-name">Tim Sarpras SMKN 1 Bantul</div>
            <div class="qr-placeholder">ID Verifikasi: RAPI-EV-{{ $event->id }}-{{ substr(md5($event->created_at), 0, 8) }}</div>
        </div>
    </div>
</div>

</body>
</html>

