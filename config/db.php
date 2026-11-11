<?php
/**
 * Koneksi database (TiDB Cloud / MySQL) + penyimpanan sesi di database.
 * Kredensial dibaca dari Environment Variables Vercel:
 *   DB_HOST, DB_NAME, DB_USER, DB_PASS  (opsional: DB_PORT, default 4000)
 */
$sertifikat = __DIR__ . '/../ca.pem';

try {
    $opsi = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    if (is_file($sertifikat)) {
        $opsi[PDO::MYSQL_ATTR_SSL_CA] = $sertifikat;
    }

    $pdo = new PDO(
        'mysql:host=' . getenv('DB_HOST')
        . ';port=' . (getenv('DB_PORT') ?: '4000')
        . ';dbname=' . getenv('DB_NAME')
        . ';charset=utf8mb4',
        getenv('DB_USER'),
        getenv('DB_PASS'),
        $opsi
    );
} catch (Throwable $e) {
    error_log('Koneksi database gagal: ' . $e->getMessage());
    http_response_code(500);
    exit('Koneksi database gagal. Periksa Environment Variables dan ca.pem.');
}

/**
 * Sesi login disimpan di tabel `sessions` (lihat sql/sessions.sql),
 * karena sesi berbasis file tidak andal di Vercel.
 */
class DbSession implements SessionHandlerInterface
{
    public function __construct(private PDO $pdo) {}

    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }

    public function read(string $id): string|false
    {
        $s = $this->pdo->prepare('SELECT data FROM sessions WHERE id = ?');
        $s->execute([$id]);
        return (string) ($s->fetchColumn() ?: '');
    }

    public function write(string $id, string $data): bool
    {
        return $this->pdo
            ->prepare('REPLACE INTO sessions (id, data, updated_at) VALUES (?, ?, NOW())')
            ->execute([$id, $data]);
    }

    public function destroy(string $id): bool
    {
        return $this->pdo->prepare('DELETE FROM sessions WHERE id = ?')->execute([$id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        return $this->pdo->exec(
            'DELETE FROM sessions WHERE updated_at < NOW() - INTERVAL ' . (int) $max_lifetime . ' SECOND'
        );
    }
}

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => !empty($_SERVER['HTTPS']) || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_set_save_handler(new DbSession($pdo), true);

// Catatan: panggil session_start() di halaman Anda SETELAH require file ini.
