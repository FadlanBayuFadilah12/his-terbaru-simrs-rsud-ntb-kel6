<?php
/**
 * Router tunggal untuk Vercel.
 * - Halaman .php dijalankan seperti biasa.
 * - Aset statis (css, js, gambar, font) disajikan dari sini.
 * - File lain (config, sql, sertifikat, dll.) TIDAK pernah disajikan.
 */
$root = realpath(__DIR__ . '/..');
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
if ($path === '' || $path === '/') {
    $path = '/index.php';
}

function tolak(int $kode, string $pesan): void {
    http_response_code($kode);
    header('Content-Type: text/plain; charset=utf-8');
    exit($pesan);
}

// Folder internal dan file tersembunyi tidak boleh diakses dari luar
$blokir = ['config', 'includes', 'api', 'vendor', 'sql'];
foreach (explode('/', trim($path, '/')) as $bagian) {
    if ($bagian === '..' || ($bagian !== '' && $bagian[0] === '.') || in_array($bagian, $blokir, true)) {
        tolak(403, 'Dilarang');
    }
}

$file = realpath($root . $path);
if ($file === false || !is_file($file) || !str_starts_with($file, $root . DIRECTORY_SEPARATOR)) {
    tolak(404, 'Halaman tidak ditemukan');
}

$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

if ($ext === 'php') {
    // Samakan variabel server supaya form dengan PHP_SELF tetap benar
    $_SERVER['SCRIPT_NAME']     = $path;
    $_SERVER['PHP_SELF']        = $path;
    $_SERVER['SCRIPT_FILENAME'] = $file;
    chdir(dirname($file));
    require $file;
    exit;
}

$mime = [
    'css'   => 'text/css; charset=utf-8',
    'js'    => 'application/javascript; charset=utf-8',
    'png'   => 'image/png',
    'jpg'   => 'image/jpeg',
    'jpeg'  => 'image/jpeg',
    'gif'   => 'image/gif',
    'webp'  => 'image/webp',
    'svg'   => 'image/svg+xml',
    'ico'   => 'image/x-icon',
    'woff'  => 'font/woff',
    'woff2' => 'font/woff2',
    'ttf'   => 'font/ttf',
];

if (!isset($mime[$ext])) {
    tolak(404, 'Halaman tidak ditemukan');
}

header('Content-Type: ' . $mime[$ext]);
header('Cache-Control: public, max-age=3600');
readfile($file);
