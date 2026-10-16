<?php
date_default_timezone_set('Asia/Makassar');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dosen') {
    header("Location: /kampus-absensi/public/index.php?page=login");
    exit();
}

require_once __DIR__ . "/../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dosen_id = $_SESSION['user_id'];
        $jadwal_id = $_POST['jadwal_id'] ?? null;
        $status = $_POST['status'] ?? 'Hadir';
        
        if (!$jadwal_id) {
            throw new Exception("Jadwal tidak valid");
        }
        
        $database = new Database();
        $conn = $database->getConnection();
        
        // Cek apakah sudah absen hari ini untuk jadwal ini
        $stmt = $conn->prepare("SELECT id FROM absensi WHERE user_id = ? AND jadwal_id = ? AND tanggal = ?");
        $stmt->execute([$dosen_id, $jadwal_id, date('Y-m-d')]);
        
        if ($stmt->fetch()) {
            header("Location: /kampus-absensi/public/index.php?page=dosen_absen&already=1");
            exit();
        }
        
        // Insert absensi dosen
        $stmt = $conn->prepare("
            INSERT INTO absensi (user_id, jadwal_id, tanggal, waktu_scan, status_sistem, persentase_final, status_verifikasi)
            VALUES (?, ?, ?, ?, ?, 100, 'Disetujui')
        ");
        $stmt->execute([$dosen_id, $jadwal_id, date('Y-m-d'), date('H:i:s'), $status]);
        
        header("Location: /kampus-absensi/public/index.php?page=dosen_absen&success=1");
        exit();
        
    } catch (Exception $e) {
        header("Location: /kampus-absensi/public/index.php?page=dosen_absen&error=" . urlencode($e->getMessage()));
        exit();
    }
}

header("Location: /kampus-absensi/public/index.php?page=dosen_dashboard");
exit();
?>