<?php
date_default_timezone_set('Asia/Makassar');
require_once __DIR__ . "/../config/database.php";

// Script ini bisa dijalankan via cron job atau manual
// Untuk menandai mahasiswa yang tidak absen saat kelas ditutup sebagai "Alpa"

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Ambil semua jadwal yang sudah selesai (jam_selesai < waktu sekarang)
    $stmt = $conn->query("
        SELECT j.id as jadwal_id, j.hari, j.jam_selesai, j.dosen_id, j.matkul_id,
               u.id as mahasiswa_id
        FROM jadwal j
        CROSS JOIN users u
        WHERE u.role = 'mahasiswa'
        AND j.hari = DAYNAME(CURDATE())
        AND j.jam_selesai < CURTIME()
        AND j.is_open = 0
    ");
    $jadwal_selesai = $stmt->fetchAll();
    
    foreach ($jadwal_selesai as $js) {
        // Cek apakah mahasiswa sudah absen untuk jadwal ini hari ini
        $stmt = $conn->prepare("
            SELECT id FROM absensi 
            WHERE user_id = ? AND jadwal_id = ? AND tanggal = ?
        ");
        $stmt->execute([$js['mahasiswa_id'], $js['jadwal_id'], date('Y-m-d')]);
        $sudah_absen = $stmt->fetch();
        
        // Jika belum absen, tandai sebagai Alpa
        if (!$sudah_absen) {
            $stmt = $conn->prepare("
                INSERT INTO absensi (user_id, jadwal_id, tanggal, waktu_scan, status_sistem, persentase_final, status_verifikasi)
                VALUES (?, ?, ?, NOW(), 'Alpa', 0, 'Disetujui')
            ");
            $stmt->execute([$js['mahasiswa_id'], $js['jadwal_id'], date('Y-m-d')]);
        }
    }
    
    echo "✅ Berhasil menandai mahasiswa yang tidak hadir sebagai Alpa";
    
} catch (Exception $e) {
    echo " Error: " . $e->getMessage();
}
?>