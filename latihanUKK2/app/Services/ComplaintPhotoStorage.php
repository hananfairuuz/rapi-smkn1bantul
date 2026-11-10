<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Service Kompresi Gambar Otomatis (PHP GD Library)
 *
 * Fungsi Utama:
 * 1. Mengubah resolusi gambar kamera resolusi tinggi ke dimensi proporsional (maks 1600px).
 * 2. Mengompresi kualitas gambar JPEG ke level 70% agar hemat ruang penyimpanan server.
 * 3. Menghasilkan nama berkas unik dan menyimpannya di disk storage 'public'.
 */
class ComplaintPhotoStorage
{
    // Batas maksimal ukuran berkas setelah dikompresi (1.000.000 byte = ~1 MB)
    private const MAX_STORED_BYTES = 1_000_000;

    /**
     * Memproses file upload, mengompres, dan menyimpan ke folder storage 'complaints/'
     *
     * @param UploadedFile $file   Berkas foto dari form request
     * @param string       $prefix Prefix nama berkas ('complaint_' untuk kerusakan, 'repair_' untuk bukti perbaikan)
     * @return string Jalur relatif penyimpanan file foto di storage
     */
    public function store(UploadedFile $file, string $prefix = 'complaint_'): string
    {
        // 1. Pastikan ekstensi GD aktif di PHP server
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg')) {
            throw new RuntimeException('Ekstensi GD wajib tersedia untuk mengompres foto pengaduan.');
        }

        // 2. Baca isi file foto asli
        $source = @imagecreatefromstring(file_get_contents($file->getRealPath()));
        if ($source === false) {
            throw new RuntimeException('Foto tidak dapat dibuka untuk dikompres.');
        }

        // 3. Hitung skala pengecilan proporsional (Maksimal dimensi panjang/lebar 1600 piksel)
        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min(1, 1600 / max($sourceWidth, $sourceHeight));
        $width = max(1, (int) round($sourceWidth * $scale));
        $height = max(1, (int) round($sourceHeight * $scale));
        $compressed = null;
        $target = null;

        try {
            // 4. Lakukan resampling dan kompresi kualitas bertingkat (dimulai dari 70%)
            while (true) {
                if ($target) {
                    imagedestroy($target);
                }
                $target = imagecreatetruecolor($width, $height);
                $white = imagecolorallocate($target, 255, 255, 255);
                imagefill($target, 0, 0, $white);
                imagecopyresampled($target, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);

                // Menguji kompresi kualitas (Quality Factor: 70%, 60%, 50%, dst.)
                foreach ([70, 60, 50, 40, 30] as $quality) {
                    ob_start();
                    $encoded = imagejpeg($target, null, $quality);
                    $contents = ob_get_clean();
                    if (! $encoded || $contents === false) {
                        throw new RuntimeException('Foto gagal dikompres.');
                    }
                    // Jika ukuran sudah masuk ke batas hemat, ambil hasilnya
                    if (strlen($contents) <= self::MAX_STORED_BYTES) {
                        $compressed = $contents;
                        break 2;
                    }
                }

                if ($width === 1 && $height === 1) {
                    throw new RuntimeException('Foto tidak dapat dikompres hingga batas ukuran 1 MB.');
                }

                // Jika masih terlalu besar, kurangi resolusi sedikit demi sedikit
                $scale = min(0.8, 320 / max($width, $height));
                $width = max(1, (int) floor($width * $scale));
                $height = max(1, (int) floor($height * $scale));
            }
        } finally {
            // Bersihkan memori RAM GD
            imagedestroy($source);
            if ($target) {
                imagedestroy($target);
            }
        }

        // 5. Simpan file terkompresi dengan nama acak unik di storage publik
        $path = 'complaints/'.uniqid($prefix, true).'.jpg';
        if (! Storage::disk('public')->put($path, $compressed)) {
            throw new RuntimeException('Foto terkompresi gagal disimpan.');
        }

        return $path;
    }
}
