<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ComplaintPhotoStorage
{
    private const MAX_STORED_BYTES = 1_000_000;

    public function store(UploadedFile $file): string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg')) {
            throw new RuntimeException('Ekstensi GD wajib tersedia untuk mengompres foto pengaduan.');
        }

        $source = @imagecreatefromstring(file_get_contents($file->getRealPath()));
        if ($source === false) {
            throw new RuntimeException('Foto tidak dapat dibuka untuk dikompres.');
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min(1, 1600 / max($sourceWidth, $sourceHeight));
        $width = max(1, (int) round($sourceWidth * $scale));
        $height = max(1, (int) round($sourceHeight * $scale));
        $compressed = null;
        $target = null;

        try {
            while (true) {
                if ($target) {
                    imagedestroy($target);
                }
                $target = imagecreatetruecolor($width, $height);
                $white = imagecolorallocate($target, 255, 255, 255);
                imagefill($target, 0, 0, $white);
                imagecopyresampled($target, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight);

                foreach ([82, 72, 62, 52, 42, 32, 22, 12] as $quality) {
                    ob_start();
                    $encoded = imagejpeg($target, null, $quality);
                    $contents = ob_get_clean();
                    if (! $encoded || $contents === false) {
                        throw new RuntimeException('Foto gagal dikompres.');
                    }
                    if (strlen($contents) <= self::MAX_STORED_BYTES) {
                        $compressed = $contents;
                        break 2;
                    }
                }

                if ($width === 1 && $height === 1) {
                    throw new RuntimeException('Foto tidak dapat dikompres hingga batas ukuran 1 MB.');
                }

                $scale = min(0.8, 320 / max($width, $height));
                $width = max(1, (int) floor($width * $scale));
                $height = max(1, (int) floor($height * $scale));
            }
        } finally {
            imagedestroy($source);
            if ($target) {
                imagedestroy($target);
            }
        }

        $path = 'complaints/'.uniqid('complaint_', true).'.jpg';
        if (! Storage::disk('public')->put($path, $compressed)) {
            throw new RuntimeException('Foto terkompresi gagal disimpan.');
        }

        return $path;
    }
}
