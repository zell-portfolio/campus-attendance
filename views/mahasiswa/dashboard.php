<?php
date_default_timezone_set('Asia/Makassar');


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: /kampus-absensi/public/index.php?page=login");
    exit();
}

require_once __DIR__ . "/../../config/database.php";
$database = new Database();
$conn = $database->getConnection();

$user_id = $_SESSION['user_id'];
$user_nama = $_SESSION['nama'];
$user_nim = $_SESSION['nip_nim'];

// Statistik dengan kategori Tidak Hadir
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status_sistem = 'Hadir' THEN 1 ELSE 0 END) as hadir,
        SUM(CASE WHEN status_sistem IN ('Izin', 'Sakit') THEN 1 ELSE 0 END) as izin_sakit,
        SUM(CASE WHEN status_sistem LIKE '%Terlambat%' THEN 1 ELSE 0 END) as terlambat,
        SUM(CASE WHEN status_sistem = 'Tidak Hadir' THEN 1 ELSE 0 END) as tidak_hadir
    FROM absensi 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

$persentase = 0;
if ($stats['total'] > 0) {
    $persentase = round(($stats['hadir'] / $stats['total']) * 100, 1);
}

// Jadwal hari ini
$hari_indo = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
$hari_ini = $hari_indo[date('l')];
$waktu_sekarang = date('H:i:s');

$stmt = $conn->prepare("SELECT j.*, m.nama_matkul, u.nama as nama_dosen FROM jadwal j LEFT JOIN matkul m ON j.matkul_id = m.id LEFT JOIN users u ON j.dosen_id = u.id WHERE j.hari = ? ORDER BY j.jam_mulai ASC");
$stmt->execute([$hari_ini]);
$jadwal_hari_ini = $stmt->fetchAll();

