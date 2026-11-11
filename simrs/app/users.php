
<?php

// Periksa hak akses pengguna yang sedang login.
$accessQuery = $pdo->prepare("
    SELECT COUNT(*)
    FROM user_roles ur
    JOIN roles r
        ON r.id_role = ur.id_role
    JOIN role_permissions rp
        ON rp.id_role = r.id_role
    JOIN permissions p
        ON p.id_permission = rp.id_permission
    WHERE ur.id_user = :id_user
      AND r.status_aktif = 1
      AND p.kode_permission = 'user_management.access'
");

$accessQuery->execute([
    'id_user' => $user['id_user']
]);

$canAccess = (int) $accessQuery->fetchColumn() > 0;

if (!$canAccess):
    http_response_code(403);
?>

    <div class="notice error">
        Kamu tidak memiliki izin untuk mengakses
        Manajemen Pengguna.
    </div>

<?php
    return;
endif;


// Ambil daftar pengguna dari database.
$userQuery = $pdo->query("
    SELECT
        u.id_user,
        u.username,
        u.nama_tampilan,
        u.status_aktif,
        u.last_login,
        pg.nama AS nama_pegawai,
        pg.peran,
        pg.spesialisasi,
        r.kode_role,
        r.nama_role
    FROM users u
    INNER JOIN pegawai pg
        ON pg.id_pegawai = u.id_pegawai
    LEFT JOIN user_roles ur
        ON ur.id_user = u.id_user
       AND ur.is_default = 1
    LEFT JOIN roles r
        ON r.id_role = ur.id_role
    ORDER BY u.id_user ASC
");

$users = $userQuery->fetchAll();

$totalUsers = count($users);
$activeUsers = 0;

foreach ($users as $account) {
    if ((int) $account['status_aktif'] === 1) {
        $activeUsers++;
    }
}

$h = static function ($value): string {
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
};

?>

<div class="page-head">

    <div>
        <h1>Manajemen Pengguna</h1>

        <p>
            Pengelolaan akun dan hak akses pengguna
            SIMRS RSUD NTB.
        </p>
    </div>

</div>


<!-- Ringkasan pengguna -->
<div class="grid g2">

    <div class="stat">
        <div class="label">Total Akun</div>

        <div class="value">
            <?= number_format($totalUsers) ?>
        </div>

        <div class="foot">
            Seluruh akun yang terdaftar
        </div>
    </div>

    <div class="stat">
        <div class="label">Akun Aktif</div>

        <div class="value">
            <?= number_format($activeUsers) ?>
        </div>

        <div class="foot">
            Pengguna dengan status aktif
        </div>
    </div>

</div>

<br>


<!-- Tabel pengguna -->
<section class="card">

    <div class="row">

        <h2>Daftar Pengguna</h2>

        <span class="spacer"></span>

        <span class="pill blue">
            <?= $totalUsers ?> Pengguna
        </span>

    </div>

    <div class="table-wrap">

        <table>

            <thead>
                <tr>
                    <th>No.</th>
                    <th>Username</th>
                    <th>Nama Pengguna</th>
                    <th>Jabatan</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Login Terakhir</th>
                </tr>
            </thead>

            <tbody>

                <?php if (empty($users)): ?>

                    <tr>
                        <td colspan="7" class="empty">
                            Belum terdapat pengguna.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($users as $i => $account): ?>

                        <tr>

                            <td>
                                <?= $i + 1 ?>
                            </td>

                            <td>
                                <strong>
                                    <?= $h($account['username']) ?>
                                </strong>
                            </td>

                            <td>
                                <?= $h($account['nama_tampilan']) ?>
                            </td>

                            <td>
                                <?= $h($account['peran']) ?>
                            </td>

                            <td>
                                <span class="pill blue">
                                    <?= $h($account['kode_role'] ?: 'Belum ada role') ?>
                                </span>
                            </td>

                            <td>

                                <?php if ((int) $account['status_aktif'] === 1): ?>

                                    <span class="pill ok">
                                        Aktif
                                    </span>

                                <?php else: ?>

                                    <span class="pill bad">
                                        Nonaktif
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>
                                <?= $account['last_login']
                                    ? $h($account['last_login'])
                                    : 'Belum pernah login'
                                ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>
