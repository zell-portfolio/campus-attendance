<?php
session_start();
if ($_SESSION['role'] !== 'dosen') { header("Location: /kampus-absensi/public/index.php?page=login"); exit(); }
require_once __DIR__ . "/../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $absen_id = $_POST['absen_id'];
    $status = $_POST['status_verifikasi'];
    $persentase_final = isset($_POST['persentase_final']) ? $_POST['persentase_final'] : null;
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $stmt = $conn->prepare("UPDATE absensi SET status_verifikasi = ?, persentase_final = ? WHERE id = ?");
        $stmt->execute([$status, $persentase_final, $absen_id]);
        
        header("Location: /kampus-absensi/public/index.php?page=dosen_kelola_absen&success=1");
        exit();
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>