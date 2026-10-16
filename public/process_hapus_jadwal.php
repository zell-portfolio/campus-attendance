<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SESSION['role'] !== 'admin') { 
    header("Location: /kampus-absensi/public/index.php?page=login"); 
    exit(); 
}

require_once __DIR__ . "/../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    
    if (empty($id)) {
        header("Location: /kampus-absensi/public/index.php?page=admin_kelola_jadwal&error=id_kosong");
        exit();
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Cek apakah ada absensi yang terkait
        $stmt = $conn->prepare("SELECT COUNT(*) FROM absensi WHERE jadwal_id = ?");
        $stmt->execute([$id]);
        $absensi_count = $stmt->fetchColumn();
        
        if ($absensi_count > 0) {
            header("Location: /kampus-absensi/public/index.php?page=admin_kelola_jadwal&error=ada_absensi&count=" . $absensi_count);
            exit();
        }
        
        // Hapus jadwal
        $stmt = $conn->prepare("DELETE FROM jadwal WHERE id = ?");
        $stmt->execute([$id]);
        
        header("Location: /kampus-absensi/public/index.php?page=admin_kelola_jadwal&success=1");
        exit();
        
    } catch (Exception $e) {
        header("Location: /kampus-absensi/public/index.php?page=admin_kelola_jadwal&error=" . urlencode($e->getMessage()));
        exit();
    }
}

header("Location: /kampus-absensi/public/index.php?page=admin_kelola_jadwal");
exit();
?>