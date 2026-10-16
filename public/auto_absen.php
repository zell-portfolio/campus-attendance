<?php
date_default_timezone_set('Asia/Makassar');
// ...
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/../config/database.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Belum login']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$face_distance = $input['face_distance'] ?? '0';

$user_id = $_SESSION['user_id'];

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Ambil jadwal yang BUKA saat ini
    $hari_indo = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
    $hari_ini = $hari_indo[date('l')];
    $waktu_sekarang = date('H:i:s');
    $tanggal = date('Y-m-d');
    
    // Query: cari jadwal yang BUKA (force buka ATAU dalam rentang jam)
    $stmt = $conn->prepare("
        SELECT j.*, m.nama_matkul 
        FROM jadwal j 
        LEFT JOIN matkul m ON j.matkul_id = m.id 
        WHERE j.hari = ? AND (
            (j.manual_override = 1 AND j.is_open = 1) 
            OR 
            (j.manual_override = 0 AND j.is_open = 1 AND ? BETWEEN j.jam_mulai AND j.jam_selesai)
        )
        ORDER BY j.jam_mulai ASC 
        LIMIT 1
    ");
    $stmt->execute([$hari_ini, $waktu_sekarang]);
    $jadwal = $stmt->fetch();
    
    if (!$jadwal) {
        echo json_encode(['success' => false, 'message' => 'Tidak ada jadwal aktif saat ini']);
        exit();
    }
    
    $jadwal_id = $jadwal['id'];
    $jam_mulai = $jadwal['jam_mulai'];
    
    // Cek apakah sudah absen hari ini untuk jadwal ini
    $stmt = $conn->prepare("SELECT id FROM absensi WHERE user_id = ? AND jadwal_id = ? AND tanggal = ?");
    $stmt->execute([$user_id, $jadwal_id, $tanggal]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Anda sudah absen untuk jadwal ini hari ini']);
        exit();
    }
    
    // Hitung status kehadiran
    $selisih = strtotime($tanggal . ' ' . $waktu_sekarang) - strtotime($tanggal . ' ' . $jam_mulai);
    $toleransi = 900; // 15 menit
    
    if ($selisih <= $toleransi && $selisih >= 0) {
        $status = 'Hadir';
        $persentase = 100.00;
    } elseif ($selisih < 0) {
        $status = 'Hadir';
        $persentase = 100.00;
    } else {
        $status = 'Terlambat_Murni';
        $persentase = 85.00;
    }
    
    // Insert absensi
    $stmt = $conn->prepare("
        INSERT INTO absensi (user_id, jadwal_id, tanggal, waktu_scan, status_sistem, persentase_harian, status_verifikasi, face_distance) 
        VALUES (?, ?, ?, ?, ?, ?, 'Disetujui', ?)
    ");
    $stmt->execute([$user_id, $jadwal_id, $tanggal, $waktu_sekarang, $status, $persentase, $face_distance]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Absensi berhasil',
        'status' => $status,
        'matkul' => $jadwal['nama_matkul']
    ]);
    exit();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit();
}
?>