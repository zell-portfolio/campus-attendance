<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . "/../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $jadwal_id = $_POST['jadwal_id'] ?? '';
    $face_descriptor_json = $_POST['face_descriptor'] ?? '';
    $face_confidence = $_POST['face_confidence'] ?? '1.0';
    
    if (empty($jadwal_id)) {
        header("Location: /kampus-absensi/public/index.php?page=mahasiswa_absen_hadir&error=1");
        exit();
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // Ambil descriptor referensi dari database
        $stmt = $conn->prepare("SELECT face_descriptor FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user || empty($user['face_descriptor'])) {
            header("Location: /kampus-absensi/public/index.php?page=mahasiswa_absen_hadir&no_photo=1");
            exit();
        }
        
        // VERIFIKASI SERVER-SIDE
        if (!empty($face_descriptor_json)) {
            $camera_descriptor = json_decode($face_descriptor_json, true);
            $reference_descriptor = json_decode($user['face_descriptor'], true);
            
            if ($camera_descriptor && $reference_descriptor && count($camera_descriptor) === 128 && count($reference_descriptor) === 128) {
                // Hitung Euclidean distance
                $sum = 0;
                for ($i = 0; $i < 128; $i++) {
                    $sum += pow($camera_descriptor[$i] - $reference_descriptor[$i], 2);
                }
                $distance = sqrt($sum);
                
                // Threshold: 0.5 (lebih ketat)
                if ($distance > 0.5) {
                    header("Location: /kampus-absensi/public/index.php?page=mahasiswa_absen_hadir&face_mismatch=1&distance=" . round($distance, 3));
                    exit();
                }
            }
        }
        
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
        
        $selisih = strtotime($tanggal . ' ' . $waktu_scan) - strtotime($tanggal . ' ' . $jam_mulai);
        $toleransi = 900;
        
        if ($selisih <= $toleransi && $selisih >= 0) {
            $status = 'Hadir';
            $persentase = 100.00;
            $redirect = 'success=1';
        } elseif ($selisih < 0) {
            $status = 'Hadir';
            $persentase = 100.00;
            $redirect = 'success=1';
        } else {
            $status = 'Terlambat_Murni';
            $persentase = 85.00;
            $redirect = 'late=1';
        }
        
        // Cek duplikasi
        $stmt = $conn->prepare("SELECT id FROM absensi WHERE user_id = ? AND jadwal_id = ? AND tanggal = ?");
        $stmt->execute([$user_id, $jadwal_id, $tanggal]);
        if ($stmt->fetch()) {
            header("Location: /kampus-absensi/public/index.php?page=mahasiswa_absen_hadir&already=1");
            exit();
        }
        
        $stmt = $conn->prepare("INSERT INTO absensi (user_id, jadwal_id, tanggal, waktu_scan, status_sistem, persentase_harian, status_verifikasi) VALUES (?, ?, ?, ?, ?, ?, 'Disetujui')");
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