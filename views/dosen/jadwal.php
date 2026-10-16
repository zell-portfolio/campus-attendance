<?php
date_default_timezone_set('Asia/Makassar');


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dosen') {
    header("Location: /kampus-absensi/public/index.php?page=login");
    exit();
}

require_once __DIR__ . "/../../config/database.php";
$database = new Database();
$conn = $database->getConnection();

$dosen_id = $_SESSION['user_id'];
$hari_list = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$jadwal_per_hari = [];
$waktu_sekarang = date('H:i:s');
$tanggal_sekarang = date('Y-m-d');

foreach ($hari_list as $hari) {
    $stmt = $conn->prepare("SELECT j.*, m.nama_matkul, m.sks FROM jadwal j LEFT JOIN matkul m ON j.matkul_id = m.id WHERE j.dosen_id = ? AND j.hari = ? ORDER BY j.jam_mulai ASC");
    $stmt->execute([$dosen_id, $hari]);
    $jadwal_per_hari[$hari] = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jadwal Mengajar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%); min-height: 100vh; }
        .page-header { background: linear-gradient(135deg, #064e3b 0%, #059669 100%); padding: 40px; color: white; margin-bottom: 30px; }
        .page-header h1 { font-family: 'Playfair Display', serif; margin: 0; font-size: 2.2rem; }
        .page-header p { opacity: 0.9; margin: 10px 0 0; }
        .back-btn { background: rgba(255,255,255,0.2); color: white; border: 2px solid rgba(255,255,255,0.3); padding: 10px 20px; border-radius: 10px; text-decoration: none; transition: all 0.3s; display: inline-block; }
        .back-btn:hover { background: rgba(255,255,255,0.3); color: white; }
        .hari-card { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); margin-bottom: 25px; overflow: hidden; }
        .hari-header { background: linear-gradient(135deg, #059669, #064e3b); color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; }
        .hari-header h4 { margin: 0; font-family: 'Playfair Display', serif; }
        .hari-body { padding: 20px 30px; }
        .jadwal-item { display: flex; align-items: center; padding: 20px; border-radius: 15px; margin-bottom: 15px; background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border-left: 5px solid #059669; transition: all 0.3s; }
        .jadwal-item:hover { transform: translateX(10px); box-shadow: 0 5px 15px rgba(5, 150, 105, 0.2); }
        .jadwal-time { background: linear-gradient(135deg, #059669, #064e3b); color: white; padding: 15px 20px; border-radius: 12px; font-weight: 600; margin-right: 25px; min-width: 130px; text-align: center; }
        .jadwal-info { flex-grow: 1; }
        .jadwal-info h5 { color: #064e3b; font-weight: 600; margin-bottom: 5px; }
        .jadwal-info p { color: #6b7280; margin: 0; font-size: 0.9rem; }
        
        /* Tombol Absen Dosen */
        .btn-absen-dosen {
            border-radius: 50px; padding: 8px 20px; font-weight: 600; font-size: 0.85rem;
            text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
            border: none; transition: all 0.3s; min-width: 110px; justify-content: center;
        }
        .btn-absen-buka { background: linear-gradient(135deg, #059669, #064e3b); color: white; cursor: pointer; }
        .btn-absen-buka:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(5, 150, 105, 0.4); color: white; }
        .btn-absen-tutup { background: #e5e7eb; color: #9ca3af; cursor: not-allowed; }
        .btn-absen-sudah { background: #d1fae5; color: #065f46; cursor: default; }
        
        /* Highlight Hari Ini */
        .hari-card.hari-ini { box-shadow: 0 10px 40px rgba(5, 150, 105, 0.3); border: 2px solid #059669; }
        .hari-header.hari-ini { background: linear-gradient(135deg, #064e3b, #047857); }
        .badge-hari-ini { background: white; color: #059669; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-calendar-alt me-3"></i>Jadwal Mengajar</h1>
                    <p>Jadwal perkuliahan Anda selama satu minggu</p>
                </div>
                <a href="/kampus-absensi/public/index.php?page=dosen_dashboard" class="back-btn">
                    <i class="fas fa-arrow-left me-2"></i>Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php 
        $hari_indo = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
        $hari_ini = $hari_indo[date('l')];
        
        foreach($hari_list as $hari): 
            $is_hari_ini = ($hari === $hari_ini);
        ?>
            <?php if(count($jadwal_per_hari[$hari]) > 0): ?>
            <div class="hari-card <?= $is_hari_ini ? 'hari-ini' : '' ?>">
                <div class="hari-header <?= $is_hari_ini ? 'hari-ini' : '' ?>">
                    <h4>
                        <i class="fas fa-calendar-day me-2"></i><?= $hari ?>
                        <?= $is_hari_ini ? '<span class="badge-hari-ini ms-2">HARI INI</span>' : '' ?>
                    </h4>
                    <span class="badge bg-light text-dark"><?= count($jadwal_per_hari[$hari]) ?> Kelas</span>
                </div>
                <div class="hari-body">
                    <?php foreach($jadwal_per_hari[$hari] as $j): 
                        
                        // --- LOGIC TOMBOL: BUKA, TUTUP, atau SUDAH ABSEN ---
                        $status_tombol = 'tutup';
                        
                        // 1. Cek apakah sudah absen untuk jadwal ini hari ini
                        $stmt_cek = $conn->prepare("SELECT id FROM absensi WHERE user_id = ? AND jadwal_id = ? AND tanggal = ?");
                        $stmt_cek->execute([$dosen_id, $j['id'], $tanggal_sekarang]);
                        $sudah_absen = $stmt_cek->fetch();
                        
                        if ($sudah_absen) {
                            $status_tombol = 'sudah'; // Sudah absen
                        } elseif ($j['manual_override'] == 1 && $j['is_open'] == 1) {
                            $status_tombol = 'buka'; // Admin force buka
                        } elseif ($waktu_sekarang >= $j['jam_mulai'] && $waktu_sekarang <= $j['jam_selesai']) {
                            $status_tombol = 'buka'; // Dalam rentang waktu
                        }
                    ?>
                    <div class="jadwal-item">
                        <div class="jadwal-time">
                            <div style="font-size: 0.75rem; opacity: 0.8;">MULAI</div>
                            <div style="font-size: 1.1rem;"><?= date('H:i', strtotime($j['jam_mulai'])) ?></div>
                            <div style="font-size: 0.75rem; opacity: 0.8; margin-top: 5px;">SELESAI</div>
                            <div style="font-size: 1.1rem;"><?= date('H:i', strtotime($j['jam_selesai'])) ?></div>
                        </div>
                        <div class="jadwal-info">
                            <h5><?= htmlspecialchars($j['nama_matkul']) ?></h5>
                            <p><i class="fas fa-book me-1"></i> <?= $j['sks'] ?> SKS <span class="mx-2">•</span> <i class="fas fa-clock me-1"></i> <?= round((strtotime($j['jam_selesai']) - strtotime($j['jam_mulai'])) / 3600, 1) ?> Jam</p>
                        </div>
                        
                        <!-- TOMBOL DINAMIS -->
                        <?php if ($status_tombol === 'sudah'): ?>
                            <button class="btn-absen-dosen btn-absen-sudah" disabled title="Anda sudah absen untuk jadwal ini">
                                <i class="fas fa-check-circle"></i> Sudah Absen
                            </button>
                        <?php elseif ($status_tombol === 'buka'): ?>
                            <a href="/kampus-absensi/public/index.php?page=dosen_absen&jadwal_id=<?= $j['id'] ?>" class="btn-absen-dosen btn-absen-buka">
                                <i class="fas fa-check"></i> Absen
                            </a>
                        <?php else: ?>
                            <button class="btn-absen-dosen btn-absen-tutup" disabled title="Absensi ditutup">
                                <i class="fas fa-lock"></i> Tutup
                            </button>
                        <?php endif; ?>
                        
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>