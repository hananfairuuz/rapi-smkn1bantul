<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use ZipArchive;

class UserDocumentParserService
{
    /**
     * Parse an uploaded document (Excel, Word, CSV) into normalized user data.
     */
    public function parse(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        $rawRows = match ($extension) {
            'csv', 'txt' => $this->parseCsv($path),
            'xlsx', 'xlsm' => $this->parseXlsx($path),
            'docx' => $this->parseDocx($path),
            default => throw new \InvalidArgumentException("Format file .{$extension} tidak didukung. Gunakan Excel (.xlsx), Word (.docx), atau CSV (.csv)."),
        };

        return $this->normalize($rawRows);
    }

    /**
     * Parse CSV files with delimiter auto-detection.
     */
    public function parseCsv(string $path): array
    {
        $content = file_get_contents($path);
        if ($content === false || trim($content) === '') {
            return [];
        }

        // Remove UTF-8 BOM if present
        $bom = pack('H*', 'EFBBBF');
        $content = preg_replace("/^{$bom}/", '', $content);

        // Auto-detect delimiter from first line
        $firstLine = strtok($content, "\r\n") ?: '';
        $delimiters = [',', ';', "\t", '|'];
        $bestDelimiter = ',';
        $maxCount = 0;
        foreach ($delimiters as $delim) {
            $count = substr_count($firstLine, $delim);
            if ($count > $maxCount) {
                $maxCount = $count;
                $bestDelimiter = $delim;
            }
        }

        $lines = preg_split("/\r\n|\n|\r/", $content);
        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $row = str_getcsv($line, $bestDelimiter);
            if (!empty(array_filter($row, fn ($val) => trim((string) $val) !== ''))) {
                $rows[] = array_map(fn ($val) => trim((string) $val), $row);
            }
        }

