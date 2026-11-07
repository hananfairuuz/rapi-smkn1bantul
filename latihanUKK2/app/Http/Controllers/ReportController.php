<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $this->ensureAdmin();
        $report = $this->monthlyReport($request);

        return response()->view('reports.index', $report);
    }

    public function docx(Request $request): Response
    {
        $this->ensureAdmin();
        $report = $this->monthlyReport($request);
        $filename = $this->reportFilename('laporan-rapi', $report);

        return $this->downloadDocx($report, $filename, fn (string $path, array $data) => $this->writeDocx($path, $data));
    }

    public function excel(Request $request): Response
    {
        $this->ensureAdmin();
        $report = $this->monthlyReport($request);
        $datePart = $report['period_type'] === 'month'
            ? sprintf('%04d-%02d', $report['year'], $report['month'])
            : $report['startDate']->format('Y-m-d');
        $filename = "laporan-rapi-{$datePart}.xls";

        return response()->streamDownload(function () use ($report): void {
            echo view('reports.excel-monthly', $report)->render();
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function damagedGoods(Request $request): Response
    {
        $this->ensurePic();
        $report = $this->monthlyDamagedGoodsReport($request);

        return response()->view('reports.damaged-goods', $report);
    }

    public function damagedGoodsDocx(Request $request): Response
    {
        $this->ensurePic();
        $report = $this->monthlyDamagedGoodsReport($request);
        $filename = $this->reportFilename('laporan-barang-rusak', $report);

        return $this->downloadDocx($report, $filename, fn (string $path, array $data) => $this->writeDamagedGoodsDocx($path, $data));
    }

    public function damagedGoodsExcel(Request $request): Response
    {
        $this->ensurePic();
        $report = $this->monthlyDamagedGoodsReport($request);
        $datePart = $report['period_type'] === 'month'
            ? sprintf('%04d-%02d', $report['year'], $report['month'])
            : $report['startDate']->format('Y-m-d');
        $filename = "laporan-barang-rusak-{$datePart}.xls";

        return response()->streamDownload(function () use ($report): void {
            echo view('reports.excel-damaged-goods', $report)->render();
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function damagedGoodsDocxForApi(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user?->role?->name === 'pic', 403);
        $report = $this->monthlyDamagedGoodsReport($request, $user->id);
        $filename = $this->reportFilename('laporan-barang-rusak', $report);

        return $this->downloadDocx($report, $filename, fn (string $path, array $data) => $this->writeDamagedGoodsDocx($path, $data));
    }

    private function downloadDocx(array $report, string $filename, callable $writer): Response
    {
        return response()->streamDownload(function () use ($report, $writer): void {
            $path = tempnam(sys_get_temp_dir(), 'rapi-report-');
            if ($path === false) {
                throw new \RuntimeException('Tidak dapat menyiapkan berkas laporan DOCX.');
            }

            try {
                $writer($path, $report);
                readfile($path);
            } finally {
                if (file_exists($path)) {
                    unlink($path);
                }
            }
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    private function monthlyReport(Request $request): array
    {
        $period = $this->reportPeriod($request);

        return [
            'month' => $period['month'],
            'year' => $period['year'],
            'period_type' => $period['type'],
            'period' => $period['period'],
            'startDate' => $period['startDate'],
            'endDate' => $period['endDate'],
            'events' => Event::with(['room', 'user'])
                ->whereDate('date', '>=', $period['startDate']->toDateString())
                ->whereDate('date', '<', $period['endDate']->toDateString())
                ->orderBy('date')
                ->orderBy('start_time')
                ->get(),
        ];
    }

    private function monthlyDamagedGoodsReport(Request $request, ?int $picId = null): array
    {
        $period = $this->reportPeriod($request);

        return [
            'month' => $period['month'],
            'year' => $period['year'],
            'period_type' => $period['type'],
            'period' => $period['period'],
            'startDate' => $period['startDate'],
            'endDate' => $period['endDate'],
            'complaints' => Complaint::with(['room', 'reporter'])
                ->whereHas('room', fn ($query) => $query->where('pic_id', $picId ?? auth()->id()))
                ->where('created_at', '>=', $period['startUtc'])
                ->where('created_at', '<', $period['endUtc'])
                ->latest('created_at')
                ->get(),
        ];
    }

    private function reportPeriod(Request $request): array
    {
        $data = $request->validate([
            'period_type' => ['nullable', 'in:month,week,day'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'selected_month' => ['nullable', 'date_format:Y-m'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,'.(now()->year + 1)],
        ]);

        $type = $data['period_type'] ?? 'month';
        if ($type === 'day' || $type === 'week') {
            $selectedDate = Carbon::parse($data['date'] ?? now('Asia/Jakarta')->toDateString(), 'Asia/Jakarta');
            $startDate = $type === 'week'
                ? $selectedDate->copy()->startOfWeek(Carbon::MONDAY)
                : $selectedDate->copy()->startOfDay();
            $endDate = $startDate->copy()->addDays($type === 'week' ? 7 : 1);
        } else {
            if (! empty($data['selected_month'])) {
                [$year, $month] = array_map('intval', explode('-', $data['selected_month']));
            } else {
                $month = (int) ($data['month'] ?? now('Asia/Jakarta')->month);
                $year = (int) ($data['year'] ?? now('Asia/Jakarta')->year);
            }
            $startDate = Carbon::createFromDate($year, $month, 1, 'Asia/Jakarta')->startOfMonth();
            $endDate = $startDate->copy()->addMonth();
        }
        $month = $startDate->month;
        $year = $startDate->year;
        $period = match ($type) {
            'day' => $startDate->locale('id')->translatedFormat('l, d F Y'),
            'week' => $startDate->format('d/m/Y').' – '.$endDate->copy()->subDay()->format('d/m/Y'),
            default => $startDate->locale('id')->translatedFormat('F Y'),
        };

        return [
            'type' => $type,
            'month' => $month,
            'year' => $year,
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'startUtc' => $startDate->copy()->utc(),
            'endUtc' => $endDate->copy()->utc(),
        ];
    }

    private function reportFilename(string $prefix, array $report): string
    {
        $datePart = $report['period_type'] === 'month'
            ? sprintf('%04d-%02d', $report['year'], $report['month'])
            : $report['startDate']->format('Y-m-d');

        return $prefix.'-'.$datePart.'.docx';
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()->role?->name === 'admin', 403);
    }

    private function ensurePic(): void
    {
        abort_unless(auth()->user()->role?->name === 'pic', 403);
    }

    private function writeDamagedGoodsDocx(string $path, array $report): void
    {
        $archive = new ZipArchive;
        if ($archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Tidak dapat membuat berkas laporan DOCX.');
        }

        $files = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'</Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            .'</Relationships>',
            'word/document.xml' => $this->damagedGoodsDocumentXml($report),
        ];
        foreach ($files as $name => $contents) {
            if (! $archive->addFromString($name, $contents)) {
                $archive->close();
                throw new \RuntimeException('Tidak dapat menulis isi berkas laporan DOCX.');
            }
        }
        if (! $archive->close()) {
            throw new \RuntimeException('Tidak dapat menyelesaikan berkas laporan DOCX.');
        }
    }

    /**
     * @param  array{period: string, complaints: Collection}  $report
     */
    private function damagedGoodsDocumentXml(array $report): string
    {
        $complaints = $report['complaints'];
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            .$this->paragraph('Laporan Barang Rusak RAPI-Sekolah', true)
            .$this->paragraph('Periode: '.$report['period'])
            .$this->paragraph('Ringkasan')
            .$this->tableXml([
                ['Total Pengaduan', (string) $complaints->count()],
                ['Belum Diperbaiki', (string) $complaints->where('status_repair', 'Belum Diperbaiki')->count()],
            ])
            .$this->paragraph('Pengaduan Barang/Fasilitas Rusak', true)
            .$this->tableXml(array_merge(
                [['No.', 'Tanggal Laporan', 'Ruangan', 'Barang/Fasilitas dan Keterangan', 'Pelapor', 'Status Perbaikan', 'Catatan']],
                $complaints->values()->map(fn (Complaint $complaint, int $index) => [
                    (string) ($index + 1),
                    $complaint->created_at->timezone('Asia/Jakarta')->format('d/m/Y'),
                    $complaint->room->name,
                    $complaint->facility_name.' - '.$complaint->description,
                    $complaint->reporter->name,
                    $complaint->status_repair,
                    $complaint->repair_notes ?: '—',
                ])->all() ?: [['Tidak ada pengaduan pada periode ini.']]
            ))
            .'<w:sectPr><w:pgSz w:orient="landscape" w:w="16838" w:h="11906"/><w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720" w:header="360" w:footer="360" w:gutter="0"/></w:sectPr>'
            .'</w:body></w:document>';

        return $xml;
    }

    /**
     * @param  array{period: string, events: Collection}  $report
     */
    private function writeDocx(string $path, array $report): void
    {
        $archive = new ZipArchive;
        if ($archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Tidak dapat membuat berkas laporan DOCX.');
        }

        $files = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'</Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            .'</Relationships>',
            'word/document.xml' => $this->documentXml($report),
        ];
        foreach ($files as $name => $contents) {
            if (! $archive->addFromString($name, $contents)) {
                $archive->close();
                throw new \RuntimeException('Tidak dapat menulis isi berkas laporan DOCX.');
            }
        }
        if (! $archive->close()) {
            throw new \RuntimeException('Tidak dapat menyelesaikan berkas laporan DOCX.');
        }
    }

    /**
     * @param  array{period: string, events: Collection}  $report
     */
    private function documentXml(array $report): string
    {
        $events = $report['events'];
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            .$this->paragraph('Laporan RAPI-Sekolah', true)
            .$this->paragraph('Periode: '.$report['period'])
            .$this->paragraph('Rekap jadwal penggunaan ruangan')
            .$this->paragraph('Ringkasan')
            .$this->tableXml([
                ['Total Jadwal', (string) $events->count()],
                ['Jadwal Disetujui', (string) $events->where('status', 'approved')->count()],
            ])
            .$this->paragraph('Jadwal Ruangan', true)
            .$this->tableXml(array_merge(
                [['No.', 'Tanggal', 'Waktu', 'Ruangan', 'Kegiatan', 'Peminjam', 'Status']],
                $events->values()->map(fn (Event $event, int $index) => [
                    (string) ($index + 1),
                    $event->date->format('d/m/Y'),
                    substr($event->start_time, 0, 5).'–'.substr($event->end_time, 0, 5).($event->endsNextDay() ? ' (+1 hari)' : ''),
                    $event->room->name,
                    $event->title,
                    $event->borrower_name ?: $event->user->name,
                    ['approved' => 'Disetujui', 'pending' => 'Menunggu', 'rejected' => 'Ditolak'][$event->status] ?? $event->status,
                ])->all() ?: [['Tidak ada jadwal pada periode ini.']]
            ))
            .'<w:sectPr><w:pgSz w:orient="landscape" w:w="16838" w:h="11906"/><w:pgMar w:top="720" w:right="720" w:bottom="720" w:left="720" w:header="360" w:footer="360" w:gutter="0"/></w:sectPr>'
            .'</w:body></w:document>';

        return $xml;
    }

    private function paragraph(string $text, bool $bold = false): string
    {
        $runProperties = $bold ? '<w:rPr><w:b/></w:rPr>' : '';

        return '<w:p><w:r>'.$runProperties.'<w:t xml:space="preserve">'.$this->escapeXml($text).'</w:t></w:r></w:p>';
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    private function tableXml(array $rows): string
    {
        $xml = '<w:tbl><w:tblPr><w:tblBorders>'
            .'<w:top w:val="single" w:sz="4" w:color="94A3B8"/>'
            .'<w:left w:val="single" w:sz="4" w:color="94A3B8"/>'
            .'<w:bottom w:val="single" w:sz="4" w:color="94A3B8"/>'
            .'<w:right w:val="single" w:sz="4" w:color="94A3B8"/>'
            .'<w:insideH w:val="single" w:sz="4" w:color="94A3B8"/>'
            .'<w:insideV w:val="single" w:sz="4" w:color="94A3B8"/>'
            .'</w:tblBorders></w:tblPr>';

        foreach ($rows as $row) {
            $xml .= '<w:tr>';
            foreach ($row as $cell) {
                $xml .= '<w:tc><w:tcPr><w:tcW w:w="0" w:type="auto"/></w:tcPr>'.$this->paragraph($cell).'</w:tc>';
            }
            $xml .= '</w:tr>';
        }

        return $xml.'</w:tbl>';
    }

    private function escapeXml(string $text): string
    {
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
