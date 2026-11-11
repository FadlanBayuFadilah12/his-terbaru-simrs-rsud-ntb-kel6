<?php
/**
 * SETUP SEKALI PAKAI: membuat semua tabel SIMRS di TiDB.
 * Taruh di root proyek, buka /setup-db.php sekali, lalu HAPUS file ini.
 * Aman dijalankan berulang (IF NOT EXISTS / INSERT IGNORE).
 */
require __DIR__ . '/config/db.php';
header('Content-Type: text/plain; charset=utf-8');

$sql = [

'users' => <<<'SQL'
CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  nama          VARCHAR(100) NOT NULL,
  role          ENUM('administrator','dokter','admisi','perawat','apoteker','laboratorium','kasir') NOT NULL,
  subrole       VARCHAR(50)  NULL,
  aktif         TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
)
SQL,

'pasien' => <<<'SQL'
CREATE TABLE IF NOT EXISTS pasien (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  no_rm          VARCHAR(20)  NOT NULL UNIQUE,
  nik            VARCHAR(20)  NULL,
  nama           VARCHAR(100) NOT NULL,
  tgl_lahir      DATE         NULL,
  jenis_kelamin  ENUM('L','P') NULL,
  alamat         VARCHAR(255) NULL,
  no_hp          VARCHAR(20)  NULL,
  penjamin       VARCHAR(30)  NOT NULL DEFAULT 'Umum',
  no_bpjs        VARCHAR(30)  NULL,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_pasien_nama (nama),
  INDEX idx_pasien_nik (nik)
)
SQL,

'kunjungan' => <<<'SQL'
CREATE TABLE IF NOT EXISTS kunjungan (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  no_kunjungan  VARCHAR(30)  NOT NULL UNIQUE,
  pasien_id     INT          NOT NULL,
  dokter_id     INT          NULL,
  poli          VARCHAR(50)  NULL,
  jenis         ENUM('rawat_jalan','igd','rawat_inap') NOT NULL DEFAULT 'rawat_jalan',
  keluhan       TEXT         NULL,
  status        ENUM('pendaftaran','perawat','dokter','lab','farmasi','kasir','selesai','batal')
                NOT NULL DEFAULT 'pendaftaran',
  petugas_admisi_id INT      NULL,
  tgl_daftar    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  tgl_selesai   DATETIME     NULL,
  INDEX idx_kunjungan_pasien (pasien_id),
  INDEX idx_kunjungan_status (status),
  INDEX idx_kunjungan_tgl (tgl_daftar)
)
SQL,

'asesmen_perawat' => <<<'SQL'
CREATE TABLE IF NOT EXISTS asesmen_perawat (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  kunjungan_id  INT          NOT NULL,
  perawat_id    INT          NULL,
  tekanan_darah VARCHAR(10)  NULL,
  nadi          INT          NULL,
  suhu          DECIMAL(4,1) NULL,
  respirasi     INT          NULL,
  berat_badan   DECIMAL(5,1) NULL,
  tinggi_badan  DECIMAL(5,1) NULL,
  keluhan_awal  TEXT         NULL,
  catatan       TEXT         NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_asesmen_kunjungan (kunjungan_id)
)
SQL,

'pemeriksaan' => <<<'SQL'
CREATE TABLE IF NOT EXISTS pemeriksaan (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  kunjungan_id  INT          NOT NULL,
  dokter_id     INT          NULL,
  subjektif     TEXT         NULL,
  objektif      TEXT         NULL,
  diagnosa      VARCHAR(255) NULL,
  kode_icd10    VARCHAR(10)  NULL,
  rencana       TEXT         NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_pemeriksaan_kunjungan (kunjungan_id)
)
SQL,

'obat' => <<<'SQL'
CREATE TABLE IF NOT EXISTS obat (
  id      INT AUTO_INCREMENT PRIMARY KEY,
  kode    VARCHAR(20)  NOT NULL UNIQUE,
  nama    VARCHAR(100) NOT NULL,
  satuan  VARCHAR(20)  NOT NULL DEFAULT 'tablet',
  stok    INT          NOT NULL DEFAULT 0,
  harga   INT          NOT NULL DEFAULT 0
)
SQL,

'resep' => <<<'SQL'
CREATE TABLE IF NOT EXISTS resep (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  kunjungan_id  INT          NOT NULL,
  dokter_id     INT          NULL,
  apoteker_id   INT          NULL,
  status        ENUM('menunggu','disiapkan','diserahkan') NOT NULL DEFAULT 'menunggu',
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_resep_kunjungan (kunjungan_id)
)
SQL,

'resep_item' => <<<'SQL'
CREATE TABLE IF NOT EXISTS resep_item (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  resep_id     INT          NOT NULL,
  obat_id      INT          NOT NULL,
  jumlah       INT          NOT NULL DEFAULT 1,
  aturan_pakai VARCHAR(100) NULL,
  INDEX idx_resepitem_resep (resep_id)
)
SQL,