// Riwayat terakhir
$stmt = $conn->prepare("SELECT a.*, m.nama_matkul, j.hari FROM absensi a LEFT JOIN jadwal j ON a.jadwal_id = j.id LEFT JOIN matkul m ON j.matkul_id = m.id WHERE a.user_id = ? ORDER BY a.tanggal DESC, a.waktu_scan DESC LIMIT 5");
$stmt->execute([$user_id]);
$riwayat_terakhir = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mahasiswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { 
            background: linear-gradient(135deg, #faf5ff 0%, #fdf2f8 50%, #fce7f3 100%); 
            min-height: 100vh;
        }
        
        /* Navbar */
        .navbar-mhs {
            background: linear-gradient(135deg, #7c3aed 0%, #db2777 100%);
            padding: 20px 40px;
            box-shadow: 0 10px 30px rgba(124, 58, 237, 0.3);
            position: relative;
            overflow: hidden;
        }
        .navbar-mhs::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
        }
        .brand-mhs {
            font-family: 'Space Grotesk', sans-serif;
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
        }
        .brand-mhs span { color: #fde047; }
        .user-badge {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 50px;
            padding: 8px 20px;
            color: white;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .user-avatar-mhs {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #fde047, #fbbf24);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #7c3aed;
        }
        .btn-logout-mhs {
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            padding: 8px 16px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            display: inline-block;
        }
        .btn-logout-mhs:hover { background: rgba(255,255,255,0.3); color: white; }
        
        /* Hero Banner */
        .hero-banner {
            background: linear-gradient(135deg, #7c3aed 0%, #db2777 100%);
            border-radius: 25px;
            padding: 50px;
            color: white;
            position: relative;
            overflow: hidden;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(124, 58, 237, 0.3);
        }
        .hero-banner::after {
            content: '🎓';
            position: absolute;
            right: 50px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 10rem;
            opacity: 0.15;
        }
        .hero-banner h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 15px;
        }
        .hero-banner p { font-size: 1.1rem; opacity: 0.95; }
        .hero-stats {
            display: flex;
            gap: 20px;
            margin-top: 25px;
            flex-wrap: wrap;
        }
        .hero-stat-item {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 15px;
            padding: 15px 25px;
        }
        .hero-stat-item .number { font-size: 1.8rem; font-weight: 700; }
        .hero-stat-item .label { font-size: 0.85rem; opacity: 0.9; }
        
        /* Percentage Circle */
        .percentage-card {
            background: white;
            border-radius: 25px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .percentage-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #7c3aed, #db2777);
        }
        .circle-progress {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: conic-gradient(#7c3aed <?= $persentase ?>%, #f3f4f6 0);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            position: relative;
        }
        .circle-progress::before {
            content: '';
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: white;
            position: absolute;
        }
        .circle-progress .value {
            position: relative;
            z-index: 1;
            font-size: 2.5rem;
            font-weight: 700;
            color: #7c3aed;
            font-family: 'Space Grotesk', sans-serif;
        }
        .percentage-card h5 { color: #6b7280; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; }
        
        /* Menu Cards */
        .menu-mhs {
            background: white;
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }
        .menu-mhs:hover { transform: translateY(-10px); border-color: #7c3aed; box-shadow: 0 20px 40px rgba(124, 58, 237, 0.2); color: inherit; }
        .menu-mhs::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #7c3aed, #db2777);
        }
        .menu-icon-mhs {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin: 0 auto 15px;
            color: white;
        }
        .menu-mhs h5 { font-weight: 600; color: #1f2937; margin-bottom: 8px; }
        .menu-mhs p { color: #6b7280; font-size: 0.85rem; margin: 0; }
        
        /* Jadwal Card */
        .jadwal-card-mhs {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }
        .jadwal-item-mhs {
            display: flex;
            align-items: center;
            padding: 18px;
            border-radius: 15px;
            margin-bottom: 12px;
            background: linear-gradient(135deg, #faf5ff, #fdf2f8);
            border-left: 4px solid #7c3aed;
            transition: all 0.3s;
        }
        .jadwal-item-mhs:hover { transform: translateX(10px); box-shadow: 0 5px 15px rgba(124, 58, 237, 0.2); }
        .jadwal-time-mhs {
            background: linear-gradient(135deg, #7c3aed, #db2777);
            color: white;
            padding: 12px 18px;
            border-radius: 12px;
            font-weight: 600;
            margin-right: 20px;
            min-width: 110px;
            text-align: center;
        }
        .jadwal-info-mhs { flex-grow: 1; }
        .jadwal-info-mhs h6 { color: #1f2937; font-weight: 600; margin-bottom: 3px; }
        .jadwal-info-mhs small { color: #6b7280; }
        
        /* Tombol Absen di Dashboard */
        .btn-absen-dash {
            border-radius: 50px;
            padding: 8px 20px;
            font-weight: 600;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
            transition: all 0.3s;
            min-width: 110px;
            justify-content: center;
        }
        .btn-absen-buka {
            background: linear-gradient(135deg, #7c3aed, #db2777);
            color: white;
            cursor: pointer;
        }
        .btn-absen-buka:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(124, 58, 237, 0.4);
            color: white;
        }
        .btn-absen-tutup {
            background: #e5e7eb;
            color: #9ca3af;
            cursor: not-allowed;
        }
        
        /* Riwayat Table */
        .riwayat-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .riwayat-card thead { background: linear-gradient(135deg, #7c3aed, #db2777); color: white; }
        .riwayat-card th { padding: 15px; font-weight: 600; border: none; }
        .riwayat-card td { padding: 15px; vertical-align: middle; }
        .riwayat-card tbody tr { transition: all 0.3s; }
        .riwayat-card tbody tr:hover { background: #faf5ff; }
        
        .badge-mhs { padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .badge-hadir-mhs { background: #d1fae5; color: #065f46; }
        .badge-izin-mhs { background: #dbeafe; color: #1e40af; }
        .badge-sakit-mhs { background: #fef3c7; color: #92400e; }
        .badge-terlambat-mhs { background: #fee2e2; color: #991b1b; }
        .badge-tidak-hadir-mhs { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar-mhs">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="brand-mhs"><i class="fas fa-graduation-cap me-2"></i>Student<span>Hub</span></h1>
                    <div style="color: rgba(255,255,255,0.8); font-size: 0.85rem;">Sistem Absensi Mahasiswa</div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="user-badge">
                        <div class="user-avatar-mhs"><?= substr($user_nama, 0, 1) ?></div>
                        <div>
                            <div style="font-weight: 600; font-size: 0.9rem;"><?= htmlspecialchars($user_nama) ?></div>
                            <div style="font-size: 0.75rem; opacity: 0.8;"><?= $user_nim ?></div>
                        </div>
                    </div>
                    <a href="/kampus-absensi/public/logout.php" class="btn-logout-mhs">
                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <!-- Hero Banner -->
        <div class="hero-banner">
            <h1>Halo, <?= htmlspecialchars(explode(' ', $user_nama)[0]) ?>! 👋</h1>
            <p>Semangat kuliahnya hari ini! Jangan lupa absen ya.</p>
            <div class="hero-stats">
                <div class="hero-stat-item">
                    <div class="number"><?= $stats['total'] ?></div>
                    <div class="label">Total Absen</div>
                </div>
                <div class="hero-stat-item">
                    <div class="number"><?= $stats['hadir'] ?></div>
                    <div class="label">Hadir</div>
                </div>
                <div class="hero-stat-item">
                    <div class="number"><?= $stats['izin_sakit'] ?></div>
                    <div class="label">Izin/Sakit</div>
                </div>
                <div class="hero-stat-item">
                    <div class="number"><?= $stats['terlambat'] ?></div>
                    <div class="label">Terlambat</div>
                </div>
                <div class="hero-stat-item">
                    <div class="number"><?= $stats['tidak_hadir'] ?></div>
                    <div class="label">Tidak Hadir</div>
                </div>
            </div>
        </div>

        <!-- Percentage + Menu -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="percentage-card">
                    <div class="circle-progress">
                        <div class="value"><?= $persentase ?>%</div>
                    </div>
                    <h5>Persentase Kehadiran</h5>
                    <div class="mt-2">
                        <?php if($persentase >= 85): ?>
                            <span class="badge bg-success">✅ Aman</span>
                        <?php else: ?>
                            <span class="badge bg-danger">⚠️ Kurang</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="row g-3 h-100">
                    <div class="col-md-6">
                        <a href="/kampus-absensi/public/index.php?page=mahasiswa_absen_hadir" class="menu-mhs h-100">
                            <div class="menu-icon-mhs" style="background: linear-gradient(135deg, #7c3aed, #db2777);">
                                <i class="fas fa-camera"></i>
                            </div>
                            <h5>Absen Hadir</h5>
                            <p>Scan wajah untuk absen</p>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="/kampus-absensi/public/index.php?page=mahasiswa_absen_izin_sakit" class="menu-mhs h-100">
                            <div class="menu-icon-mhs" style="background: linear-gradient(135deg, #f59e0b, #ef4444);">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <h5>Izin / Sakit</h5>
                            <p>Ajukan izin atau sakit</p>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="/kampus-absensi/public/index.php?page=mahasiswa_riwayat" class="menu-mhs h-100">
                            <div class="menu-icon-mhs" style="background: linear-gradient(135deg, #3b82f6, #06b6d4);">
                                <i class="fas fa-history"></i>
                            </div>
                            <h5>Riwayat Absen</h5>
                            <p>Lihat semua riwayat</p>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="/kampus-absensi/public/index.php?page=mahasiswa_detail_matkul" class="menu-mhs h-100">
                            <div class="menu-icon-mhs" style="background: linear-gradient(135deg, #10b981, #059669);">
                                <i class="fas fa-book"></i>
                            </div>
                            <h5>Jadwal Kuliah</h5>
                            <p>Lihat jadwal matkul</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Jadwal Hari Ini -->
        <div class="jadwal-card-mhs mb-4">
            <h4 class="mb-4" style="color: #7c3aed; font-family: 'Space Grotesk', sans-serif; font-weight: 700;">
                <i class="fas fa-calendar-day me-2"></i>Jadwal Hari Ini (<?= $hari_ini ?>)
            </h4>
            <?php if(count($jadwal_hari_ini) > 0): ?>
                <?php foreach($jadwal_hari_ini as $j): 
                    
                    // --- LOGIC TOMBOL DASHBOARD ---
                    $stmt_absen = $conn->prepare("SELECT id FROM absensi WHERE user_id = ? AND jadwal_id = ? AND tanggal = ?");
                    $stmt_absen->execute([$user_id, $j['id'], date('Y-m-d')]);
                    $sudah_absen = $stmt_absen->fetch();

                    $bisa_absen = false;
                    if ($sudah_absen) {
                        $bisa_absen = false; // Sudah absen
                    } elseif ($j['manual_override'] == 1 && $j['is_open'] == 1) {
                        $bisa_absen = true; // Admin force buka
                    } elseif ($waktu_sekarang >= $j['jam_mulai'] && $waktu_sekarang <= $j['jam_selesai']) {
                        $bisa_absen = true; // Dalam rentang waktu
                    }
                ?>
                <div class="jadwal-item-mhs">
                    <div class="jadwal-time-mhs">
                        <div style="font-size: 0.7rem; opacity: 0.8;">MULAI</div>
                        <div><?= date('H:i', strtotime($j['jam_mulai'])) ?></div>
                    </div>
                    <div class="jadwal-info-mhs">
                        <h6><?= htmlspecialchars($j['nama_matkul']) ?></h6>
                        <small><i class="fas fa-user-tie me-1"></i> <?= htmlspecialchars($j['nama_dosen']) ?></small>
                    </div>
                    
                    <!-- TOMBOL DINAMIS -->
                    <?php if ($bisa_absen): ?>
                        <a href="/kampus-absensi/public/index.php?page=mahasiswa_absen_hadir&jadwal_id=<?= $j['id'] ?>" class="btn-absen-dash btn-absen-buka">
                            <i class="fas fa-check"></i> Absen
                        </a>
                    <?php else: ?>
                        <button class="btn-absen-dash btn-absen-tutup" disabled title="Absensi ditutup">
                            <i class="fas fa-lock"></i> Tutup
                        </button>
                    <?php endif; ?>
                    
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-coffee fa-3x mb-3" style="color: #7c3aed; opacity: 0.5;"></i>
                    <p class="text-muted mb-0">Tidak ada jadwal hari ini. Santai dulu! 😊</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Riwayat Terakhir -->
        <div class="riwayat-card">
            <div class="p-4" style="background: linear-gradient(135deg, #faf5ff, #fdf2f8); border-bottom: 1px solid #f3f4f6;">
                <h5 class="mb-0" style="color: #7c3aed; font-family: 'Space Grotesk', sans-serif; font-weight: 700;">
                    <i class="fas fa-clock me-2"></i>5 Absensi Terakhir
                </h5>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr><th>Tanggal</th><th>Hari</th><th>Mata Kuliah</th><th>Waktu</th><th>Status</th><th>%</th></tr>
                    </thead>
                    <tbody>
                        <?php if(count($riwayat_terakhir) > 0): ?>
                            <?php foreach($riwayat_terakhir as $r): ?>
                            <tr>
                                <td><strong><?= date('d/m/Y', strtotime($r['tanggal'])) ?></strong></td>
                                <td><?= htmlspecialchars($r['hari']) ?></td>
                                <td><?= htmlspecialchars($r['nama_matkul']) ?></td>
                                <td><?= date('H:i', strtotime($r['waktu_scan'])) ?></td>
                                <td><span class="badge-mhs badge-<?= strtolower(str_replace(['Terlambat_Murni', 'Terlambat_Beralasan', 'Tidak Hadir'], ['terlambat', 'terlambat', 'tidak-hadir'], $r['status_sistem'])) ?>-mhs"><?= htmlspecialchars(str_replace('_', ' ', $r['status_sistem'])) ?></span></td>
                                <td><strong style="color: #7c3aed;"><?= number_format($r['persentase_final'] ?? $r['persentase_harian'], 0) ?>%</strong></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada riwayat absensi. Yuk mulai absen! 📝</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>