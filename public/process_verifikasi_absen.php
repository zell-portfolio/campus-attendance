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
        $absensi_id = $_POST['absensi_id'] ?? null;
        $status_verifikasi = $_POST['status_verifikasi'] ?? null;
        $original_status = $_POST['original_status'] ?? 'Izin';
        $persentase = isset($_POST['persentase']) ? (int)$_POST['persentase'] : 100;
        
        // Validasi
        if (!$absensi_id || !$status_verifikasi) {
            throw new Exception("Data tidak lengkap");
        }
        
        // Batasi persentase 0-100
        $persentase = max(0, min(100, $persentase));
        
        $database = new Database();
        $conn = $database->getConnection();
        
        // Tentukan status akhir
        $final_status = $original_status;
        
        if ($status_verifikasi === 'Ditolak') {
            if ($persentase == 0) {
                $final_status = 'Tidak Hadir';
            }
            // Jika 1-100%, status tetap original
        } else {
            // Disetujui = 100%
            $persentase = 100;
        }
        
        // Update database
        $stmt = $conn->prepare("
            UPDATE absensi 
            SET status_verifikasi = ?, 
                persentase_final = ?, 
                status_sistem = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $status_verifikasi, 
            $persentase, 
            $final_status, 
            $absensi_id
        ]);
        
        // Redirect dengan sukses
        header("Location: /kampus-absensi/public/index.php?page=dosen_kelola_absen&success=1");
        exit();
        
    } catch (Exception $e) {
        // Redirect dengan error
        header("Location: /kampus-absensi/public/index.php?page=dosen_kelola_absen&error=" . urlencode($e->getMessage()));
        exit();
    }
}

// Jika bukan POST request
header("Location: /kampus-absensi/public/index.php?page=dosen_kelola_absen");
exit();
?>