'lab_jenis' => <<<'SQL'
CREATE TABLE IF NOT EXISTS lab_jenis (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  kode          VARCHAR(20)  NOT NULL UNIQUE,
  nama          VARCHAR(100) NOT NULL,
  satuan        VARCHAR(20)  NULL,
  nilai_normal  VARCHAR(50)  NULL,
  harga         INT          NOT NULL DEFAULT 0
)
SQL,

'lab_order' => <<<'SQL'
CREATE TABLE IF NOT EXISTS lab_order (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  kunjungan_id  INT          NOT NULL,
  dokter_id     INT          NULL,
  petugas_id    INT          NULL,
  status        ENUM('menunggu','diproses','selesai') NOT NULL DEFAULT 'menunggu',
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_laborder_kunjungan (kunjungan_id)
)
SQL,

'lab_order_item' => <<<'SQL'
CREATE TABLE IF NOT EXISTS lab_order_item (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  lab_order_id  INT          NOT NULL,
  lab_jenis_id  INT          NOT NULL,
  hasil         VARCHAR(255) NULL,
  selesai_at    DATETIME     NULL,
  INDEX idx_laborderitem_order (lab_order_id)
)
SQL,

'tagihan' => <<<'SQL'
CREATE TABLE IF NOT EXISTS tagihan (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  kunjungan_id  INT          NOT NULL UNIQUE,
  total         INT          NOT NULL DEFAULT 0,
  status        ENUM('belum_bayar','lunas') NOT NULL DEFAULT 'belum_bayar',
  metode_bayar  VARCHAR(30)  NULL,
  kasir_id      INT          NULL,
  dibayar_at    DATETIME     NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
)
SQL,

'tagihan_item' => <<<'SQL'
CREATE TABLE IF NOT EXISTS tagihan_item (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  tagihan_id  INT          NOT NULL,
  jenis       ENUM('administrasi','konsultasi','obat','lab','lainnya') NOT NULL,
  deskripsi   VARCHAR(150) NOT NULL,
  qty         INT          NOT NULL DEFAULT 1,
  harga       INT          NOT NULL DEFAULT 0,
  INDEX idx_tagihanitem_tagihan (tagihan_id)
)
SQL,

'log_aktivitas' => <<<'SQL'
CREATE TABLE IF NOT EXISTS log_aktivitas (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT          NULL,
  aksi        VARCHAR(100) NOT NULL,
  keterangan  TEXT         NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_log_user (user_id)
)
SQL,

'sessions' => <<<'SQL'
CREATE TABLE IF NOT EXISTS sessions (
  id         VARCHAR(128) PRIMARY KEY,
  data       MEDIUMTEXT,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
)
SQL,

'data contoh obat' => <<<'SQL'
INSERT IGNORE INTO obat (kode, nama, satuan, stok, harga) VALUES
  ('OBT001', 'Paracetamol 500 mg', 'tablet', 500, 1000),
  ('OBT002', 'Amoxicillin 500 mg', 'kapsul', 300, 2500),
  ('OBT003', 'Omeprazole 20 mg',   'kapsul', 200, 3000),
  ('OBT004', 'Cetirizine 10 mg',   'tablet', 250, 1500),
  ('OBT005', 'Antasida DOEN',      'tablet', 400,  800)
SQL,

'data contoh lab' => <<<'SQL'
INSERT IGNORE INTO lab_jenis (kode, nama, satuan, nilai_normal, harga) VALUES
  ('LAB001', 'Hemoglobin',         'g/dL',    '12 - 16',   25000),
  ('LAB002', 'Leukosit',           'ribu/uL', '4 - 11',    25000),
  ('LAB003', 'Gula Darah Sewaktu', 'mg/dL',   '< 200',     20000),
  ('LAB004', 'Kolesterol Total',   'mg/dL',   '< 200',     35000),
  ('LAB005', 'Asam Urat',          'mg/dL',   '3.5 - 7.2', 30000)
SQL,

];

echo "Database: " . (getenv('DB_NAME') ?: '(DB_NAME kosong)') . "\n\n";

foreach ($sql as $nama => $query) {
    try {
        $pdo->exec($query);
        echo "OK     $nama\n";
    } catch (Throwable $e) {
        echo "GAGAL  $nama: " . $e->getMessage() . "\n";
    }
}

echo "\nTabel yang ada sekarang:\n";
try {
    $tabel = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tabel as $t) {
        echo " - $t\n";
    }
    echo "\nTotal: " . count($tabel) . " tabel (seharusnya 15)\n";
} catch (Throwable $e) {
    echo "Gagal membaca daftar tabel: " . $e->getMessage() . "\n";
}

echo "\nSELESAI. Hapus file setup-db.php dari GitHub sekarang.\n";
