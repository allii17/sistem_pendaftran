<?php
// ============================================================
//  koneksi.php — Koneksi ke Database
//  Universitas Pamulang - Pemrograman Web II/3
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // ganti sesuai user MySQL Anda
define('DB_PASS', '');           // ganti sesuai password MySQL Anda
define('DB_NAME', 'sistem_pendaftaran');

function getKoneksi(): mysqli {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die(json_encode([
            'status'  => 'error',
            'pesan'   => 'Koneksi gagal: ' . $conn->connect_error
        ]));
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}
