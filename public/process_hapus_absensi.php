<?php
date_default_timezone_set('Asia/Makassar');
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cek apakah user adalah admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /kampus-absensi/public/index.php?page=login");
    exit();
}

require_once __DIR__ . "/../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    
    if (empty($id)) {
        header("Location: /kampus-absensi/public/index.php?page=admin_rekap_absensi&error=id_kosong");
        exit();
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Cek apakah data absensi ada
        $stmt = $conn->prepare("SELECT id, user_id, jadwal_id, tanggal FROM absensi WHERE id = ?");
        $stmt->execute([$id]);
        $absensi = $stmt->fetch();
        
        if (!$absensi) {
            header("Location: /kampus-absensi/public/index.php?page=admin_rekap_absensi&error=data_tidak_ditemukan");
            exit();
        }
        
        // Hapus data absensi
        $stmt = $conn->prepare("DELETE FROM absensi WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            header("Location: /kampus-absensi/public/index.php?page=admin_rekap_absensi&success=1");
        } else {
            header("Location: /kampus-absensi/public/index.php?page=admin_rekap_absensi&error=hapus_gagal");
        }
        exit();
        
    } catch (Exception $e) {
        header("Location: /kampus-absensi/public/index.php?page=admin_rekap_absensi&error=" . urlencode($e->getMessage()));
        exit();
    }
}

header("Location: /kampus-absensi/public/index.php?page=admin_rekap_absensi");
exit();
?>