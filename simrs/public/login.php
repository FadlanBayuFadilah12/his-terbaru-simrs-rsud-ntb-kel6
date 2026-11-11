<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/app/auth.php';
if (current_user($pdo)) redirect_to(APP_BASE_URL . '/index.php');
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!csrf_check($_POST['csrf']??null)) $error='Sesi formulir tidak valid. Muat ulang halaman.';
    else {
        $username=trim((string)($_POST['username']??''));
        $password=(string)($_POST['password']??'');
        if ($username==='' || $password==='') $error='Username dan password wajib diisi.';
        elseif (login_user($pdo,$username,$password)) redirect_to(APP_BASE_URL . '/index.php');
        else $error='Username atau password tidak sesuai, atau akun tidak aktif.';
    }
}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login | SIMRS RSUD NTB</title><link rel="stylesheet" href="<?= APP_BASE_URL ?>/assets/simrs.css"></head><body><main class="login-page"><section class="login-card"><div class="login-banner"><div><div class="logo-mark">✚</div><h1>SIMRS RSUD NTB<br>Terpadu</h1><p>Sistem Informasi Manajemen Rumah Sakit</p></div><div class="banner-foot">Registrasi • Rekam Medis • Penunjang • Farmasi • Billing • Inventaris</div></div><div class="login-body"><h2>Selamat datang</h2><p class="muted">Masukkan akun yang sudah terdaftar di database SIMRS.</p><?php if($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><div class="field"><label for="username">Username</label><input id="username" name="username" autocomplete="username" required autofocus></div><div class="field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required></div><button class="btn primary full" type="submit">Masuk ke SIMRS →</button></form><div class="login-help">Akun dan password diperiksa dari tabel users. Tidak ada akun demo bawaan.</div></div></section></main></body></html>