        return $rows;
    }

    /**
     * Parse XLSX Excel files directly via ZipArchive and XML without third-party heavy dependencies.
     */
    public function parseXlsx(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Gagal membuka file Excel. Pastikan file tidak rusak.');
        }

        // 1. Read shared strings
        $sharedStrings = [];
        $stringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($stringsXml !== false) {
            $xml = @simplexml_load_string($stringsXml);
            if ($xml !== false) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $r) {
                            $text .= (string) $r->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Read all worksheets in the exact tab order specified in workbook.xml
        $sheetPaths = $this->getWorksheetPathsInOrder($zip);
        $rows = [];

        foreach ($sheetPaths as $sheetPath) {
            $sheetXml = $zip->getFromName($sheetPath);
            if ($sheetXml === false) {
                continue;
            }

            $xml = @simplexml_load_string($sheetXml);
            if ($xml === false || !isset($xml->sheetData)) {
                continue;
            }

            foreach ($xml->sheetData->row as $rowNode) {
                $rowCells = [];
                $maxColIndex = 0;

                foreach ($rowNode->c as $cellNode) {
                    $cellRef = (string) $cellNode['r'];
                    $colLetters = preg_replace('/[0-9]/', '', $cellRef);
                    $colIndex = $this->lettersToColumnIndex($colLetters);

                    $cellType = (string) $cellNode['t'];
                    $val = '';

                    if ($cellType === 's') {
                        // Shared string index
                        $idx = (int) (string) $cellNode->v;
                        $val = $sharedStrings[$idx] ?? '';
                    } elseif ($cellType === 'inlineStr' && isset($cellNode->is->t)) {
                        $val = (string) $cellNode->is->t;
                    } elseif (isset($cellNode->v)) {
                        $val = (string) $cellNode->v;
                    }

                    $rowCells[$colIndex] = trim($val);
                    if ($colIndex > $maxColIndex) {
                        $maxColIndex = $colIndex;
                    }
                }

                // Fill empty cells to preserve column order
                $orderedRow = [];
                for ($i = 0; $i <= $maxColIndex; $i++) {
                    $orderedRow[$i] = $rowCells[$i] ?? '';
                }

                if (!empty(array_filter($orderedRow, fn ($v) => $v !== ''))) {
                    $rows[] = $orderedRow;
                }
            }
        }

        $zip->close();

        return $rows;
    }

    /**
     * Get all worksheet file paths in the exact tab order specified in workbook.xml.
     */
    private function getWorksheetPathsInOrder(ZipArchive $zip): array
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relsXml === false) {
            $found = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if (isset($stat['name']) && str_starts_with($stat['name'], 'xl/worksheets/sheet') && str_ends_with($stat['name'], '.xml')) {
                    $found[] = $stat['name'];
                }
            }
            return !empty($found) ? $found : ['xl/worksheets/sheet1.xml'];
        }

        $wb = @simplexml_load_string($workbookXml);
        $rels = @simplexml_load_string($relsXml);

        if ($wb === false || $rels === false) {
            return ['xl/worksheets/sheet1.xml'];
        }

        $relMap = [];
        if (isset($rels->Relationship)) {
            foreach ($rels->Relationship as $r) {
                $relMap[(string) $r['Id']] = (string) $r['Target'];
            }
        }

        $sheetPaths = [];
        if (isset($wb->sheets->sheet)) {
            foreach ($wb->sheets->sheet as $s) {
                $rId = (string) $s->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
                if (isset($relMap[$rId])) {
                    $target = $relMap[$rId];
                    $path = str_starts_with($target, 'xl/') ? $target : 'xl/' . ltrim($target, '/');
                    $sheetPaths[] = $path;
                }
            }
        }

        return !empty($sheetPaths) ? $sheetPaths : ['xl/worksheets/sheet1.xml'];
    }

    /**
     * Parse DOCX Word files directly via ZipArchive and word/document.xml.
     */
    public function parseDocx(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Gagal membuka file Word. Pastikan format file adalah .docx yang valid.');
        }

        $docXml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($docXml === false) {
            return [];
        }

        $xml = @simplexml_load_string($docXml);
        if ($xml === false) {
            return [];
        }

        // Register OpenXML namespaces
        $namespaces = $xml->getNamespaces(true);
        $wNamespace = $namespaces['w'] ?? 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
        $xml->registerXPathNamespace('w', $wNamespace);

        $rows = [];

        // 1. Look for tables (<w:tbl>)
        $tables = $xml->xpath('//w:tbl');
        if (!empty($tables)) {
            foreach ($tables as $tbl) {
                $tbl->registerXPathNamespace('w', $wNamespace);
                $trList = $tbl->xpath('.//w:tr');
                foreach ($trList as $tr) {
                    $tr->registerXPathNamespace('w', $wNamespace);
                    $tcList = $tr->xpath('.//w:tc');
                    $rowCells = [];
                    foreach ($tcList as $tc) {
                        $tc->registerXPathNamespace('w', $wNamespace);
                        $tNodes = $tc->xpath('.//w:t');
                        $cellText = '';
                        foreach ($tNodes as $t) {
                            $cellText .= (string) $t;
                        }
                        $rowCells[] = trim($cellText);
                    }
                    if (!empty(array_filter($rowCells, fn ($v) => $v !== ''))) {
                        $rows[] = $rowCells;
                    }
                }
            }
        }

        // 2. If no table rows were extracted, fallback to paragraphs
        if (empty($rows)) {
            $paragraphs = $xml->xpath('//w:p');
            foreach ($paragraphs as $p) {
                $p->registerXPathNamespace('w', $wNamespace);
                $tNodes = $p->xpath('.//w:t');
                $pText = '';
                foreach ($tNodes as $t) {
                    $pText .= (string) $t;
                }
                $pText = trim($pText);
                if ($pText !== '') {
                    // Check if delimiter like comma, tab, or semicolon exists
                    $cells = preg_split('/[,;\t|]/', $pText);
                    $rows[] = array_map('trim', $cells);
                }
            }
        }

        return $rows;
    }

    /**
     * Convert Excel column letters (A, B, ..., Z, AA, ...) to 0-based index.
     */
    private function lettersToColumnIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $len = strlen($letters);
        $index = 0;
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }
        return $index - 1;
    }

    /**
     * Map raw rows into clean, structured user records with column auto-detection.
     * Guaranteed that every returned row has a real, verified person name.
     */
    public function normalize(array $rawRows): array
    {
        if (empty($rawRows)) {
            return [];
        }

        // Clean values
        $cleanedRows = array_map(function ($row) {
            return array_map(fn ($cell) => trim((string) $cell), $row);
        }, $rawRows);

        $columnMap = [
            'name' => null,
            'email' => null,
            'password' => null,
            'whatsapp' => null,
            'role' => null,
        ];

        // 1. Find initial header row
        foreach ($cleanedRows as $row) {
            $headerCandidate = $this->detectRowHeader($row);
            if ($headerCandidate !== null) {
                $columnMap = $headerCandidate;
                break;
            }
        }

        if ($columnMap['name'] === null) {
            $columnMap = $this->guessColumnMapping($cleanedRows);
        }

        $normalized = [];
        foreach ($cleanedRows as $row) {
            if (empty(array_filter($row, fn ($val) => $val !== ''))) {
                continue;
            }

            // Check if this row is a column header (supports multi-class sheets with repeating headers)
            $headerCandidate = $this->detectRowHeader($row);
            if ($headerCandidate !== null) {
                $columnMap = $headerCandidate;
                continue; // Never include the header row itself!
            }

            // Extract candidate name from mapped name column
            $nameCandidate = '';
            if ($columnMap['name'] !== null && isset($row[$columnMap['name']])) {
                $nameCandidate = $this->cleanPersonName((string) $row[$columnMap['name']]);
            }

            // If the mapped cell does not contain a valid person name:
            if (!$this->isValidPersonName($nameCandidate)) {
                // Only if no header was found can we inspect alternative cells
                if ($columnMap['name'] === null) {
                    $foundValid = false;
                    foreach ($row as $cell) {
                        $alt = $this->cleanPersonName((string) $cell);
                        if ($this->isValidPersonName($alt)) {
                            $nameCandidate = $alt;
                            $foundValid = true;
                            break;
                        }
                    }
                    if (!$foundValid) {
                        continue;
                    }
                } else {
                    // Mapped column exists but is empty or not a valid person name (e.g. signature, date, notes)
                    continue;
                }
            }

            // Extract Email
            $email = $columnMap['email'] !== null ? ($row[$columnMap['email']] ?? '') : '';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $email = '';
                foreach ($row as $cell) {
                    if (filter_var($cell, FILTER_VALIDATE_EMAIL)) {
                        $email = $cell;
                        break;
                    }
                }
            }

            // Extract Password
            $password = $columnMap['password'] !== null ? ($row[$columnMap['password']] ?? '') : '';

            // Extract WhatsApp
            $whatsapp = $columnMap['whatsapp'] !== null ? ($row[$columnMap['whatsapp']] ?? '') : '';
            if (empty($whatsapp)) {
                foreach ($row as $cell) {
                    $cleanNum = preg_replace('/[^0-9]/', '', (string) $cell);
                    if (strlen($cleanNum) >= 9 && strlen($cleanNum) <= 16 && (str_starts_with($cleanNum, '08') || str_starts_with($cleanNum, '62') || str_starts_with($cleanNum, '8'))) {
                        $whatsapp = $cleanNum;
                        break;
                    }
                }
            }

            // Format WhatsApp standard 628...
            $whatsappClean = $this->formatWhatsApp($whatsapp);

            // Extract Role
            $roleRaw = $columnMap['role'] !== null ? ($row[$columnMap['role']] ?? '') : '';
            if (empty($roleRaw)) {
                foreach ($row as $cell) {
                    $rTest = $this->normalizeRole((string) $cell);
                    if ($rTest !== null) {
                        $roleRaw = $cell;
                        break;
                    }
                }
            }
            $role = $this->normalizeRole($roleRaw);

            $normalized[] = [
                'name' => $nameCandidate,
                'email' => strtolower($email),
                'password' => $password,
                'whatsapp' => $whatsappClean,
                'role' => $role,
            ];
        }

        return $normalized;
    }

    /**
     * Detect if a row is a table column header row.
     */
    public function detectRowHeader(array $row): ?array
    {
        $nonEmpty = count(array_filter($row, fn ($c) => trim((string) $c) !== ''));
        if ($nonEmpty < 2) {
            return null; // A header row must have at least 2 non-empty cells
        }

        $rowLower = array_map('mb_strtolower', $row);
        $map = [
            'name' => null,
            'email' => null,
            'password' => null,
            'whatsapp' => null,
            'role' => null,
        ];
        $score = 0;

        foreach ($rowLower as $cIdx => $cell) {
            $c = trim($cell);
            if (in_array($c, ['nama lengkap', 'nama siswa', 'nama peserta', 'nama guru', 'nama pengguna', 'nama anggota', 'full name', 'fullname', 'name', 'nama'], true)) {
                $map['name'] = $cIdx;
                $score += 5;
            } elseif (str_contains($c, 'nama') && !str_contains($c, 'daftar') && !str_contains($c, 'ayah') && !str_contains($c, 'ibu') && !str_contains($c, 'sekolah') && !str_contains($c, 'guru')) {
                $map['name'] ??= $cIdx;
                $score += 3;
            }

            if (in_array($c, ['email', 'e-mail', 'email akun', 'email login', 'mail'], true)) {
                $map['email'] = $cIdx;
                $score += 5;
            } elseif (str_contains($c, 'email') || str_contains($c, 'mail')) {
                $map['email'] ??= $cIdx;
                $score += 3;
            }

            if (in_array($c, ['kata sandi', 'sandi', 'password', 'pass', 'pwd'], true)) {
                $map['password'] = $cIdx;
                $score += 5;
            }

            if (in_array($c, ['whatsapp', 'wa', 'no wa', 'nomor whatsapp', 'no telepon', 'telepon', 'telp', 'no hp', 'hp', 'phone', 'contact'], true)) {
                $map['whatsapp'] = $cIdx;
                $score += 5;
            } elseif (str_contains($c, 'wa') || str_contains($c, 'telp') || str_contains($c, 'phone') || str_contains($c, 'hp')) {
                $map['whatsapp'] ??= $cIdx;
                $score += 3;
            }

            if (in_array($c, ['peran', 'role', 'jabatan', 'posisi', 'level'], true)) {
                $map['role'] = $cIdx;
                $score += 5;
            }

            if (in_array($c, ['no', 'no.', 'nomor', 'urt', 'urut', 'no urut', 'nis', 'nisn', 'nip', 'nik', 'jk', 'l/p', 'l / p', 'kelas'], true)) {
                $score += 2;
            }
        }

        return ($score >= 5 && $map['name'] !== null) ? $map : null;
    }

    /**
     * Check if a string is a real, valid person name rather than document metadata, headers, titles, or form labels.
     */
    public function isValidPersonName(string $text): bool
    {
        $t = trim($text);
        if ($t === '' || mb_strlen($t) < 2 || mb_strlen($t) > 75 || is_numeric($t)) {
            return false;
        }

        // Must contain letters
        if (!preg_match('/[a-zA-Z]/', $t)) {
            return false;
        }

        // Reject trailing commas (like "Bantul,") or colons/semicolons
        if (str_ends_with($t, ',') || str_starts_with($t, ',') || str_contains($t, ':') || str_contains($t, ';')) {
            return false;
        }

        // Reject colons (:), slashes (/ or \), semicolons, brackets, math/formatting symbols
        if (preg_match('/[:;\/\\\\_\+=\[\]\{\}%\*#\$]/', $t)) {
            return false;
        }

        // Reject multiple dots (..) or dashes (--) used for fill-ins or placeholders
        if (str_contains($t, '..') || str_contains($t, '--')) {
            return false;
        }

        $lower = mb_strtolower($t);
        $cleanLower = trim((string) preg_replace('/[^a-z0-9\s]/', '', $lower));

        // Reject Indonesian cities, regencies, provinces (especially Yogyakarta/Bantul school context)
        $placeNames = [
            'bantul', 'yogyakarta', 'jogja', 'sleman', 'kulon progo', 'kulonprogo', 'gunungkidul', 'gunung kidul',
            'klaten', 'magelang', 'surakarta', 'solo', 'semarang', 'jakarta', 'surabaya', 'bandung',
            'indonesia', 'diy', 'd.i. yogyakarta', 'd.i.y.'
        ];
        if (in_array($cleanLower, $placeNames, true)) {
            return false;
        }
        foreach ($placeNames as $p) {
            if (str_starts_with($cleanLower, $p . ' ') || str_ends_with($cleanLower, ' ' . $p)) {
                return false;
            }
        }

        // Reject month names (usually present in date signature lines like "Bantul, 10 Juli 2026")
        $months = [
            'januari', 'februari', 'maret', 'april', 'mei', 'juni', 'juli', 'agustus',
            'september', 'oktober', 'november', 'desember'
        ];
        foreach ($months as $m) {
            if (str_contains($lower, $m)) {
                return false;
            }
        }

        // Reject 4-digit years (e.g. 2024, 2025, 2026, 2027)
        if (preg_match('/\b(20[2-3][0-9])\b/', $t)) {
            return false;
        }

        // Exact match rejects (common form labels, headers, titles, counters)
        $exactRejects = [
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
        if (in_array($lower, $exactRejects, true) || in_array($cleanLower, $exactRejects, true)) {
            return false;
        }

        // Disallowed substrings that indicate metadata / headers / titles
        $disallowedSubstrings = [
            'kelas', 'kls ', 'rombel', 'jurusan',
            'tahun pelajaran', 'tahun ajaran', 'th. pelajaran', 'th pelajaran', 'tapel', 'semester',
            'mata pelajaran', 'guru mata pelajaran', 'guru mapel', 'wali kelas', 'kepala sekolah',
            'nip.', 'nuptk', 'nisn', 'tanda tangan', 'mengetahui',
            'daftar ', 'rekap ', 'rekapitulasi', 'laporan ',
            'smk negeri', 'smkn ', 'sman ', 'smpn '
        ];
        foreach ($disallowedSubstrings as $sub) {
            if (str_contains($lower, $sub)) {
                return false;
            }
        }

        // Short strings <= 3 chars without spaces that look like codes (except known short names)
        if (mb_strlen($t) <= 3 && !in_array($lower, ['ali', 'ani', 'ana', 'eko', 'ida', 'ina', 'lia', 'mia', 'ria', 'tio', 'tri', 'udi', 'uti', 'ade', 'adi', 'ayu', 'eka', 'eva', 'ira', 'ita', 'nur', 'roy'])) {
            return false;
        }

        return true;
    }

    /**
     * Clean up a person's name by stripping leading numbering (e.g. "1. ALLIF MUKHLIS" -> "ALLIF MUKHLIS").
     */
    public function cleanPersonName(string $name): string
    {
        $cleaned = preg_replace('/^\d+[\.\-\s]+/', '', trim($name));
        return trim($cleaned ?? '');
    }

    /**
     * Guess column mapping if there was no recognizable header row.
     */
    private function guessColumnMapping(array $rows): array
    {
        $map = [
            'name' => 0,
            'email' => null,
            'password' => null,
            'whatsapp' => null,
            'role' => null,
        ];

        // Sample up to 10 rows
        $sample = array_slice($rows, 0, 10);
        foreach ($sample as $row) {
            foreach ($row as $cIdx => $val) {
                if (filter_var($val, FILTER_VALIDATE_EMAIL)) {
                    $map['email'] ??= $cIdx;
                }
                $cleanNum = preg_replace('/[^0-9]/', '', $val);
                if (strlen($cleanNum) >= 9 && strlen($cleanNum) <= 15 && (str_starts_with($cleanNum, '08') || str_starts_with($cleanNum, '62'))) {
                    $map['whatsapp'] ??= $cIdx;
                }
                $valLower = strtolower($val);
                if (in_array($valLower, ['admin', 'pic', 'guru', 'organisasi'])) {
                    $map['role'] ??= $cIdx;
                }
            }
        }

        return $map;
    }

    /**
     * Normalize role string to valid system roles: admin, pic, guru, organisasi.
     */
    public function normalizeRole(string $roleRaw): ?string
    {
        $r = strtolower(trim($roleRaw));
        if ($r === '') {
            return null;
        }

        if (str_contains($r, 'admin') || str_contains($r, 'waka') || str_contains($r, 'kepala')) {
            return 'admin';
        }
        if (str_contains($r, 'pic') || str_contains($r, 'sarpras') || str_contains($r, 'penanggung')) {
            return 'pic';
        }
        if (str_contains($r, 'guru') || str_contains($r, 'pengajar') || str_contains($r, 'wali')) {
            return 'guru';
        }
        if (str_contains($r, 'organisasi') || str_contains($r, 'osis') || str_contains($r, 'ekskul') || str_contains($r, 'org')) {
            return 'organisasi';
        }

        return in_array($r, ['admin', 'pic', 'guru', 'organisasi']) ? $r : null;
    }

    /**
     * Format phone number to clean WhatsApp number standard.
     */
    public function formatWhatsApp(string $phone): string
    {
        $num = preg_replace('/[^0-9]/', '', $phone);
        if ($num === '') {
            return '';
        }
        if (str_starts_with($num, '08')) {
            return '62' . substr($num, 1);
        }
        if (str_starts_with($num, '8')) {
            return '62' . $num;
        }
        return $num;
    }

    /**
     * Sample template rows for user imports.
     */
    public function getTemplateSampleData(): array
    {
        return [
            ['Nama Lengkap', 'Email Akun', 'Kata Sandi', 'Nomor WhatsApp', 'Peran'],
            ['Budi Santoso', 'budi.santoso@smkn1bantul.sch.id', 'Password123!', '6281234567890', 'guru'],
            ['Siti Rahmawati', 'siti.rahmawati@smkn1bantul.sch.id', 'Password123!', '081298765432', 'pic'],
            ['Ahmad Pratama', 'ahmad.pratama@smkn1bantul.sch.id', 'Password123!', '6285712345678', 'admin'],
            ['Pengurus OSIS', 'osis@smkn1bantul.sch.id', 'Password123!', '085294756301', 'organisasi'],
        ];
    }

    /**
     * Generate template file content and filename based on requested format.
     */
    public function generateTemplate(string $format): array
    {
        $format = strtolower(trim($format));

        return match ($format) {
            'xlsx', 'excel' => [
                'filename' => 'template_import_pengguna.xlsx',
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'content' => $this->generateXlsxTemplate(),
            ],
            'docx', 'word' => [
                'filename' => 'template_import_pengguna.docx',
                'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'content' => $this->generateDocxTemplate(),
            ],
            default => [
                'filename' => 'template_import_pengguna.csv',
                'mime' => 'text/csv; charset=UTF-8',
                'content' => $this->generateCsvTemplate(),
            ],
        };
    }

    /**
     * Generate a CSV template.
     */
    public function generateCsvTemplate(): string
    {
        $handle = fopen('php://temp', 'r+');
        // Add UTF-8 BOM so Excel opens it with proper encoding
        fwrite($handle, pack('H*', 'EFBBBF'));

        foreach ($this->getTemplateSampleData() as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return $content ?: '';
    }

    /**
     * Generate a native XLSX template using ZipArchive without third-party dependencies.
     */
    public function generateXlsxTemplate(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_tpl_');
        $zip = new ZipArchive();
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';

        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>';

        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Pengguna" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>';

        $sheetRowsXml = '';
        foreach ($this->getTemplateSampleData() as $rIdx => $row) {
            $rowNum = $rIdx + 1;
            $sheetRowsXml .= '<row r="' . $rowNum . '">';
            foreach ($row as $cIdx => $cellVal) {
                $colLetter = chr(65 + $cIdx);
                $cellRef = $colLetter . $rowNum;
                $escaped = htmlspecialchars($cellVal, ENT_XML1, 'UTF-8');
                $sheetRowsXml .= '<c r="' . $cellRef . '" t="inlineStr"><is><t>' . $escaped . '</t></is></c>';
            }
            $sheetRowsXml .= '</row>';
        }

        $worksheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>' . $sheetRowsXml . '</sheetData>
</worksheet>';

        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/worksheets/sheet1.xml', $worksheet);
        $zip->close();

        $content = (string) file_get_contents($tempFile);
        @unlink($tempFile);

        return $content;
    }

    /**
     * Generate a native DOCX template table using ZipArchive.
     */
    public function generateDocxTemplate(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'docx_tpl_');
        $zip = new ZipArchive();
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>';

        $tableRowsXml = '';
        foreach ($this->getTemplateSampleData() as $row) {
            $tableRowsXml .= '<w:tr>';
            foreach ($row as $cellVal) {
                $escaped = htmlspecialchars($cellVal, ENT_XML1, 'UTF-8');
                $tableRowsXml .= '<w:tc><w:p><w:r><w:t>' . $escaped . '</w:t></w:r></w:p></w:tc>';
            }
            $tableRowsXml .= '</w:tr>';
        }

        $documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
  <w:body>
    <w:p><w:r><w:t>Daftar Calon Pengguna Sistem RAPI-Sekolah</w:t></w:r></w:p>
    <w:tbl>' . $tableRowsXml . '</w:tbl>
  </w:body>
</w:document>';

        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('word/document.xml', $documentXml);
        $zip->close();

        $content = (string) file_get_contents($tempFile);
        @unlink($tempFile);

        return $content;
    }
}
