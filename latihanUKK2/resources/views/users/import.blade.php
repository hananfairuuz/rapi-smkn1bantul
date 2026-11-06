@extends('layouts.app')

@section('title', 'Import Pengguna dari Dokumen - RAPI-Sekolah')

@section('content')
<div class="page-heading">
    <div>
        <a href="{{ route('users.index') }}" class="back-link" style="text-decoration:none;font-weight:600;font-size:0.9rem;display:inline-flex;align-items:center;gap:6px;margin-bottom:6px;color:#d97706;">
            ← Kembali ke Daftar Pengguna
        </a>
        <h1>Import Pengguna dari Dokumen</h1>
        <p class="muted">Unggah data pengguna dari file Excel (.xlsx), Word (.docx), atau CSV (.csv). Data dapat ditinjau dan dilengkapi sebelum disimpan ke sistem.</p>
    </div>
</div>

@if($errors->has('import') || $errors->has('file'))
    <div class="card" style="margin-bottom:1.25rem;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:1rem 1.25rem;border-radius:10px;">
        <strong style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
            ⚠️ Perhatian Terdapat Kesalahan:
        </strong>
        <div style="font-size:0.92rem;line-height:1.5;">
            {!! $errors->first('import') ?: $errors->first('file') !!}
        </div>
    </div>
@endif

<!-- Step 1: Upload & Format Card -->
<div class="card" style="margin-bottom:1.5rem;padding:1.5rem;border-radius:12px;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1.25rem;margin-bottom:1.25rem;">
        <div>
            <h2 style="font-size:1.15rem;margin:0 0 4px 0;display:flex;align-items:center;gap:8px;">
                <span style="display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;background:#d97706;color:#fff;font-size:0.85rem;font-weight:bold;">1</span>
                Unggah File Dokumen
            </h2>
            <p class="muted" style="margin:0;font-size:0.9rem;">
                Format yang didukung: <strong>Excel (.xlsx)</strong>, <strong>Word (.docx)</strong>, atau <strong>CSV (.csv)</strong>.
            </p>
        </div>

        <!-- Download Template Options -->
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <span style="font-size:0.85rem;font-weight:600;color:#64748b;">Unduh Template:</span>
            <a href="{{ route('users.import.template', 'xlsx') }}" class="btn secondary" style="padding:6px 12px;font-size:0.82rem;display:inline-flex;align-items:center;gap:6px;" title="Unduh contoh template Excel">
                📊 Excel (.xlsx)
            </a>
            <a href="{{ route('users.import.template', 'docx') }}" class="btn secondary" style="padding:6px 12px;font-size:0.82rem;display:inline-flex;align-items:center;gap:6px;" title="Unduh contoh template Word">
                📝 Word (.docx)
            </a>
            <a href="{{ route('users.import.template', 'csv') }}" class="btn secondary" style="padding:6px 12px;font-size:0.82rem;display:inline-flex;align-items:center;gap:6px;" title="Unduh contoh template CSV">
                📄 CSV (.csv)
            </a>
        </div>
    </div>

    <!-- Dropzone Area -->
    <div id="dropzone" style="border:2px dashed #cbd5e1;border-radius:12px;padding:2rem 1.5rem;text-align:center;background:#f8fafc;cursor:pointer;transition:all 0.2s ease;">
        <input type="file" id="fileInput" accept=".xlsx,.docx,.csv,.txt" style="display:none;">
        <div style="font-size:2.5rem;margin-bottom:0.5rem;line-height:1;">📁</div>
        <p style="margin:0 0 6px 0;font-weight:600;color:#1e293b;font-size:1rem;">
            Klik untuk memilih file dokumen, atau seret &amp; lepas file ke sini
        </p>
        <p class="muted" style="margin:0 0 1rem 0;font-size:0.85rem;">
            Maksimal ukuran file 5 MB. Sistem akan otomatis mendeteksi kolom Nama, Email, Sandi, WhatsApp, dan Peran.
        </p>
        <button type="button" class="btn" style="padding:8px 18px;font-size:0.9rem;" onclick="document.getElementById('fileInput').click()">
            Pilih File Dokumen
        </button>
        <div id="selectedFileInfo" style="display:none;margin-top:1rem;padding:0.6rem 1rem;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:8px;color:#065f46;font-size:0.88rem;display:none;align-items:center;justify-content:center;gap:8px;">
            <span>📄</span>
            <span id="fileName" style="font-weight:600;"></span>
            <span id="fileSize" style="opacity:0.8;font-size:0.8rem;"></span>
            <button type="button" id="btnProcessFile" class="btn" style="margin-left:12px;padding:4px 12px;font-size:0.82rem;background:#059669;border-color:#059669;">
                🚀 Baca Dokumen Ini
            </button>
        </div>
    </div>

    <div id="loadingIndicator" style="display:none;margin-top:1.25rem;text-align:center;padding:1.5rem;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;">
        <div class="spinner" style="display:inline-block;width:32px;height:32px;border:3px solid #cbd5e1;border-top-color:#d97706;border-radius:50%;animation:spin 0.8s linear infinite;"></div>
        <p style="margin:0.75rem 0 0 0;font-weight:600;color:#475569;font-size:0.9rem;">
            Sedang membaca &amp; memetakan isi dokumen...
        </p>
    </div>
