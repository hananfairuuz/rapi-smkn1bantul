<?php
/**
 * Automated CI/CD Deploy Hook for Shared Hosting (DomaiNesia Nimbus One)
 *
 * Extracts release.zip, synchronizes assets to public directory, and runs Artisan
 * database migrations & caches without requiring terminal/SSH access.
 * Protected with DEPLOY_SECRET_KEY.
 */

declare(strict_types=1);

// Disable execution time limit for extraction and migrations
@set_time_limit(300);
@ini_set('memory_limit', '256M');

header('Content-Type: application/json; charset=UTF-8');

// 1. Locate application root
$possibleRoots = [
    realpath(__DIR__ . '/..'),               // If DocumentRoot points to public/
    realpath(__DIR__ . '/../rapi_app'),      // If inside public_html and app is in ../rapi_app
    dirname(__DIR__),
];

$appRoot = null;
foreach ($possibleRoots as $path) {
    if ($path && file_exists($path . '/artisan') && file_exists($path . '/bootstrap/app.php')) {
        $appRoot = $path;
        break;
    }
}

if (!$appRoot) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Application root directory could not be located.',
    ], JSON_PRETTY_PRINT);
    exit;
}

// 2. Read DEPLOY_SECRET_KEY from .env
function getEnvValue(string $filePath, string $key): ?string {
    if (!file_exists($filePath) || !is_readable($filePath)) {
        return null;
    }
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return null;
    }
    $prefix = $key . '=';
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_starts_with($line, $prefix)) {
            $value = substr($line, strlen($prefix));
            return trim($value, " \t\n\r\0\x0B\"'");
        }
    }
    return null;
}

$secretInEnv = getEnvValue($appRoot . '/.env', 'DEPLOY_SECRET_KEY');
if (empty($secretInEnv) || strlen($secretInEnv) < 16) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'DEPLOY_SECRET_KEY is not configured or too short in .env file.',
    ], JSON_PRETTY_PRINT);
    exit;
}

// 3. Authenticate incoming request
$incomingKey = $_SERVER['HTTP_X_DEPLOY_KEY']
    ?? $_POST['secret']
    ?? $_GET['secret']
    ?? '';

if (!hash_equals($secretInEnv, (string) $incomingKey)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized: Invalid deploy secret key.',
    ], JSON_PRETTY_PRINT);
    exit;
}

// 4. Locate and extract release.zip
$possibleZips = [
    $appRoot . '/release.zip',
    dirname($appRoot) . '/release.zip',
    __DIR__ . '/release.zip',
];

$extracted = false;
$zipFileUsed = null;

foreach ($possibleZips as $zipPath) {
    if (file_exists($zipPath) && is_readable($zipPath)) {
        if (!class_exists('ZipArchive')) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'PHP ZipArchive extension is not enabled in cPanel.',
            ], JSON_PRETTY_PRINT);
            exit;
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) === true) {
            $zip->extractTo($appRoot);
            $zip->close();
            @unlink($zipPath);
            $extracted = true;
            $zipFileUsed = $zipPath;
            break;
        }
    }
}

// 5. Synchronize public assets if running inside public_html separate from app/public
function syncDirectories(string $source, string $target): void {
    if (!is_dir($source)) {
        return;
    }
    if (!is_dir($target)) {
        @mkdir($target, 0755, true);
    }
    $dir = opendir($source);
    if (!$dir) {
        return;
    }
    while (false !== ($file = readdir($dir))) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $srcPath = $source . '/' . $file;
        $dstPath = $target . '/' . $file;
        if (is_dir($srcPath)) {
            syncDirectories($srcPath, $dstPath);
        } else {
            @copy($srcPath, $dstPath);
        }
    }
    closedir($dir);
}

if ($appRoot . '/public' !== __DIR__) {
    // If running in public_html, copy compiled build assets to public_html/build
    if (is_dir($appRoot . '/public/build')) {
        syncDirectories($appRoot . '/public/build', __DIR__ . '/build');
    }
}

// 6. Bootstrap Laravel and execute Artisan tasks
$tasks = [];
try {
    require_once $appRoot . '/vendor/autoload.php';
    $app = require_once $appRoot . '/bootstrap/app.php';

    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    $commands = [
        'migrate' => ['--force' => true],
        'config:cache' => [],
        'route:cache' => [],
        'view:cache' => [],
        'storage:link' => [],
    ];

    foreach ($commands as $cmd => $args) {
        try {
            $exitCode = \Illuminate\Support\Facades\Artisan::call($cmd, $args);
            $tasks[$cmd] = [
                'exit_code' => $exitCode,
                'output' => trim(\Illuminate\Support\Facades\Artisan::output()),
            ];
        } catch (\Throwable $taskEx) {
            $tasks[$cmd] = [
                'exit_code' => 1,
                'output' => 'Error: ' . $taskEx->getMessage(),
            ];
        }
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Laravel bootstrap failed: ' . $e->getMessage(),
        'extracted' => $extracted,
        'tasks' => $tasks,
    ], JSON_PRETTY_PRINT);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'CI/CD deployment finished successfully.',
    'app_root' => $appRoot,
    'archive_extracted' => $extracted,
    'archive_path' => $zipFileUsed,
    'tasks' => $tasks,
    'timestamp' => date('Y-m-d H:i:s T'),
], JSON_PRETTY_PRINT);

