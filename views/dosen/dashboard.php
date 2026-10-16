<?php
date_default_timezone_set('Asia/Makassar');
// PENTING: Harus ada agar $_SESSION terbaca

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dosen') {
    header("Location: /kampus-absensi/public/index.php?page=login");
    exit();
}

require_once __DIR__ . "/../../config/database.php";
$database = new Database();
$conn = $database->getConnection();

$dosen_id = $_SESSION['user_id'];
$waktu_sekarang = date('H:i:s');
$tanggal_sekarang = date('Y-m-d');

// Statistik - dengan kategori Tidak Hadir
$stmt = $conn->prepare("
    SELECT 
        COUNT(DISTINCT a.user_id) as total_mhs,
        COUNT(*) as total_absen,
        SUM(CASE WHEN a.status_sistem = 'Hadir' THEN 1 ELSE 0 END) as hadir,
        SUM(CASE WHEN a.status_sistem IN ('Izin', 'Sakit') THEN 1 ELSE 0 END) as izin_sakit,
        SUM(CASE WHEN a.status_sistem LIKE '%Terlambat%' THEN 1 ELSE 0 END) as terlambat,
        SUM(CASE WHEN a.status_sistem = 'Tidak Hadir' THEN 1 ELSE 0 END) as tidak_hadir
    FROM absensi a
    LEFT JOIN jadwal j ON a.jadwal_id = j.id
    LEFT JOIN users u ON a.user_id = u.id
    WHERE j.dosen_id = ? AND u.role = 'mahasiswa'
");
$stmt->execute([$dosen_id]);
$stats = $stmt->fetch();

$stmt = $conn->prepare("SELECT COUNT(*) FROM jadwal WHERE dosen_id = ?");
$stmt->execute([$dosen_id]);
$total_matkul = $stmt->fetchColumn();

$hari_indo = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
$hari_ini = $hari_indo[date('l')];

$stmt = $conn->prepare("SELECT j.*, m.nama_matkul FROM jadwal j LEFT JOIN matkul m ON j.matkul_id = m.id WHERE j.dosen_id = ? AND j.hari = ? ORDER BY j.jam_mulai ASC");
$stmt->execute([$dosen_id, $hari_ini]);
$jadwal_hari_ini = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Dosen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #d1fae5 100%); min-height: 100vh; }
        
        .top-nav { background: linear-gradient(135deg, #064e3b 0%, #059669 100%); padding: 20px 40px; box-shadow: 0 10px 30px rgba(5, 150, 105, 0.3); }
        .brand-title { font-family: 'Playfair Display', serif; color: white; font-size: 1.8rem; font-weight: 700; margin: 0; }
        .brand-subtitle { color: #d4af37; font-size: 0.85rem; letter-spacing: 2px; text-transform: uppercase; }
        .user-profile { display: flex; align-items: center; gap: 15px; color: white; }
        .user-avatar { width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #d4af37, #f59e0b); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; font-weight: 600; color: white; }
        .btn-logout { background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.4); color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 500; transition: all 0.3s; display: inline-block; }
        .btn-logout:hover { background: rgba(255,255,255,0.3); color: white; }
        
        .welcome-banner { background: linear-gradient(135deg, #059669 0%, #064e3b 100%); border-radius: 20px; padding: 40px; color: white; margin-bottom: 30px; box-shadow: 0 20px 40px rgba(5, 150, 105, 0.3); position: relative; overflow: hidden; }
        .welcome-banner h1 { font-family: 'Playfair Display', serif; font-size: 2.5rem; margin-bottom: 10px; }
        
        .stat-card { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); transition: all 0.3s; border-top: 5px solid; }
        .stat-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.15); }
        .stat-card.green { border-top-color: #10b981; }
        .stat-card.gold { border-top-color: #d4af37; }
        .stat-card.blue { border-top-color: #3b82f6; }
        .stat-card.red { border-top-color: #ef4444; }
        .stat-card.orange { border-top-color: #f59e0b; }
        .stat-icon { width: 60px; height: 60px; border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 15px; color: white; }
        .stat-icon.green { background: linear-gradient(135deg, #10b981, #059669); }
        .stat-icon.gold { background: linear-gradient(135deg, #f59e0b, #d4af37); }
        .stat-icon.blue { background: linear-gradient(135deg, #3b82f6, #1e40af); }
        .stat-icon.red { background: linear-gradient(135deg, #ef4444, #991b1b); }
        .stat-icon.orange { background: linear-gradient(135deg, #f97316, #ea580c); }
        .stat-number { font-size: 2rem; font-weight: 700; color: #064e3b; }
        .stat-label { color: #6b7280; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; }
        
        .menu-card { background: white; border-radius: 20px; padding: 30px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.08); transition: all 0.3s; text-decoration: none; color: inherit; display: block; border: 2px solid transparent; }
        .menu-card:hover { transform: translateY(-10px); border-color: #059669; box-shadow: 0 20px 40px rgba(5, 150, 105, 0.2); color: inherit; }
        .menu-icon-large { width: 80px; height: 80px; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 20px; color: white; }
        .menu-card h5 { font-weight: 600; color: #064e3b; margin-bottom: 10px; }
        .menu-card p { color: #6b7280; font-size: 0.9rem; margin: 0; }
        
        .jadwal-section { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        .jadwal-item { display: flex; align-items: center; padding: 15px; border-radius: 15px; margin-bottom: 10px; background: linear-gradient(135deg, #f0fdf4, #ecfdf5); border-left: 4px solid #059669; transition: all 0.3s; }
        .jadwal-item:hover { transform: translateX(10px); }
        .jadwal-time { background: linear-gradient(135deg, #059669, #064e3b); color: white; padding: 10px 15px; border-radius: 10px; font-weight: 600; margin-right: 20px; min-width: 100px; text-align: center; }

        /* Tombol Absen Dosen Dashboard */
        .btn-absen-dash-dosen {
            border-radius: 50px; padding: 8px 20px; font-weight: 600; font-size: 0.85rem;
            text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
            border: none; transition: all 0.3s; min-width: 110px; justify-content: center;
        }
        .btn-absen-buka { background: linear-gradient(135deg, #059669, #064e3b); color: white; cursor: pointer; }
        .btn-absen-buka:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(5, 150, 105, 0.4); color: white; }
        .btn-absen-tutup { background: #e5e7eb; color: #9ca3af; cursor: not-allowed; }
        .btn-absen-sudah { background: #d1fae5; color: #065f46; cursor: default; } /* TAMBAHAN BARU */
    </style>
</head>
<body>
    <div class="top-nav">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="brand-title"><i class="fas fa-graduation-cap me-2"></i>Portal Dosen</h1>
                    <div class="brand-subtitle">Sistem Absensi Akademik</div>
                </div>
                <div class="user-profile">
                    <div class="text-end">
                        <div style="font-weight: 600;"><?= htmlspecialchars($_SESSION['nama']) ?></div>
                        <div style="font-size: 0.85rem; opacity: 0.8;">Dosen Pengajar</div>
                    </div>
                    <div class="user-avatar"><?= substr($_SESSION['nama'], 0, 1) ?></div>
                    <a href="/kampus-absensi/public/logout.php" class="btn-logout">
                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <div class="welcome-banner">
            <h1>Selamat Datang, <?= htmlspecialchars(explode(' ', $_SESSION['nama'])[0]) ?>! 👋</h1>
            <p>Semoga hari ini penuh dengan semangat mengajar.</p>
            <div class="mt-3">
                <span class="badge bg-light text-dark me-2"><i class="fas fa-calendar me-1"></i> <?= date('l, d F Y') ?></span>
                <span class="badge" style="background: #d4af37; color: white;"><i class="fas fa-clock me-1"></i> <?= date('H:i') ?> WITA</span>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card gold">
                    <div class="stat-icon gold"><i class="fas fa-user-graduate"></i></div>
                    <div class="stat-number"><?= $stats['total_mhs'] ?></div>
                    <div class="stat-label">Total Mahasiswa</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card blue">
                    <div class="stat-icon blue"><i class="fas fa-book-open"></i></div>
                    <div class="stat-number"><?= $total_matkul ?></div>
                    <div class="stat-label">Mata Kuliah</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card red">
                    <div class="stat-icon red"><i class="fas fa-clipboard-check"></i></div>
                    <div class="stat-number"><?= $stats['total_absen'] ?></div>
                    <div class="stat-label">Total Absensi</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card orange">
                    <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
                    <div class="stat-number"><?= $stats['tidak_hadir'] ?></div>
                    <div class="stat-label">Tidak Hadir</div>
                </div>
            </div>
        </div>

        <h3 class="mb-4" style="color: #064e3b; font-family: 'Playfair Display', serif;">Menu Utama</h3>
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <a href="/kampus-absensi/public/index.php?page=dosen_absen" class="menu-card">
                    <div class="menu-icon-large" style="background: linear-gradient(135deg, #10b981, #059669);"><i class="fas fa-check-circle"></i></div>
                    <h5>Absen Dosen</h5>
                    <p>Catat kehadiran Anda</p>
                </a>
            </div>
            <div class="col-md-3">
                <a href="/kampus-absensi/public/index.php?page=dosen_kelola_absen" class="menu-card">
                    <div class="menu-icon-large" style="background: linear-gradient(135deg, #f59e0b, #d4af37);"><i class="fas fa-tasks"></i></div>
                    <h5>Verifikasi Absen</h5>
                    <p><?= $stats['tidak_hadir'] ?> tidak hadir</p>
                </a>
            </div>
            <div class="col-md-3">
                <a href="/kampus-absensi/public/index.php?page=dosen_riwayat" class="menu-card">
                    <div class="menu-icon-large" style="background: linear-gradient(135deg, #3b82f6, #1e40af);"><i class="fas fa-history"></i></div>
                    <h5>Riwayat</h5>
                    <p>Lihat riwayat mengajar</p>
                </a>
            </div>
            <div class="col-md-3">
                <a href="/kampus-absensi/public/index.php?page=dosen_jadwal" class="menu-card">
                    <div class="menu-icon-large" style="background: linear-gradient(135deg, #8b5cf6, #6d28d9);"><i class="fas fa-calendar-alt"></i></div>
                    <h5>Jadwal</h5>
                    <p>Lihat jadwal mengajar</p>
                </a>
            </div>
        </div>

        <div class="jadwal-section">
            <h4 class="mb-4" style="color: #064e3b; font-family: 'Playfair Display', serif;">
                <i class="fas fa-calendar-day me-2" style="color: #d4af37;"></i>Jadwal Hari Ini (<?= $hari_ini ?>)
            </h4>
            <?php if(count($jadwal_hari_ini) > 0): ?>
                <?php foreach($jadwal_hari_ini as $j): 
                    // --- LOGIC TOMBOL DASHBOARD DOSEN ---
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
                        <div><?= date('H:i', strtotime($j['jam_mulai'])) ?></div>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="mb-1" style="color: #064e3b; font-weight: 600;"><?= htmlspecialchars($j['nama_matkul']) ?></h5>
                        <div class="text-muted small">
                            <i class="fas fa-clock me-1"></i> <?= date('H:i', strtotime($j['jam_mulai'])) ?> - <?= date('H:i', strtotime($j['jam_selesai'])) ?>
                        </div>
                    </div>
                    
                    <!-- TOMBOL DINAMIS -->
                    <?php if ($status_tombol === 'sudah'): ?>
                        <button class="btn-absen-dash-dosen btn-absen-sudah" disabled title="Anda sudah absen">
                            <i class="fas fa-check-circle"></i> Sudah Absen
                        </button>
                    <?php elseif ($status_tombol === 'buka'): ?>
                        <a href="/kampus-absensi/public/index.php?page=dosen_absen&jadwal_id=<?= $j['id'] ?>" class="btn-absen-dash-dosen btn-absen-buka">
                            <i class="fas fa-check"></i> Absen
                        </a>
                    <?php else: ?>
                        <button class="btn-absen-dash-dosen btn-absen-tutup" disabled title="Absensi ditutup">
                            <i class="fas fa-lock"></i> Tutup
                        </button>
                    <?php endif; ?>
                    
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-coffee fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Tidak ada jadwal mengajar hari ini.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>