-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Waktu pembuatan: 16 Okt 2026 pada 08.10
-- Versi server: 10.4.28-MariaDB
-- Versi PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kampus_absensi_db`
--
CREATE DATABASE IF NOT EXISTS `kampus_absensi_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kampus_absensi_db`;

-- --------------------------------------------------------

--
-- Struktur dari tabel `absensi`
--

CREATE TABLE `absensi` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `jadwal_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `waktu_scan` time NOT NULL,
  `status_sistem` enum('Hadir','Izin','Sakit','Terlambat','Terlambat_Murni','Terlambat_Beralasan','Tidak Hadir') DEFAULT 'Hadir',
  `persentase_harian` decimal(5,2) NOT NULL,
  `alasan` text DEFAULT NULL,
  `bukti_path` varchar(255) DEFAULT NULL,
  `status_verifikasi` enum('Pending','Disetujui','Ditolak') DEFAULT 'Pending',
  `face_distance` varchar(20) DEFAULT NULL,
  `persentase_final` decimal(5,2) DEFAULT NULL,
  `catatan_dosen` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `jadwal`
--

CREATE TABLE `jadwal` (
  `id` int(11) NOT NULL,
  `matkul_id` int(11) NOT NULL,
  `dosen_id` int(11) NOT NULL,
  `hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `semester` varchar(20) NOT NULL,
  `tahun_ajaran` varchar(20) NOT NULL,
  `is_open` tinyint(1) DEFAULT 1 COMMENT '1=buka, 0=tutup',
  `manual_override` tinyint(1) DEFAULT 0 COMMENT '1=admin force buka/tutup'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `jadwal`
--

INSERT INTO `jadwal` (`id`, `matkul_id`, `dosen_id`, `hari`, `jam_mulai`, `jam_selesai`, `semester`, `tahun_ajaran`, `is_open`, `manual_override`) VALUES
(2, 2, 2, 'Senin', '14:15:00', '16:00:00', '4', '2024/2025', 1, 0),
(3, 3, 3, 'Selasa', '08:45:00', '10:30:00', '4', '2024/2025', 1, 0),
(4, 4, 4, 'Selasa', '10:50:00', '12:20:00', '4', '2024/2025', 1, 0),
(6, 5, 5, 'Rabu', '07:30:00', '09:15:00', '4', '2024/2025', 1, 0),
(8, 4, 4, 'Kamis', '07:30:00', '09:00:00', '4', '2024/2025', 1, 0),
(11, 6, 7, 'Kamis', '13:00:00', '14:10:00', '4', '2024/2025', 0, 1),
(12, 1, 1, 'Sabtu', '09:10:00', '10:40:00', '4', '2024/2025', 1, 0),
(13, 7, 3, 'Sabtu', '11:10:00', '12:20:00', '4', '2024/2025', 1, 0),
(14, 6, 6, 'Kamis', '10:50:00', '12:20:00', '4', '2025/2026', 1, 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `matkul`
--

CREATE TABLE `matkul` (
  `id` int(11) NOT NULL,
  `kode_matkul` varchar(20) NOT NULL,
  `nama_matkul` varchar(100) NOT NULL,
  `sks` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `matkul`
--

INSERT INTO `matkul` (`id`, `kode_matkul`, `nama_matkul`, `sks`) VALUES
(1, 'PBO', 'Pemrograman Berbasis Objek', 2),
(2, 'ITK', 'Instalasi dan Troubleshooting Komputer', 3),
(3, 'APSI', 'Analisa dan Perancangan Sistem Informasi', 3),
(4, 'PW', 'Pemrograman Web', 2),
(5, 'SD', 'Sistem Terdistribusi', 3),
(6, 'KDJK', 'Komunikasi Data dan Jaringan Komputer', 2),
(7, 'TECH', 'Technopreneurship II', 2);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nip_nim` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('mahasiswa','dosen','admin') NOT NULL,
  `foto_wajah_path` varchar(255) DEFAULT NULL,
  `face_descriptor` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nip_nim`, `nama`, `email`, `password`, `role`, `foto_wajah_path`, `face_descriptor`, `created_at`) VALUES
(1, 'D001', 'Dosen 01', 'dosen01@kampus.com', '$2y$10$yj4W5Komra3J/xKlKcwGJu20ZbH9vdABe7KiX9nB1phnLs3QdEwPe', 'dosen', NULL, NULL, '2026-08-02 12:52:17'),
(2, 'D002', 'Dosen 02', 'dosen02@kampus.com', '$2y$10$yj4W5Komra3J/xKlKcwGJu20ZbH9vdABe7KiX9nB1phnLs3QdEwPe', 'dosen', NULL, NULL, '2026-08-02 12:52:17'),
(3, 'D003', 'Dosen 03', 'dosen03@kampus.com', '$2y$10$yj4W5Komra3J/xKlKcwGJu20ZbH9vdABe7KiX9nB1phnLs3QdEwPe', 'dosen', NULL, NULL, '2026-08-02 12:52:17'),
(4, 'D004', 'Dosen 04', 'dosen04@kampus.com', '$2y$10$yj4W5Komra3J/xKlKcwGJu20ZbH9vdABe7KiX9nB1phnLs3QdEwPe', 'dosen', NULL, NULL, '2026-08-02 12:52:17'),
(5, 'D005', 'Dosen 05', 'dosen05@kampus.com', '$2y$10$yj4W5Komra3J/xKlKcwGJu20ZbH9vdABe7KiX9nB1phnLs3QdEwPe', 'dosen', NULL, NULL, '2026-08-02 12:52:17'),
(6, 'D006', 'Dosen 06', 'dosen06@kampus.com', '$2y$10$yj4W5Komra3J/xKlKcwGJu20ZbH9vdABe7KiX9nB1phnLs3QdEwPe', 'dosen', NULL, NULL, '2026-08-02 12:52:17'),
(7, 'D007', 'Dosen 07', 'dosen07@kampus.com', '$2y$10$yj4W5Komra3J/xKlKcwGJu20ZbH9vdABe7KiX9nB1phnLs3QdEwPe', 'dosen', NULL, NULL, '2026-08-02 12:52:17'),
(8, 'MHS008', 'Mahasiswa 08', 'mahasiswa08@student.kampus.com', '$2y$10$yj4W5Komra3J/xKlKcwGJu20ZbH9vdABe7KiX9nB1phnLs3QdEwPe', 'mahasiswa', NULL, NULL, '2026-08-02 12:52:17'),
(9, 'MHS009', 'Mahasiswa 09', 'mahasiswa09@student.kampus.com', '$2y$10$yj4W5Komra3J/xKlKcwGJu20ZbH9vdABe7KiX9nB1phnLs3QdEwPe', 'mahasiswa', NULL, NULL, '2026-08-02 12:52:17'),
(10, 'MHS010', 'Mahasiswa 10', 'mahasiswa10@student.kampus.com', '$2y$10$yj4W5Komra3J/xKlKcwGJu20ZbH9vdABe7KiX9nB1phnLs3QdEwPe', 'mahasiswa', NULL, NULL, '2026-08-02 12:52:17'),
(11, 'MHS011', 'Mahasiswa 11', 'mahasiswa11@student.kampus.com', '$2y$10$yj4W5Komra3J/xKlKcwGJu20ZbH9vdABe7KiX9nB1phnLs3QdEwPe', 'mahasiswa', NULL, NULL, '2026-08-02 12:52:17'),
(12, 'MHS012', 'Mahasiswa 12', 'mahasiswa12@student.kampus.com', '$2y$10$yj4W5Komra3J/xKlKcwGJu20ZbH9vdABe7KiX9nB1phnLs3QdEwPe', 'mahasiswa', NULL, NULL, '2026-08-02 12:52:17'),
(14, 'MHS014', 'Mahasiswa 14', 'mahasiswa14@student.kampus.com', '$2y$10$yj4W5Komra3J/xKlKcwGJu20ZbH9vdABe7KiX9nB1phnLs3QdEwPe', 'mahasiswa', NULL, NULL, '2026-08-02 12:52:17'),
(15, 'MHS015', 'Mahasiswa 15', 'mahasiswa15@student.kampus.com', '$2y$10$yj4W5Komra3J/xKlKcwGJu20ZbH9vdABe7KiX9nB1phnLs3QdEwPe', 'mahasiswa', NULL, NULL, '2026-08-02 12:52:17'),
(16, 'MHS016', 'Mahasiswa 16', 'mahasiswa16@student.kampus.com', '$2y$10$yj4W5Komra3J/xKlKcwGJu20ZbH9vdABe7KiX9nB1phnLs3QdEwPe', 'mahasiswa', NULL, NULL, '2026-08-02 12:52:17'),
(17, 'MHS017', 'Mahasiswa 17', 'mahasiswa17@student.kampus.com', '$2y$10$yj4W5Komra3J/xKlKcwGJu20ZbH9vdABe7KiX9nB1phnLs3QdEwPe', 'mahasiswa', NULL, NULL, '2026-08-02 12:52:17'),
(18, 'MHS018', 'Mahasiswa 18', 'mahasiswa18@student.kampus.com', '$2y$10$yj4W5Komra3J/xKlKcwGJu20ZbH9vdABe7KiX9nB1phnLs3QdEwPe', 'mahasiswa', NULL, NULL, '2026-08-02 12:52:17'),
(21, 'MHS021', 'Mahasiswa 21', 'mahasiswa21@student.kampus.com', '$2y$10$yj4W5Komra3J/xKlKcwGJu20ZbH9vdABe7KiX9nB1phnLs3QdEwPe', 'mahasiswa', NULL, NULL, '2026-08-02 12:52:17'),
(22, 'MHS022', 'Mahasiswa 22', 'mahasiswa22@student.kampus.com', '$2y$10$yj4W5Komra3J/xKlKcwGJu20ZbH9vdABe7KiX9nB1phnLs3QdEwPe', 'mahasiswa', NULL, NULL, '2026-08-02 12:52:17'),
(23, 'MHS023', 'Mahasiswa 23', 'mahasiswa23@student.kampus.com', '$2y$10$yj4W5Komra3J/xKlKcwGJu20ZbH9vdABe7KiX9nB1phnLs3QdEwPe', 'mahasiswa', NULL, NULL, '2026-08-02 12:52:17'),
(24, 'MHS024', 'Mahasiswa 24', 'mahasiswa24@student.kampus.com', '$2y$10$yj4W5Komra3J/xKlKcwGJu20ZbH9vdABe7KiX9nB1phnLs3QdEwPe', 'mahasiswa', NULL, NULL, '2026-08-02 12:52:17'),
(25, 'MHS025', 'Mahasiswa 25', 'mahasiswa25@student.kampus.com', '$2y$10$yj4W5Komra3J/xKlKcwGJu20ZbH9vdABe7KiX9nB1phnLs3QdEwPe', 'mahasiswa', NULL, NULL, '2026-08-02 12:52:17'),
(26, 'MHS026', 'Mahasiswa 26', 'mahasiswa26@student.kampus.com', '$2y$10$yj4W5Komra3J/xKlKcwGJu20ZbH9vdABe7KiX9nB1phnLs3QdEwPe', 'mahasiswa', NULL, NULL, '2026-08-02 12:52:17'),
(27, 'MHS027', 'Mahasiswa 27', 'mahasiswa27@student.kampus.com', '$2y$10$yj4W5Komra3J/xKlKcwGJu20ZbH9vdABe7KiX9nB1phnLs3QdEwPe', 'mahasiswa', NULL, NULL, '2026-08-02 12:52:17'),
(28, 'MHS028', 'Mahasiswa 28', 'mahasiswa28@student.kampus.com', '$2y$10$yj4W5Komra3J/xKlKcwGJu20ZbH9vdABe7KiX9nB1phnLs3QdEwPe', 'mahasiswa', NULL, NULL, '2026-08-02 12:52:17'),
(29, 'MHS029', 'Mahasiswa 29', 'mahasiswa29@student.kampus.com', '$2y$10$yj4W5Komra3J/xKlKcwGJu20ZbH9vdABe7KiX9nB1phnLs3QdEwPe', 'mahasiswa', NULL, NULL, '2026-08-02 12:52:17'),
(30, 'TEST001', 'Test User', 'test@test.com', '$2y$10$yj4W5Komra3J/xKlKcwGJu20ZbH9vdABe7KiX9nB1phnLs3QdEwPe', 'mahasiswa', NULL, NULL, '2026-08-02 12:55:33'),
(31, 'ADMIN001', 'Super Admin', 'admin@kampus.com', '$2y$10$yj4W5Komra3J/xKlKcwGJu20ZbH9vdABe7KiX9nB1phnLs3QdEwPe', 'admin', NULL, NULL, '2026-08-02 14:05:45'),
(32, 'MHS032', 'Mahasiswa 32', 'mahasiswa32@student.kampus.com', '$2y$10$tyB3Dqr3IRE3JHw.2BRQ.uwaIq6k9EuXdAqXOgE1g0Yh./827mH52', 'mahasiswa', NULL, NULL, '2026-08-05 14:46:23'),
(35, 'MHS035', 'Mahasiswa 35', 'mahasiswa35@student.kampus.com', '$2y$10$VC9Ansf5YrDa/RoeFuNtgOp8NYg/xS0/YeIU/ilqx5QaHtiFf.olq', 'mahasiswa', NULL, NULL, '2026-08-06 09:13:48');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `jadwal_id` (`jadwal_id`);

--
-- Indeks untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `matkul_id` (`matkul_id`),
  ADD KEY `dosen_id` (`dosen_id`);

--
-- Indeks untuk tabel `matkul`
--
ALTER TABLE `matkul`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_matkul` (`kode_matkul`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nip_nim` (`nip_nim`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `matkul`
--
ALTER TABLE `matkul`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `absensi`
--
ALTER TABLE `absensi`
  ADD CONSTRAINT `absensi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `absensi_ibfk_2` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  ADD CONSTRAINT `jadwal_ibfk_1` FOREIGN KEY (`matkul_id`) REFERENCES `matkul` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `jadwal_ibfk_2` FOREIGN KEY (`dosen_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
