<?php

namespace App\Services;

class MobileReleaseService
{
    /**
     * Dapatkan versi aplikasi mobile Android secara otomatis.
     * Urutan deteksi otomatis:
     * 1. File rapi_mobile/pubspec.yaml (otomatis sinkron saat Anda ubah versi Flutter di pubspec.yaml)
     * 2. File downloads/version.json di folder public (digunakan saat dideploy ke hosting cPanel/DomaiNesia)
     * 3. Konfigurasi env('APP_MOBILE_VERSION')
     * 4. Fallback default '1.0.3'
     */
    public static function version(): string
    {
        // 1. Cek langsung pubspec.yaml Flutter di folder rapi_mobile (sinkronisasi otomatis real-time)
        $pubspecCandidates = [
            base_path('../rapi_mobile/pubspec.yaml'),
            base_path('rapi_mobile/pubspec.yaml'),
        ];
        foreach ($pubspecCandidates as $candidate) {
            if (file_exists($candidate)) {
                $content = (string) file_get_contents($candidate);
                if (preg_match('/^version:\s*([0-9]+\.[0-9]+\.[0-9]+)/m', $content, $matches)) {
                    return trim($matches[1]);
                }
            }
        }

        // 2. Cek file version.json di folder public/downloads (untuk server hosting cPanel)
        $jsonPath = public_path('downloads/version.json');
        if (file_exists($jsonPath)) {
            $data = json_decode((string) file_get_contents($jsonPath), true);
            if (!empty($data['version'])) {
                return trim((string) $data['version']);
            }
        }

        // 3. Cek konfigurasi .env
        $envVersion = env('APP_MOBILE_VERSION') ?? config('app.mobile_version');
        if (!empty($envVersion)) {
            return trim((string) $envVersion);
        }

        // 4. Fallback default
        return '1.0.3';
    }

    /**
     * Dapatkan perkiraan ukuran file APK secara otomatis (contoh: '±25 MB').
     */
    public static function fileSize(): string
    {
        $apkPath = public_path('downloads/rapi-mobile.apk');
        if (file_exists($apkPath)) {
            $bytes = filesize($apkPath);
            $mb = round($bytes / (1024 * 1024));
            return "±{$mb} MB";
        }
        return '±25 MB';
    }

    /**
     * Dapatkan nama file download APK resmi (contoh: 'RAPI-Sekolah-v1.0.3.apk').
     */
    public static function downloadFileName(): string
    {
        return 'RAPI-Sekolah-v' . self::version() . '.apk';
    }
}
