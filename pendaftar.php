<?php
// ============================================================
//  pendaftar.php — CRUD Pendaftar (REST-style)
//  Universitas Pamulang - Pemrograman Web II/3
//
//  GET    pendaftar.php            → semua data
//  GET    pendaftar.php?id=5       → satu data
//  GET    pendaftar.php?cari=ali   → cari nama/kode
//  POST   pendaftar.php            → tambah (JSON body)
//  PUT    pendaftar.php?id=5       → update (JSON body)
//  DELETE pendaftar.php?id=5       → hapus
// ============================================================

require_once 'koneksi.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$conn   = getKoneksi();
$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id'])   ? (int)$_GET['id']   : null;
$cari   = isset($_GET['cari']) ? trim($_GET['cari']) : null;

// ────────────────────────────────────────────────────────────
// Fungsi bantu
// ────────────────────────────────────────────────────────────

function buatKode(string $gedung, int $nomor, int $ket): string {
    return strtoupper($gedung) . str_pad($nomor, 2, '0', STR_PAD_LEFT) . '-' . $ket;
}

function hitungKeterangan(float $rata): string {
    if ($rata >= 70) return 'Lulus';
    if ($rata >= 60) return 'Cadangan';
    return 'Tidak Lulus';
}

function responJSON(array $data, int $kode = 200): void {
    http_response_code($kode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function bacaBody(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        responJSON(['status' => 'error', 'pesan' => 'Body JSON tidak valid'], 400);
    }
    return $data;
}

function validasi(array $d): void {
    $wajib = ['nama_pendaftar','jenis_kelamin','ttl','asal_sekolah',
              'kode_gedung','nomor_urut','keterangan_no',
              'nilai_mat','nilai_bindo','nilai_bing'];
    foreach ($wajib as $f) {
        if (!isset($d[$f]) || $d[$f] === '') {
            responJSON(['status' => 'error', 'pesan' => "Field '$f' wajib diisi"], 400);
        }
    }
    if (!in_array($d['jenis_kelamin'], ['Laki-Laki','Perempuan'])) {
        responJSON(['status' => 'error', 'pesan' => 'Jenis kelamin tidak valid'], 400);
    }
    foreach (['nilai_mat','nilai_bindo','nilai_bing'] as $n) {
        $v = (float)$d[$n];
        if ($v < 0 || $v > 100) {
            responJSON(['status' => 'error', 'pesan' => "$n harus antara 0-100"], 400);
        }
    }
}

// ────────────────────────────────────────────────────────────
// GET — Ambil data
// ────────────────────────────────────────────────────────────
if ($method === 'GET') {

    // Statistik ringkasan
    if (isset($_GET['statistik'])) {
        $res = $conn->query("SELECT * FROM v_statistik");
        responJSON(['status' => 'ok', 'data' => $res->fetch_assoc()]);
    }

    // Cari pendaftar
    if ($cari !== null) {
        $stmt = $conn->prepare("CALL sp_cari_pendaftar(?)");
        $stmt->bind_param('s', $cari);
        $stmt->execute();
        $hasil = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        responJSON(['status' => 'ok', 'total' => count($hasil), 'data' => $hasil]);
    }

    // Satu pendaftar by ID
    if ($id !== null) {
        $stmt = $conn->prepare("SELECT * FROM v_rekap_pendaftar WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) responJSON(['status' => 'error', 'pesan' => 'Data tidak ditemukan'], 404);
        responJSON(['status' => 'ok', 'data' => $row]);
    }

    // Semua data
    $res  = $conn->query("SELECT * FROM v_rekap_pendaftar");
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    responJSON(['status' => 'ok', 'total' => count($rows), 'data' => $rows]);
}

// ────────────────────────────────────────────────────────────
// POST — Tambah pendaftar baru
// ────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $d = bacaBody();
    validasi($d);

    $kode  = buatKode($d['kode_gedung'], (int)$d['nomor_urut'], (int)$d['keterangan_no']);
    $rata  = ((float)$d['nilai_mat'] + (float)$d['nilai_bindo'] + (float)$d['nilai_bing']) / 3;

    // Cek duplikat kode
    $cekStmt = $conn->prepare("SELECT id FROM pendaftar WHERE kode_pendaftar = ?");
    $cekStmt->bind_param('s', $kode);
    $cekStmt->execute();
    if ($cekStmt->get_result()->num_rows > 0) {
        responJSON(['status' => 'error', 'pesan' => "Kode $kode sudah terdaftar"], 409);
    }

    $stmt = $conn->prepare("
        INSERT INTO pendaftar
          (kode_pendaftar, nama_pendaftar, jenis_kelamin, ttl, asal_sekolah,
           kode_gedung, nomor_urut, keterangan_no, nilai_mat, nilai_bindo, nilai_bing)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        'ssssssiiddd',
        $kode,
        $d['nama_pendaftar'],
        $d['jenis_kelamin'],
        $d['ttl'],
        $d['asal_sekolah'],
        $d['kode_gedung'],
        $d['nomor_urut'],
        $d['keterangan_no'],
        $d['nilai_mat'],
        $d['nilai_bindo'],
        $d['nilai_bing']
    );

    if (!$stmt->execute()) {
        responJSON(['status' => 'error', 'pesan' => $stmt->error], 500);
    }

    responJSON([
        'status'          => 'ok',
        'pesan'           => 'Pendaftar berhasil ditambahkan',
        'id'              => $conn->insert_id,
        'kode_pendaftar'  => $kode,
        'rata_rata'       => round($rata, 2),
        'keterangan_lulus'=> hitungKeterangan($rata)
    ], 201);
}

// ────────────────────────────────────────────────────────────
// PUT — Update nilai atau data pendaftar
// ────────────────────────────────────────────────────────────
if ($method === 'PUT') {
    if (!$id) responJSON(['status' => 'error', 'pesan' => 'Parameter id diperlukan'], 400);
    $d = bacaBody();

    // Cek keberadaan data
    $cekStmt = $conn->prepare("SELECT id FROM pendaftar WHERE id = ?");
    $cekStmt->bind_param('i', $id);
    $cekStmt->execute();
    if ($cekStmt->get_result()->num_rows === 0) {
        responJSON(['status' => 'error', 'pesan' => 'Data tidak ditemukan'], 404);
    }

    // Bangun SET dinamis hanya field yang dikirim
    $allowed = ['nama_pendaftar','jenis_kelamin','ttl','asal_sekolah',
                'nilai_mat','nilai_bindo','nilai_bing'];
    $setParts = []; $params = []; $types = '';
    foreach ($allowed as $col) {
        if (isset($d[$col])) {
            $setParts[] = "$col = ?";
            $params[]   = $d[$col];
            $types     .= in_array($col, ['nilai_mat','nilai_bindo','nilai_bing']) ? 'd' : 's';
        }
    }
    if (empty($setParts)) responJSON(['status' => 'error', 'pesan' => 'Tidak ada field untuk diupdate'], 400);

    $params[] = $id; $types .= 'i';
    $sql  = "UPDATE pendaftar SET " . implode(', ', $setParts) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) responJSON(['status' => 'error', 'pesan' => $stmt->error], 500);
    responJSON(['status' => 'ok', 'pesan' => "Data ID $id berhasil diupdate"]);
}

// ────────────────────────────────────────────────────────────
// DELETE — Hapus pendaftar
// ────────────────────────────────────────────────────────────
if ($method === 'DELETE') {
    if (!$id) responJSON(['status' => 'error', 'pesan' => 'Parameter id diperlukan'], 400);

    $stmt = $conn->prepare("DELETE FROM pendaftar WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        responJSON(['status' => 'error', 'pesan' => 'Data tidak ditemukan'], 404);
    }
    responJSON(['status' => 'ok', 'pesan' => "Data ID $id berhasil dihapus"]);
}

$conn->close();
