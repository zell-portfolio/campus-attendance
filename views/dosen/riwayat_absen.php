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

// Ambil semua absensi mahasiswa untuk kelas yang diajar dosen ini
$stmt = $conn->prepare("
    SELECT a.*, u.nama as nama_mahasiswa, u.nip_nim, m.nama_matkul, j.hari, j.jam_mulai, j.jam_selesai
    FROM absensi a
    LEFT JOIN jadwal j ON a.jadwal_id = j.id
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN matkul m ON j.matkul_id = m.id
    WHERE j.dosen_id = ? AND u.role = 'mahasiswa'
    ORDER BY a.tanggal DESC, a.waktu_scan DESC
    LIMIT 50
");
$stmt->execute([$dosen_id]);
$riwayat_list = $stmt->fetchAll();

// Statistik - DIPISAH
$total = count($riwayat_list);
$hadir = count(array_filter($riwayat_list, fn($r) => $r['status_sistem'] === 'Hadir'));
$izin = count(array_filter($riwayat_list, fn($r) => in_array($r['status_sistem'], ['Izin', 'Sakit'])));
$terlambat = count(array_filter($riwayat_list, fn($r) => strpos($r['status_sistem'], 'Terlambat') !== false));
$tidak_hadir = count(array_filter($riwayat_list, fn($r) => $r['status_sistem'] === 'Tidak Hadir'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Mengajar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%); min-height: 100vh; }
        .header-riwayat { background: linear-gradient(135deg, #064e3b 0%, #059669 100%); padding: 40px; color: white; margin-bottom: 30px; }
        .header-riwayat h1 { font-family: 'Playfair Display', serif; margin: 0; font-size: 2.2rem; }
        .header-riwayat p { opacity: 0.9; margin: 10px 0 0; }
        .btn-back { background: rgba(255,255,255,0.2); color: white; border: 2px solid rgba(255,255,255,0.3); padding: 10px 20px; border-radius: 10px; text-decoration: none; transition: all 0.3s; display: inline-block; }
        .btn-back:hover { background: rgba(255,255,255,0.3); color: white; }
        
        .stat-box { background: white; border-radius: 15px; padding: 20px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .stat-box .number { font-size: 2rem; font-weight: 700; color: #064e3b; }
        .stat-box .label { color: #6b7280; font-size: 0.85rem; text-transform: uppercase; }
        
        .table-riwayat { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .table-riwayat thead { background: linear-gradient(135deg, #059669, #064e3b); color: white; }
        .table-riwayat th { padding: 15px; font-weight: 600; border: none; }
        .table-riwayat td { padding: 15px; vertical-align: middle; }
        .table-riwayat tbody tr { transition: all 0.3s; }
        .table-riwayat tbody tr:hover { background: #f0fdf4; }
        
        .badge-status { padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .badge-hadir { background: #d1fae5; color: #065f46; }
        .badge-izin { background: #dbeafe; color: #1e40af; }
        .badge-sakit { background: #fef3c7; color: #92400e; }
        .badge-terlambat { background: #fee2e2; color: #991b1b; }
        .badge-tidak-hadir { background: #fee2e2; color: #991b1b; }
        
        .empty-state { text-align: center; padding: 60px 20px; color: #9ca3af; }
        .empty-state i { font-size: 4rem; margin-bottom: 20px; opacity: 0.5; }
    </style>
</head>
<body>
    <div class="header-riwayat">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-history me-3"></i>Riwayat Mengajar</h1>
                    <p>Rekap absensi MAHASISWA dari kelas yang Anda ajar</p>
                </div>
                <a href="/kampus-absensi/public/index.php?page=dosen_dashboard" class="btn-back">
                    <i class="fas fa-arrow-left me-2"></i>Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Statistik DIPISAH menjadi 5 kotak -->
        <div class="row g-4 mb-4">
            <div class="col-md-2">
                <div class="stat-box">
                    <div class="number"><?= $total ?></div>
                    <div class="label">Total Absensi MHS</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-box">
                    <div class="number" style="color: #10b981;"><?= $hadir ?></div>
                    <div class="label">Hadir</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-box">
                    <div class="number" style="color: #3b82f6;"><?= $izin ?></div>
                    <div class="label">Izin/Sakit</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="number" style="color: #f59e0b;"><?= $terlambat ?></div>
                    <div class="label">Terlambat</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="number" style="color: #ef4444;"><?= $tidak_hadir ?></div>
                    <div class="label">Tidak Hadir</div>
                </div>
            </div>
        </div>

        <div class="table-riwayat">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Hari</th>
                        <th>Mahasiswa</th>
                        <th>Mata Kuliah</th>
                        <th>Waktu</th>
                        <th>Status</th>
                        <th>%</th>
                        <th>Verifikasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($riwayat_list) > 0): ?>
                        <?php foreach($riwayat_list as $r): ?>
                        <tr>
                            <td><strong><?= date('d/m/Y', strtotime($r['tanggal'])) ?></strong></td>
                            <td><?= htmlspecialchars($r['hari']) ?></td>
                            <td>
                                <div><?= htmlspecialchars($r['nama_mahasiswa']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($r['nip_nim']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($r['nama_matkul']) ?></td>
                            <td><?= date('H:i', strtotime($r['waktu_scan'])) ?></td>
                            <td>
                                <span class="badge-status badge-<?= 
                                    strtolower(str_replace(['Terlambat_Murni', 'Terlambat_Beralasan', 'Tidak Hadir'], 
                                    ['terlambat', 'terlambat', 'tidak-hadir'], $r['status_sistem'])) 
                                ?>">
                                    <?= htmlspecialchars(str_replace('_', ' ', $r['status_sistem'])) ?>
                                </span>
                            </td>
                            <td><strong><?= number_format($r['persentase_final'] ?? $r['persentase_harian'], 0) ?>%</strong></td>
                            <td>
                                <span class="badge bg-<?= $r['status_verifikasi'] === 'Disetujui' ? 'success' : ($r['status_verifikasi'] === 'Ditolak' ? 'danger' : 'warning') ?>">
                                    <?= $r['status_verifikasi'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <p class="mt-3">Belum ada absensi mahasiswa di kelas Anda.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>