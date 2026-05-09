-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 09 Bulan Mei 2026 pada 13.47
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sistem_pendaftaran`
--

DELIMITER $$
--
-- Prosedur
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_cari_pendaftar` (IN `p_keyword` VARCHAR(100))   BEGIN
  SELECT * FROM v_rekap_pendaftar
  WHERE nama_pendaftar LIKE CONCAT('%', p_keyword, '%')
     OR kode_pendaftar LIKE CONCAT('%', p_keyword, '%');
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_tambah_pendaftar` (IN `p_nama` VARCHAR(100), IN `p_jenis_kelamin` ENUM('Laki-Laki','Perempuan'), IN `p_ttl` VARCHAR(100), IN `p_asal_sekolah` VARCHAR(150), IN `p_kode_gedung` CHAR(1), IN `p_nomor_urut` TINYINT, IN `p_keterangan` TINYINT, IN `p_nilai_mat` DECIMAL(5,2), IN `p_nilai_bindo` DECIMAL(5,2), IN `p_nilai_bing` DECIMAL(5,2))   BEGIN
  DECLARE v_kode VARCHAR(10);
  SET v_kode = CONCAT(p_kode_gedung, LPAD(p_nomor_urut, 2, '0'), '-', p_keterangan);

  INSERT INTO pendaftar
    (kode_pendaftar, nama_pendaftar, jenis_kelamin, ttl, asal_sekolah,
     kode_gedung, nomor_urut, keterangan_no,
     nilai_mat, nilai_bindo, nilai_bing)
  VALUES
    (v_kode, p_nama, p_jenis_kelamin, p_ttl, p_asal_sekolah,
     p_kode_gedung, p_nomor_urut, p_keterangan,
     p_nilai_mat, p_nilai_bindo, p_nilai_bing);

  SELECT LAST_INSERT_ID() AS id_baru, v_kode AS kode_pendaftar;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `gedung`
--

CREATE TABLE `gedung` (
  `kode_gedung` char(1) NOT NULL,
  `nama_gedung` varchar(50) NOT NULL,
  `kapasitas` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `gedung`
--

INSERT INTO `gedung` (`kode_gedung`, `nama_gedung`, `kapasitas`) VALUES
('A', 'Gedung A', 100),
('B', 'Gedung B', 100),
('V', 'Viktor', 80);

-- --------------------------------------------------------

--
-- Struktur dari tabel `pendaftar`
--

CREATE TABLE `pendaftar` (
  `id` int(11) NOT NULL,
  `kode_pendaftar` varchar(10) NOT NULL,
  `nama_pendaftar` varchar(100) NOT NULL,
  `jenis_kelamin` enum('Laki-Laki','Perempuan') NOT NULL,
  `ttl` varchar(100) NOT NULL,
  `asal_sekolah` varchar(150) NOT NULL,
  `kode_gedung` char(1) NOT NULL,
  `nomor_urut` tinyint(4) NOT NULL,
  `keterangan_no` tinyint(4) NOT NULL,
  `nilai_mat` decimal(5,2) NOT NULL DEFAULT 0.00,
  `nilai_bindo` decimal(5,2) NOT NULL DEFAULT 0.00,
  `nilai_bing` decimal(5,2) NOT NULL DEFAULT 0.00,
  `rata_rata` decimal(5,2) GENERATED ALWAYS AS (round((`nilai_mat` + `nilai_bindo` + `nilai_bing`) / 3,2)) STORED,
  `keterangan_lulus` varchar(20) GENERATED ALWAYS AS (case when round((`nilai_mat` + `nilai_bindo` + `nilai_bing`) / 3,2) >= 70 then 'Lulus' when round((`nilai_mat` + `nilai_bindo` + `nilai_bing`) / 3,2) >= 60 then 'Cadangan' else 'Tidak Lulus' end) STORED,
  `tanggal_daftar` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pendaftar`
--

