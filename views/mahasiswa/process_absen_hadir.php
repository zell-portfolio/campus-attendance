<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once __DIR__ . "/../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $jadwal_id = $_POST['jadwal_id'] ?? '';
    
    if (empty($jadwal_id)) {
        header("Location: /kampus-absensi/public/index.php?page=mahasiswa_absen_hadir&error=1");
        exit();
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Ambil data jadwal
        $stmt = $conn->prepare("SELECT * FROM jadwal WHERE id = ?");
        $stmt->execute([$jadwal_id]);
        $jadwal = $stmt->fetch();
        
        if (!$jadwal) {
            header("Location: /kampus-absensi/public/index.php?page=mahasiswa_absen_hadir&error=1");
            exit();
        }
        
        $waktu_scan = date('H:i:s');
        $tanggal = date('Y-m-d');
        $jam_mulai = $jadwal['jam_mulai'];
        
        // Hitung selisih waktu (dalam detik)
        $waktu_scan_timestamp = strtotime($tanggal . ' ' . $waktu_scan);
        $jam_mulai_timestamp = strtotime($tanggal . ' ' . $jam_mulai);
        $selisih = $waktu_scan_timestamp - $jam_mulai_timestamp;
        
        // Toleransi 15 menit = 900 detik
        $toleransi = 900;
        
        if ($selisih <= $toleransi && $selisih >= 0) {
            // Hadir tepat waktu (atau dalam toleransi)
            $status = 'Hadir';
            $persentase = 100.00;
            $redirect = 'success=1';
        } elseif ($selisih < 0) {
            // Absen sebelum jam mulai (terlalu awal)
            $status = 'Hadir';
            $persentase = 100.00;
            $redirect = 'success=1';
        } else {
            // Terlambat (lebih dari 15 menit)
            $status = 'Terlambat_Murni';
            $persentase = 85.00;
            $redirect = 'late=1';
        }
        
        // Cek apakah sudah absen hari ini untuk jadwal ini
        $stmt = $conn->prepare("SELECT id FROM absensi WHERE user_id = ? AND jadwal_id = ? AND tanggal = ?");
        $stmt->execute([$user_id, $jadwal_id, $tanggal]);
        $already_absent = $stmt->fetch();
        
        if ($already_absent) {
            header("Location: /kampus-absensi/public/index.php?page=mahasiswa_absen_hadir&already=1");
            exit();
        }
        
        // Insert absensi
        $stmt = $conn->prepare("
            INSERT INTO absensi (user_id, jadwal_id, tanggal, waktu_scan, status_sistem, persentase_harian, status_verifikasi) 
            VALUES (?, ?, ?, ?, ?, ?, 'Disetujui')
        ");
        $stmt->execute([$user_id, $jadwal_id, $tanggal, $waktu_scan, $status, $persentase]);
        
        header("Location: /kampus-absensi/public/index.php?page=mahasiswa_absen_hadir&" . $redirect);
        exit();
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    header("Location: /kampus-absensi/public/index.php?page=login");
    exit();
}
?>