</div>

<!-- Step 2: Interactive Staging & Bulk Configuration Table -->
<div id="stagingCard" class="card" style="display:none;padding:1.5rem;border-radius:12px;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;margin-bottom:1.25rem;border-bottom:1px solid #e2e8f0;padding-bottom:1rem;">
        <div>
            <h2 style="font-size:1.15rem;margin:0 0 4px 0;display:flex;align-items:center;gap:8px;">
                <span style="display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;background:#059669;color:#fff;font-size:0.85rem;font-weight:bold;">2</span>
                Pratinjau &amp; Lengkapi Data Pengguna
            </h2>
            <p class="muted" style="margin:0;font-size:0.9rem;">
                Ditemukan <strong id="totalRowsCount" style="color:#0f172a;">0</strong> baris data. Kolom dengan tanda kuning <span style="background:#fef08a;padding:1px 6px;border-radius:4px;color:#854d0e;font-size:0.8rem;font-weight:600;">perlu diisi</span> sebelum disimpan.
            </p>
        </div>

        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <button type="button" class="btn secondary" onclick="removeEmptyNameRows()" style="padding:6px 14px;font-size:0.85rem;" title="Hapus baris yang namanya masih kosong">
                🧹 Hapus Baris Kosong
            </button>
            <button type="button" class="btn secondary" onclick="addNewEmptyRow()" style="padding:6px 14px;font-size:0.85rem;">
                + Tambah Baris Baru
            </button>
            <button type="button" class="btn secondary" onclick="clearTable()" style="padding:6px 14px;font-size:0.85rem;color:#dc2626;border-color:#fca5a5;">
                Kosongkan
            </button>
        </div>
    </div>

    <!-- Bulk Controls Toolbar -->
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:1rem;margin-bottom:1.25rem;">
        <div style="font-size:0.85rem;font-weight:700;color:#475569;margin-bottom:0.75rem;text-transform:uppercase;letter-spacing:0.04em;">
            ⚡ Aksi Cepat Sekaligus (Bulk Actions)
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:1rem;align-items:end;">
            <!-- Bulk Role -->
            <div>
                <label for="bulkRoleSelect" style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:4px;color:#334155;">
                    1. Atur Peran untuk Semua Baris:
                </label>
                <div style="display:flex;gap:8px;">
                    <select id="bulkRoleSelect" style="flex:1;padding:7px 10px;font-size:0.88rem;border:1px solid #cbd5e1;border-radius:6px;background:#fff;">
                        <option value="">-- Pilih Peran --</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}">
                                {{ match($role->name) {'admin'=>'Waka / Admin','pic'=>'PIC Ruang','guru'=>'Guru','organisasi'=>'Organisasi',default=>$role->name} }}
                            </option>
                        @endforeach
                    </select>
                    <button type="button" class="btn" style="white-space:nowrap;padding:7px 14px;font-size:0.85rem;background:#d97706;border-color:#d97706;" onclick="applyBulkRole()">
                        Terapkan ke Semua
                    </button>
                </div>
            </div>

            <!-- Bulk Password -->
            <div>
                <label for="bulkPasswordInput" style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:4px;color:#334155;">
                    2. Isi Kata Sandi yang Kosong:
                </label>
                <div style="display:flex;gap:8px;">
                    <input type="text" id="bulkPasswordInput" value="Password123!" placeholder="Kata sandi default (min 8 karakter)" style="flex:1;padding:7px 10px;font-size:0.88rem;border:1px solid #cbd5e1;border-radius:6px;background:#fff;">
                    <button type="button" class="btn secondary" style="white-space:nowrap;padding:7px 14px;font-size:0.85rem;" onclick="applyBulkPassword()">
                        Isi Sandi Kosong
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Submission Form -->
    <form id="importForm" method="POST" action="{{ route('users.import.store') }}">
        @csrf
        <div style="overflow-x:auto;border:1px solid #e2e8f0;border-radius:8px;max-height:550px;">
            <table id="usersTable" style="width:100%;border-collapse:collapse;font-size:0.88rem;min-width:850px;">
                <thead style="position:sticky;top:0;background:#f1f5f9;z-index:2;box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                    <tr>
                        <th style="padding:10px 8px;text-align:center;width:45px;border-bottom:1px solid #cbd5e1;">#</th>
                        <th style="padding:10px 10px;text-align:left;min-width:180px;border-bottom:1px solid #cbd5e1;">Nama Lengkap <span style="color:#dc2626;">*</span></th>
                        <th style="padding:10px 10px;text-align:left;min-width:210px;border-bottom:1px solid #cbd5e1;">Email Akun (Login) <span style="color:#dc2626;">*</span></th>
                        <th style="padding:10px 10px;text-align:left;min-width:160px;border-bottom:1px solid #cbd5e1;">Kata Sandi <span style="color:#dc2626;">*</span></th>
                        <th style="padding:10px 10px;text-align:left;min-width:150px;border-bottom:1px solid #cbd5e1;">Nomor WhatsApp <span style="color:#dc2626;">*</span></th>
                        <th style="padding:10px 10px;text-align:left;min-width:170px;border-bottom:1px solid #cbd5e1;">Peran Akun <span style="color:#dc2626;">*</span></th>
                        <th style="padding:10px 8px;text-align:center;width:60px;border-bottom:1px solid #cbd5e1;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <!-- Dynamic Rows populated via JS -->
                </tbody>
            </table>
        </div>

        <div style="margin-top:1.5rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;border-top:1px solid #e2e8f0;padding-top:1.25rem;">
            <div id="validationSummary" style="font-size:0.88rem;color:#64748b;">
                Periksa kembali kelengkapan seluruh data sebelum menyimpan.
            </div>

            <div style="display:flex;gap:10px;align-items:center;">
                <a href="{{ route('users.index') }}" class="btn secondary">Batal</a>
                <button type="submit" id="btnSubmitAll" class="btn" style="padding:10px 24px;font-size:0.95rem;background:#059669;border-color:#059669;font-weight:600;">
                    💾 Simpan Semua Pengguna
                </button>
            </div>
        </div>
    </form>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