INSERT INTO `pendaftar` (`id`, `kode_pendaftar`, `nama_pendaftar`, `jenis_kelamin`, `ttl`, `asal_sekolah`, `kode_gedung`, `nomor_urut`, `keterangan_no`, `nilai_mat`, `nilai_bindo`, `nilai_bing`, `tanggal_daftar`) VALUES
(1, 'A02-1', 'Nur Ali Mahpudin', 'Laki-Laki', 'Tangerang, 15-03-2001', 'SMA Negeri 3 Pamulang', 'A', 2, 1, 80.00, 75.00, 70.00, '2026-05-09 18:46:44'),
(2, 'A05-1', 'Siti Rahayu', 'Perempuan', 'Jakarta, 22-07-2002', 'SMK Bina Karya Pamulang', 'A', 5, 1, 65.00, 70.00, 60.00, '2026-05-09 18:46:44'),
(3, 'B03-2', 'Deni Firmansyah', 'Laki-Laki', 'Bogor, 10-11-2001', 'SMA Negeri 1 Bogor', 'B', 3, 2, 55.00, 60.00, 50.00, '2026-05-09 18:46:44'),
(4, 'B07-1', 'Anisa Putri', 'Perempuan', 'Depok, 04-05-2002', 'SMA Al-Azhar Depok', 'B', 7, 1, 90.00, 85.00, 88.00, '2026-05-09 18:46:44'),
(5, 'V01-3', 'Rizky Pratama', 'Laki-Laki', 'Tangerang, 30-09-2001', 'SMA Negeri 5 Tangerang', 'V', 1, 3, 72.00, 68.00, 74.00, '2026-05-09 18:46:44');

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `v_rekap_pendaftar`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `v_rekap_pendaftar` (
`id` int(11)
,`kode_pendaftar` varchar(10)
,`nama_pendaftar` varchar(100)
,`jenis_kelamin` enum('Laki-Laki','Perempuan')
,`ttl` varchar(100)
,`asal_sekolah` varchar(150)
,`nama_gedung` varchar(50)
,`nilai_mat` decimal(5,2)
,`nilai_bindo` decimal(5,2)
,`nilai_bing` decimal(5,2)
,`rata_rata` decimal(5,2)
,`keterangan_lulus` varchar(20)
,`tanggal_daftar` datetime
);

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `v_statistik`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `v_statistik` (
`total_pendaftar` bigint(21)
,`jumlah_lulus` decimal(22,0)
,`jumlah_cadangan` decimal(22,0)
,`jumlah_tidak_lulus` decimal(22,0)
,`rata_rata_keseluruhan` decimal(6,2)
);

-- --------------------------------------------------------

--
-- Struktur untuk view `v_rekap_pendaftar`
--
DROP TABLE IF EXISTS `v_rekap_pendaftar`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_rekap_pendaftar`  AS SELECT `p`.`id` AS `id`, `p`.`kode_pendaftar` AS `kode_pendaftar`, `p`.`nama_pendaftar` AS `nama_pendaftar`, `p`.`jenis_kelamin` AS `jenis_kelamin`, `p`.`ttl` AS `ttl`, `p`.`asal_sekolah` AS `asal_sekolah`, `g`.`nama_gedung` AS `nama_gedung`, `p`.`nilai_mat` AS `nilai_mat`, `p`.`nilai_bindo` AS `nilai_bindo`, `p`.`nilai_bing` AS `nilai_bing`, `p`.`rata_rata` AS `rata_rata`, `p`.`keterangan_lulus` AS `keterangan_lulus`, `p`.`tanggal_daftar` AS `tanggal_daftar` FROM (`pendaftar` `p` join `gedung` `g` on(`p`.`kode_gedung` = `g`.`kode_gedung`)) ORDER BY `p`.`kode_pendaftar` ASC ;

-- --------------------------------------------------------

--
-- Struktur untuk view `v_statistik`
--
DROP TABLE IF EXISTS `v_statistik`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_statistik`  AS SELECT count(0) AS `total_pendaftar`, sum(case when `pendaftar`.`keterangan_lulus` = 'Lulus' then 1 else 0 end) AS `jumlah_lulus`, sum(case when `pendaftar`.`keterangan_lulus` = 'Cadangan' then 1 else 0 end) AS `jumlah_cadangan`, sum(case when `pendaftar`.`keterangan_lulus` = 'Tidak Lulus' then 1 else 0 end) AS `jumlah_tidak_lulus`, round(avg(`pendaftar`.`rata_rata`),2) AS `rata_rata_keseluruhan` FROM `pendaftar` ;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `gedung`
--
ALTER TABLE `gedung`
  ADD PRIMARY KEY (`kode_gedung`);

--
-- Indeks untuk tabel `pendaftar`
--
ALTER TABLE `pendaftar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_pendaftar` (`kode_pendaftar`),
  ADD KEY `fk_gedung` (`kode_gedung`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `pendaftar`
--
ALTER TABLE `pendaftar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `pendaftar`
--
ALTER TABLE `pendaftar`
  ADD CONSTRAINT `fk_gedung` FOREIGN KEY (`kode_gedung`) REFERENCES `gedung` (`kode_gedung`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
