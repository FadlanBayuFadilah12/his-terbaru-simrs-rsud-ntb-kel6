<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/app/auth.php';
require_once dirname(__DIR__) . '/app/permissions.php';
require_once dirname(__DIR__) . '/app/schema.php';
$user = require_login($pdo);
$menuData = require dirname(__DIR__) . '/app/menu.php';
$menus = $menuData['menus'];
$roles = $menuData['roles'];
$isITAdmin = $menuData['is_it_admin'];
$moduleDefinitions = require dirname(__DIR__) . '/app/modules_config.php';
$page = $_GET['page'] ?? 'dashboard';
$allowedPages = array_merge(['dashboard','users','audit','master'], array_keys($moduleDefinitions));
if (!in_array($page, $allowedPages, true)) $page = 'dashboard';
if (!isset($menus[$page])) { http_response_code(403); $page = 'dashboard'; $denied = true; } else $denied = false;
$menuLabels = ['dashboard'=>'Dashboard','users'=>'Manajemen Pengguna','audit'=>'Audit Trail','master'=>'Master Data'];
foreach ($moduleDefinitions as $key=>$definition) $menuLabels[$key] = $definition['label'];
$pageTitle = $menuLabels[$page] ?? 'Dashboard';
$csrf = csrf_token();
$flash = flash_get();
$workflowPages = ['outpatient','inpatient','igd','emr','nursing','cpoe','laboratory','radiology','pharmacy','billing','inventory','reporting'];
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($pageTitle) ?> | SIMRS RSUD NTB</title>
<link rel="stylesheet" href="<?= APP_BASE_URL ?>/assets/simrs.css">
</head>
<body>
<div class="app">
  <aside class="sidebar">
    <div class="brand"><div class="brand-icon">✚</div><div><strong>SIMRS RSUD NTB</strong><span>Portal Terpadu</span></div></div>
    <div class="nav-label">MENU <?= e(implode(' / ', $roles)) ?></div>
    <nav class="nav">
      <?php foreach ($menus as $route=>$item): ?>
        <a href="<?= APP_BASE_URL ?>/index.php?page=<?= e($route) ?>" class="<?= $route===$page?'active':'' ?>"><span class="ico"><?= e($item['icon']) ?></span><span><?= e($item['label']) ?></span></a>
      <?php endforeach; ?>
    </nav>
    <div class="nav-foot"><div class="notice"><b><?= e($user['nama_tampilan']) ?></b><p><?= e(implode(', ', $roles)) ?></p></div></div>
  </aside>
  <section class="main">
    <header class="topbar">
      <div><div class="top-title">SIMRS RSUD NTB</div><div class="top-sub">Sistem Informasi Manajemen Rumah Sakit · <?= e($pageTitle) ?></div></div>
      <div class="user-chip"><div class="avatar"><?= e(strtoupper(substr((string)$user['nama_tampilan'],0,1))) ?></div><div><b><?= e($user['nama_tampilan']) ?></b><div class="top-sub"><?= e($user['username']) ?> · <?= e(implode(', ', $roles)) ?></div></div><form method="post" action="<?= APP_BASE_URL ?>/logout.php"><input type="hidden" name="csrf" value="<?= e($csrf) ?>"><button class="btn" type="submit">Keluar</button></form></div>
    </header>
    <main class="content">
      <div class="crumb">SIMRS RSUD NTB / <b><?= e($pageTitle) ?></b></div>
      <?php if ($denied): ?><div class="flash error">Akses halaman ditolak untuk role akun ini.</div><?php endif; ?>
      <?php if ($flash): ?><div class="flash <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
      <?php
        if ($page === 'dashboard') require dirname(__DIR__) . '/app/pages/dashboard.php';
        elseif ($page === 'users') require dirname(__DIR__) . '/app/pages/users.php';
        elseif ($page === 'audit') require dirname(__DIR__) . '/app/pages/audit.php';
        elseif (in_array($page, $workflowPages, true)) require dirname(__DIR__) . '/app/pages/workflow.php';
        else require dirname(__DIR__) . '/app/pages/module.php';
      ?>
    </main>
  </section>
</div>
</body>
</html>
