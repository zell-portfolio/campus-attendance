<?php
date_default_timezone_set('Asia/Makassar');
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $status = $_POST['status'] ?? '';
    $jadwal_id = $_POST['jadwal_id'] ?? '';
    $alasan = $_POST['alasan'] ?? '';
    
    // Validasi input
    if (empty($status) || empty($jadwal_id) || empty($alasan)) {
        header("Location: /kampus-absensi/public/index.php?page=mahasiswa_absen_izin_sakit&error=missing_data");
        exit();
    }
    
    // Handle file upload
    $bukti_path = null;
    if (isset($_FILES['bukti']) && $_FILES['bukti']['error'] === 0) {
        $allowedTypes = [];
        $maxSize = 0;
        $uploadDir = __DIR__ . "/uploads/";
        
        if ($status === 'Sakit') {
            $allowedTypes = ['application/pdf'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            $subdir = 'pdfs/';
        } else {
            $allowedTypes = ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo'];
            $maxSize = 50 * 1024 * 1024; // 50MB
            $subdir = 'videos/';
        }
        
        // Cek ukuran file
        if ($_FILES['bukti']['size'] > $maxSize) {
            header("Location: /kampus-absensi/public/index.php?page=mahasiswa_absen_izin_sakit&error=upload_failed");
            exit();
        }
        
        // Cek tipe file
        $fileType = mime_content_type($_FILES['bukti']['tmp_name']);
        if (!in_array($fileType, $allowedTypes)) {
            header("Location: /kampus-absensi/public/index.php?page=mahasiswa_absen_izin_sakit&error=invalid_type");
            exit();
        }
        
        // Buat direktori jika belum ada
        $targetDir = $uploadDir . $subdir;
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        // Generate nama file unik
        $fileName = uniqid() . '_' . time() . '_' . basename($_FILES['bukti']['name']);
        $targetPath = $targetDir . $fileName;
        
        if (move_uploaded_file($_FILES['bukti']['tmp_name'], $targetPath)) {
            $bukti_path = 'uploads/' . $subdir . $fileName;
        } else {
            header("Location: /kampus-absensi/public/index.php?page=mahasiswa_absen_izin_sakit&error=upload_failed");
            exit();
        }
    }
    
    // Tentukan status sistem
    $status_sistem = 'Terlambat_Beralasan';
    if ($status === 'Izin') {
        $status_sistem = 'Izin';
    } elseif ($status === 'Sakit') {
        $status_sistem = 'Sakit';
    }
    
    $persentase = 85.00;
    $tanggal = date('Y-m-d');
    $waktu_scan = date('H:i:s');
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Cek apakah sudah absen hari ini
        $stmt = $conn->prepare("SELECT id FROM absensi WHERE user_id = ? AND jadwal_id = ? AND tanggal = ?");
        $stmt->execute([$user_id, $jadwal_id, $tanggal]);
        if ($stmt->fetch()) {
            header("Location: /kampus-absensi/public/index.php?page=mahasiswa_absen_izin_sakit&already=1");
            exit();
        }
        
        // Insert absensi
        $stmt = $conn->prepare("
            INSERT INTO absensi (user_id, jadwal_id, tanggal, waktu_scan, status_sistem, persentase_harian, alasan, bukti_path, status_verifikasi) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
        ");
        $stmt->execute([$user_id, $jadwal_id, $tanggal, $waktu_scan, $status_sistem, $persentase, $alasan, $bukti_path]);
        
        header("Location: /kampus-absensi/public/index.php?page=mahasiswa_absen_izin_sakit&success=1");
        exit();
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        exit();
    }
} else {
    header("Location: /kampus-absensi/public/index.php?page=login");
    exit();
}
?>