#dropzone.dragover {
    background: #ecfdf5 !important;
    border-color: #059669 !important;
}

.table-input {
    width: 100%;
    padding: 6px 10px;
    font-size: 0.86rem;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    background: #ffffff;
    transition: all 0.15s ease;
    box-sizing: border-box;
}

.table-input:focus {
    border-color: #d97706;
    outline: none;
    box-shadow: 0 0 0 2px rgba(217, 119, 6, 0.2);
}

.table-input.is-empty {
    border-color: #f59e0b !important;
    background-color: #fffbeb !important;
}

.table-input.is-invalid {
    border-color: #ef4444 !important;
    background-color: #fef2f2 !important;
}

.row-delete-btn {
    background: transparent;
    border: none;
    cursor: pointer;
    color: #94a3b8;
    padding: 4px 8px;
    border-radius: 6px;
    transition: all 0.15s;
    font-size: 1rem;
}

.row-delete-btn:hover {
    color: #ef4444;
    background: #fee2e2;
}

#usersTable tbody tr:hover td {
    background-color: #f8fafc;
}

#usersTable td {
    padding: 8px 10px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('fileInput');
    const selectedFileInfo = document.getElementById('selectedFileInfo');
    const fileNameSpan = document.getElementById('fileName');
    const fileSizeSpan = document.getElementById('fileSize');
    const btnProcessFile = document.getElementById('btnProcessFile');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const stagingCard = document.getElementById('stagingCard');
    const usersTableBody = document.getElementById('usersTableBody');
    const totalRowsCount = document.getElementById('totalRowsCount');
    const importForm = document.getElementById('importForm');
    const validationSummary = document.getElementById('validationSummary');

    // Available system roles from backend
    const systemRoles = [
        @foreach($roles as $role)
            { id: {{ $role->id }}, name: "{{ $role->name }}", label: "{{ match($role->name) {'admin'=>'Waka / Admin','pic'=>'PIC Ruang','guru'=>'Guru','organisasi'=>'Organisasi',default=>$role->name} }}" },
        @endforeach
    ];

    let currentFile = null;

    // Drag and drop handlers
    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('dragover');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('dragover');
        });
    });

    dropzone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files.length > 0) {
            handleFileSelect(files[0]);
        }
    });

    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            handleFileSelect(this.files[0]);
        }
    });

    function handleFileSelect(file) {
        currentFile = file;
        fileNameSpan.textContent = file.name;
        fileSizeSpan.textContent = '(' + (file.size / 1024).toFixed(1) + ' KB)';
        selectedFileInfo.style.display = 'inline-flex';
        // Auto process
        processUploadedFile();
    }

    btnProcessFile.addEventListener('click', function(e) {
        e.stopPropagation();
        processUploadedFile();
    });

    function processUploadedFile() {
        if (!currentFile) return;

        const formData = new FormData();
        formData.append('file', currentFile);
        formData.append('_token', '{{ csrf_token() }}');

        loadingIndicator.style.display = 'block';
        stagingCard.style.display = 'none';

        fetch('{{ route("users.import.parse") }}', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => { throw new Error(data.message || 'Gagal membaca dokumen.'); });
            }
            return response.json();
        })
        .then(data => {
            loadingIndicator.style.display = 'none';
            if (data.success && data.rows && data.rows.length > 0) {
                renderTableRows(data.rows);
                stagingCard.style.display = 'block';
                stagingCard.scrollIntoView({ behavior: 'smooth' });
            } else {
                alert(data.message || 'Tidak ada baris data pengguna yang valid dalam dokumen.');
            }
        })
        .catch(err => {
            loadingIndicator.style.display = 'none';
            alert('Kesalahan saat membaca dokumen: ' + err.message);
        });
    }

    function isValidPersonName(text) {
        if (!text) return false;
        const t = String(text).trim();
        if (t.length < 2 || t.length > 75 || !isNaN(t)) return false;
        if (!/[a-zA-Z]/.test(t)) return false;

        // Reject trailing commas (e.g. "Bantul,") or colons/semicolons
        if (t.endsWith(',') || t.startsWith(',') || t.includes(':') || t.includes(';')) return false;

        // Reject characters that never belong in real names
        if (/[:;\/\\_\+=\[\]\{\}%\*#\$]/.test(t)) return false;
        if (t.includes('..') || t.includes('--')) return false;

        const lower = t.toLowerCase();
        const cleanLower = lower.replace(/[^a-z0-9\s]/g, '').trim();

        // Place names & administrative locations
        const placeNames = [
            'bantul', 'yogyakarta', 'jogja', 'sleman', 'kulon progo', 'kulonprogo', 'gunungkidul', 'gunung kidul',
            'klaten', 'magelang', 'surakarta', 'solo', 'semarang', 'jakarta', 'surabaya', 'bandung',
            'indonesia', 'diy', 'd.i. yogyakarta', 'd.i.y.'
        ];
        if (placeNames.includes(cleanLower)) return false;
        for (let p of placeNames) {
            if (cleanLower.startsWith(p + ' ') || cleanLower.endsWith(' ' + p)) return false;
        }

        // Months
        const months = [
            'januari', 'februari', 'maret', 'april', 'mei', 'juni', 'juli', 'agustus',
            'september', 'oktober', 'november', 'desember'
        ];
        for (let m of months) {
            if (lower.includes(m)) return false;
        }

        // 4-digit years like 2024, 2025, 2026, 2027
        if (/\b(20[2-3][0-9])\b/.test(t)) return false;

        const exactRejects = [
            'nip', 'nip.', 'nis', 'nisn', 'nik', 'nuptk',
            'no', 'no.', 'nomor', 'urt', 'urut', 'no urut', 'no. urut',
            'nama', 'nama lengkap', 'nama siswa', 'nama guru', 'nama peserta', 'nama pengguna', 'name', 'full name', 'fullname',
            'email', 'mail', 'whatsapp', 'wa', 'telp', 'telepon', 'hp', 'phone',
            'l', 'p', 'l/p', 'l / p', 'jk', 'jenis kelamin', 'laki-laki', 'perempuan',
            'guru mata pelajaran', 'guru mapel', 'wali kelas', 'kepala sekolah', 'waka',
            'mengetahui', 'mengetahui,', 'tanda tangan', 'ttd',
            'jumlah', 'total', 'rata-rata', 'keterangan', 'ket', 'catatan', 'catatan:',
            'alfa', 'izin', 'sakit', 'hadir', 'presensi', 'absensi'
        ];
        if (exactRejects.includes(lower) || exactRejects.includes(cleanLower)) return false;

        const disallowedSubs = [
            'kelas', 'kls ', 'rombel', 'jurusan',
            'tahun pelajaran', 'tahun ajaran', 'th. pelajaran', 'th pelajaran', 'tapel', 'semester',
            'mata pelajaran', 'guru mata pelajaran', 'guru mapel', 'wali kelas', 'kepala sekolah',
            'nip.', 'nuptk', 'nisn', 'tanda tangan', 'mengetahui',
            'daftar ', 'rekap ', 'rekapitulasi', 'laporan ',
            'smk negeri', 'smkn ', 'sman ', 'smpn '
        ];
        for (let sub of disallowedSubs) {
            if (lower.includes(sub)) return false;
        }

        if (t.length <= 3 && !['ali', 'ani', 'ana', 'eko', 'ida', 'ina', 'lia', 'mia', 'ria', 'tio', 'tri', 'udi', 'uti', 'ade', 'adi', 'ayu', 'eka', 'eva', 'ira', 'ita', 'nur', 'roy'].includes(lower)) {
            return false;
        }

        return true;
    }

    function renderTableRows(rows) {
        usersTableBody.innerHTML = '';
        // Pastikan hanya baris yang terbukti nama orang asli yang ditampilkan
        const validRows = (rows || []).filter(r => r && r.name && isValidPersonName(r.name));
        if (validRows.length === 0) {
            alert('Tidak ditemukan baris data dengan nama orang yang valid pada dokumen yang diunggah.');
            return;
        }
        validRows.forEach((row, idx) => {
            appendRowToTable(row, idx);
        });
        updateRowCount();
        checkTableValidation();
    }

    window.appendRowToTable = function(data = {}, index = null) {
        const rowIdx = index !== null ? index : usersTableBody.children.length;
        const tr = document.createElement('tr');
        tr.dataset.rowIdx = rowIdx;

        // Find role id from string name if provided
        let matchedRoleId = '';
        if (data.role) {
            const rName = String(data.role).toLowerCase();
            const found = systemRoles.find(r => r.name.toLowerCase() === rName);
            if (found) {
                matchedRoleId = found.id;
            }
        }

        const nameVal = data.name || '';
        const emailVal = data.email || '';
        const passwordVal = data.password || '';
        const waVal = data.whatsapp || '';

        // Role select options
        let roleOptions = '<option value="">-- Pilih Peran --</option>';
        systemRoles.forEach(r => {
            const selected = String(matchedRoleId) === String(r.id) ? 'selected' : '';
            roleOptions += `<option value="${r.id}" ${selected}>${r.label}</option>`;
        });

        tr.innerHTML = `
            <td style="text-align:center;font-weight:600;color:#64748b;" class="row-num">${rowIdx + 1}</td>
            <td>
                <input type="text" name="users[${rowIdx}][name]" value="${escapeHtml(nameVal)}" placeholder="Nama lengkap..." class="table-input ${!nameVal ? 'is-empty' : ''}" required oninput="validateInput(this)">
            </td>
            <td>
                <input type="email" name="users[${rowIdx}][email]" value="${escapeHtml(emailVal)}" placeholder="email@sekolah.sch.id" class="table-input ${!emailVal ? 'is-empty' : ''}" required oninput="validateEmailInput(this)">
            </td>
            <td>
                <input type="text" name="users[${rowIdx}][password]" value="${escapeHtml(passwordVal)}" placeholder="Min 8 karakter" class="table-input ${!passwordVal ? 'is-empty' : ''}" required minlength="8" oninput="validatePasswordInput(this)">
            </td>
            <td>
                <input type="text" name="users[${rowIdx}][whatsapp]" value="${escapeHtml(waVal)}" placeholder="62812..." class="table-input ${!waVal ? 'is-empty' : ''}" required oninput="validateWaInput(this)">
            </td>
            <td>
                <select name="users[${rowIdx}][role_id]" class="table-input ${!matchedRoleId ? 'is-empty' : ''}" required onchange="validateSelect(this)">
                    ${roleOptions}
                </select>
            </td>
            <td style="text-align:center;">
                <button type="button" class="row-delete-btn" title="Hapus baris ini" onclick="deleteTableRow(this)">🗑️</button>
            </td>
        `;

        usersTableBody.appendChild(tr);
    };

    window.addNewEmptyRow = function() {
        window.appendRowToTable();
        updateRowCount();
        checkTableValidation();
        // Scroll to the new row
        const lastRow = usersTableBody.lastElementChild;
        if (lastRow) {
            lastRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            const firstInput = lastRow.querySelector('input');
            if (firstInput) firstInput.focus();
        }
    };

    window.deleteTableRow = function(btn) {
        const tr = btn.closest('tr');
        if (tr) {
            tr.remove();
            reindexRows();
            updateRowCount();
            checkTableValidation();
        }
    };

    window.removeEmptyNameRows = function() {
        const rows = usersTableBody.querySelectorAll('tr');
        let removed = 0;
        rows.forEach(tr => {
            const nameInput = tr.querySelector('input[name*="[name]"]');
            const nameVal = (nameInput ? nameInput.value : '').trim();
            if (!isValidPersonName(nameVal)) {
                tr.remove();
                removed++;
            }
        });
        if (removed > 0) {
            reindexRows();
            updateRowCount();
            checkTableValidation();
            alert(`Berhasil membersihkan ${removed} baris yang bukan nama orang.`);
        } else {
            alert('Semua baris sudah terisi nama orang yang sah.');
        }
    };

    window.clearTable = function() {
        if (confirm('Kosongkan seluruh data pada tabel pratinjau?')) {
            usersTableBody.innerHTML = '';
            stagingCard.style.display = 'none';
            selectedFileInfo.style.display = 'none';
            fileInput.value = '';
            currentFile = null;
        }
    };

    function reindexRows() {
        const rows = usersTableBody.querySelectorAll('tr');
        rows.forEach((tr, idx) => {
            tr.dataset.rowIdx = idx;
            const numCell = tr.querySelector('.row-num');
            if (numCell) numCell.textContent = idx + 1;

            tr.querySelectorAll('input, select').forEach(input => {
                const name = input.getAttribute('name');
                if (name) {
                    input.setAttribute('name', name.replace(/users\[\d+\]/, `users[${idx}]`));
                }
            });
        });
    }

    function updateRowCount() {
        const count = usersTableBody.children.length;
        totalRowsCount.textContent = count;
        if (count === 0) {
            stagingCard.style.display = 'none';
        }
    }

    // Bulk Actions
    window.applyBulkRole = function() {
        const bulkSelect = document.getElementById('bulkRoleSelect');
        const roleId = bulkSelect.value;
        if (!roleId) {
            alert('Silakan pilih peran terlebih dahulu.');
            return;
        }

        const roleSelects = usersTableBody.querySelectorAll('select[name*="[role_id]"]');
        roleSelects.forEach(sel => {
            sel.value = roleId;
            sel.classList.remove('is-empty');
        });
        checkTableValidation();
    };

    window.applyBulkPassword = function() {
        const bulkPass = document.getElementById('bulkPasswordInput').value.trim();
        if (bulkPass.length < 8) {
            alert('Kata sandi default minimal harus 8 karakter.');
            return;
        }

        const passInputs = usersTableBody.querySelectorAll('input[name*="[password]"]');
        let filledCount = 0;
        passInputs.forEach(input => {
            if (!input.value.trim()) {
                input.value = bulkPass;
                input.classList.remove('is-empty', 'is-invalid');
                filledCount++;
            }
        });
        checkTableValidation();
        alert(`Berhasil mengisi ${filledCount} kata sandi yang sebelumnya kosong.`);
    };

    // Live validation helpers
    window.validateInput = function(el) {
        if (!el.value.trim()) {
            el.classList.add('is-empty');
        } else {
            el.classList.remove('is-empty');
        }
        checkTableValidation();
    };

    window.validateEmailInput = function(el) {
        const val = el.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!val) {
            el.classList.add('is-empty');
            el.classList.remove('is-invalid');
        } else if (!emailRegex.test(val)) {
            el.classList.add('is-invalid');
            el.classList.remove('is-empty');
        } else {
            el.classList.remove('is-empty', 'is-invalid');
        }
        checkTableValidation();
    };

    window.validatePasswordInput = function(el) {
        const val = el.value.trim();
        if (!val) {
            el.classList.add('is-empty');
            el.classList.remove('is-invalid');
        } else if (val.length < 8) {
            el.classList.add('is-invalid');
            el.classList.remove('is-empty');
        } else {
            el.classList.remove('is-empty', 'is-invalid');
        }
        checkTableValidation();
    };

    window.validateWaInput = function(el) {
        const val = el.value.trim().replace(/[^0-9]/g, '');
        if (!val) {
            el.classList.add('is-empty');
            el.classList.remove('is-invalid');
        } else if (val.length < 8 || val.length > 20) {
            el.classList.add('is-invalid');
            el.classList.remove('is-empty');
        } else {
            el.classList.remove('is-empty', 'is-invalid');
        }
        checkTableValidation();
    };

    window.validateSelect = function(el) {
        if (!el.value) {
            el.classList.add('is-empty');
        } else {
            el.classList.remove('is-empty');
        }
        checkTableValidation();
    };

    function checkTableValidation() {
        const emptyFields = usersTableBody.querySelectorAll('.is-empty, .is-invalid');
        const count = usersTableBody.children.length;
        if (count === 0) {
            validationSummary.innerHTML = 'Belum ada data pengguna.';
            return false;
        }

        if (emptyFields.length > 0) {
            validationSummary.innerHTML = `<span style="color:#d97706;font-weight:600;">⚠️ Terdapat ${emptyFields.length} kolom yang belum lengkap atau tidak valid (ditandai kuning/merah).</span> Harap lengkapi sebelum menyimpan.`;
            return false;
        } else {
            validationSummary.innerHTML = `<span style="color:#059669;font-weight:600;">✅ Seluruh ${count} data pengguna telah lengkap dan siap disimpan.</span>`;
            return true;
        }
    }

    importForm.addEventListener('submit', function(e) {
        // Otomatis bersihkan baris yang sama sekali kosong (misal ditambahkan dari tombol tapi tidak diisi)
        const currentRows = Array.from(usersTableBody.children);
        let cleanedBlank = 0;
        currentRows.forEach(tr => {
            const nameVal = (tr.querySelector('input[name*="[name]"]')?.value || '').trim();
            const emailVal = (tr.querySelector('input[name*="[email]"]')?.value || '').trim();
            const passVal = (tr.querySelector('input[name*="[password]"]')?.value || '').trim();
            if (!nameVal && !emailVal && !passVal) {
                tr.remove();
                cleanedBlank++;
            }
        });
        if (cleanedBlank > 0) {
            reindexRows();
            updateRowCount();
        }

        const rows = usersTableBody.children;
        if (rows.length === 0) {
            e.preventDefault();
            alert('Tidak ada data pengguna pada tabel pratinjau.');
            return;
        }

        // Trigger validation check on all inputs
        let hasErrors = false;
        let firstInvalid = null;

        usersTableBody.querySelectorAll('input, select').forEach(el => {
            if (el.tagName === 'SELECT') {
                window.validateSelect(el);
            } else if (el.name.includes('[email]')) {
                window.validateEmailInput(el);
            } else if (el.name.includes('[password]')) {
                window.validatePasswordInput(el);
            } else if (el.name.includes('[whatsapp]')) {
                window.validateWaInput(el);
            } else {
                window.validateInput(el);
            }

            if (el.classList.contains('is-empty') || el.classList.contains('is-invalid')) {
                hasErrors = true;
                if (!firstInvalid) firstInvalid = el;
            }
        });

        if (hasErrors) {
            e.preventDefault();
            alert('Masih terdapat data yang kosong atau belum sesuai (ditandai warna kuning/merah). Harap lengkapi terlebih dahulu.');
            if (firstInvalid) {
                firstInvalid.focus();
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        // Confirm submission
        if (!confirm(`Simpan ${rows.length} akun pengguna ke sistem?`)) {
            e.preventDefault();
        }
    });

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
</script>
@endsection

