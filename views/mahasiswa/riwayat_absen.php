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

// Ambil riwayat absensi
$stmt = $conn->prepare("SELECT a.*, m.nama_matkul, j.hari, u.nama as nama_dosen FROM absensi a LEFT JOIN jadwal j ON a.jadwal_id = j.id LEFT JOIN matkul m ON j.matkul_id = m.id LEFT JOIN users u ON j.dosen_id = u.id WHERE a.user_id = ? ORDER BY a.tanggal DESC, a.waktu_scan DESC");
$stmt->execute([$user_id]);
$riwayat = $stmt->fetchAll();

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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Absensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { background: linear-gradient(135deg, #faf5ff 0%, #fdf2f8 100%); min-height: 100vh; }
        
        .page-header-mhs {
            background: linear-gradient(135deg, #7c3aed 0%, #db2777 100%);
            padding: 40px;
            color: white;
            margin-bottom: 30px;
            position: relative;
        }
        .page-header-mhs h1 { font-family: 'Space Grotesk', sans-serif; margin: 0; font-size: 2.2rem; }
        .page-header-mhs p { opacity: 0.95; margin: 10px 0 0; }
        
        .back-btn-mhs {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid rgba(255,255,255,0.3);
            padding: 10px 20px;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
            position: relative;
            z-index: 9999;
            pointer-events: auto;
            cursor: pointer;
        }
        .back-btn-mhs:hover { background: rgba(255,255,255,0.3); color: white; }
        
        .stats-row-mhs { display: flex; gap: 15px; margin-bottom: 30px; flex-wrap: wrap; }
        .stat-mini-mhs {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            flex: 1;
            min-width: 150px;
            border-top: 4px solid #7c3aed;
        }
        .stat-mini-mhs .number { font-size: 2rem; font-weight: 700; color: #7c3aed; font-family: 'Space Grotesk', sans-serif; }
        .stat-mini-mhs .label { color: #6b7280; font-size: 0.85rem; text-transform: uppercase; }
        
        .riwayat-card-mhs {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .riwayat-card-mhs thead { background: linear-gradient(135deg, #7c3aed, #db2777); color: white; }
        .riwayat-card-mhs th { padding: 15px; font-weight: 600; border: none; }
        .riwayat-card-mhs td { padding: 15px; vertical-align: middle; }
        .riwayat-card-mhs tbody tr { transition: all 0.3s; }
        .riwayat-card-mhs tbody tr:hover { background: #faf5ff; }
        
        .badge-mhs { padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .badge-hadir-mhs { background: #d1fae5; color: #065f46; }
        .badge-izin-mhs { background: #dbeafe; color: #1e40af; }
        .badge-sakit-mhs { background: #fef3c7; color: #92400e; }
        .badge-terlambat-mhs { background: #fed7aa; color: #9a3412; }
        .badge-tidak-hadir-mhs { background: #fee2e2; color: #991b1b; }
        
        .badge-verify-mhs { padding: 5px 10px; border-radius: 15px; font-size: 0.75rem; font-weight: 600; }
        .badge-disetujui-mhs { background: #d1fae5; color: #065f46; }
        .badge-ditolak-mhs { background: #fee2e2; color: #991b1b; }
        .badge-pending-mhs { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
    <div class="page-header-mhs">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center" style="position: relative; z-index: 10;">
                <div>
                    <h1><i class="fas fa-history me-3"></i>Riwayat Absensi</h1>
                    <p>Rekap lengkap kehadiran Anda</p>
                </div>
                <a href="/kampus-absensi/public/index.php?page=mahasiswa_dashboard" class="back-btn-mhs">
                    <i class="fas fa-arrow-left me-2"></i>Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="stats-row-mhs">
            <div class="stat-mini-mhs">
                <div class="number"><?= $stats['total'] ?></div>
                <div class="label">Total Absen</div>
            </div>
            <div class="stat-mini-mhs">
                <div class="number" style="color: #10b981;"><?= $stats['hadir'] ?></div>
                <div class="label">Hadir</div>
            </div>
            <div class="stat-mini-mhs">
                <div class="number" style="color: #3b82f6;"><?= $stats['izin_sakit'] ?></div>
                <div class="label">Izin/Sakit</div>
            </div>
            <div class="stat-mini-mhs">
                <div class="number" style="color: #f59e0b;"><?= $stats['terlambat'] ?></div>
                <div class="label">Terlambat</div>
            </div>
            <div class="stat-mini-mhs">
                <div class="number" style="color: #ef4444;"><?= $stats['tidak_hadir'] ?></div>
                <div class="label">Tidak Hadir</div>
            </div>
            <div class="stat-mini-mhs" style="border-top-color: #db2777;">
                <div class="number" style="color: #db2777;"><?= $persentase ?>%</div>
                <div class="label">Persentase</div>
            </div>
        </div>

        <div class="riwayat-card-mhs">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Hari</th>
                            <th>Mata Kuliah</th>
                            <th>Dosen</th>
                            <th>Waktu</th>
                            <th>Status</th>
                            <th>%</th>
                            <th>Verifikasi</th>
                            <th>Bukti</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($riwayat) > 0): ?>
                            <?php foreach($riwayat as $r): ?>
                            <tr>
                                <td><strong><?= date('d/m/Y', strtotime($r['tanggal'])) ?></strong></td>
                                <td><?= htmlspecialchars($r['hari']) ?></td>
                                <td><?= htmlspecialchars($r['nama_matkul']) ?></td>
                                <td><?= htmlspecialchars($r['nama_dosen']) ?></td>
                                <td><?= date('H:i', strtotime($r['waktu_scan'])) ?></td>
                                <td>
                                    <span class="badge-mhs badge-<?= 
                                        strtolower(str_replace(['Terlambat_Murni', 'Terlambat_Beralasan', 'Tidak Hadir'], 
                                        ['terlambat', 'terlambat', 'tidak-hadir'], $r['status_sistem'])) 
                                    ?>-mhs">
                                        <?= htmlspecialchars(str_replace('_', ' ', $r['status_sistem'])) ?>
                                    </span>
                                </td>
                                <td><strong style="color: #7c3aed;"><?= number_format($r['persentase_final'] ?? $r['persentase_harian'], 0) ?>%</strong></td>
                                <td><span class="badge-verify-mhs badge-<?= strtolower($r['status_verifikasi']) ?>-mhs"><?= $r['status_verifikasi'] ?></span></td>
                                <td>
                                    <?php if(!empty($r['bukti_path'])): ?>
                                        <a href="/kampus-absensi/public/<?= htmlspecialchars($r['bukti_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary" style="border-radius: 50px;">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="text-center py-5 text-muted">Belum ada riwayat absensi. Yuk mulai absen! 📝</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>