<?php
date_default_timezone_set('Asia/Makassar');
// HAPUS session_start() karena sudah di index.php
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /kampus-absensi/public/index.php?page=login");
    exit();
}

require_once __DIR__ . "/../../config/database.php";
$database = new Database();
$conn = $database->getConnection();

// Ambil semua absensi dengan detail lengkap
$stmt = $conn->query("
    SELECT a.*, 
           u.nama as nama_user, u.nip_nim, u.role,
           m.nama_matkul, j.hari, j.jam_mulai, j.jam_selesai,
           d.nama as nama_dosen
    FROM absensi a 
    LEFT JOIN users u ON a.user_id = u.id 
    LEFT JOIN jadwal j ON a.jadwal_id = j.id 
    LEFT JOIN matkul m ON j.matkul_id = m.id 
    LEFT JOIN users d ON j.dosen_id = d.id
    ORDER BY a.tanggal DESC, a.waktu_scan DESC
");
$absensi = $stmt->fetchAll();

// Statistik dengan Tidak Hadir
$total = count($absensi);
$hadir = count(array_filter($absensi, fn($a) => $a['status_sistem'] === 'Hadir'));
$izin = count(array_filter($absensi, fn($a) => in_array($a['status_sistem'], ['Izin', 'Sakit'])));
$terlambat = count(array_filter($absensi, fn($a) => strpos($a['status_sistem'], 'Terlambat') !== false));
$tidak_hadir = count(array_filter($absensi, fn($a) => $a['status_sistem'] === 'Tidak Hadir'));
$pending = count(array_filter($absensi, fn($a) => $a['status_verifikasi'] === 'Pending'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Absensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #4e73df; --primary-dark: #224abe; --sidebar-width: 260px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: #f8f9fc; font-family: 'Segoe UI', Tahoma, sans-serif; }
        
        .sidebar {
            position: fixed; top: 0; left: 0; width: var(--sidebar-width); height: 100vh;
            background: linear-gradient(180deg, var(--primary) 10%, var(--primary-dark) 100%);
            color: white; z-index: 1000; overflow-y: auto;
        }
        .sidebar-brand {
            height: 70px; display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; font-weight: 800; color: white; text-decoration: none;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.8); padding: 15px 20px;
            display: flex; align-items: center; transition: all 0.3s; text-decoration: none;
        }
        .sidebar .nav-link:hover { color: white; background: rgba(255,255,255,0.1); }
        .sidebar .nav-link.active { color: white; font-weight: 700; background: rgba(255,255,255,0.15); }
        .sidebar .nav-link i { margin-right: 10px; width: 20px; text-align: center; }
        
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        .topbar {
            height: 70px; background: white;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 30px; position: sticky; top: 0; z-index: 100;
        }
        .content-wrapper { padding: 30px; }
        
        .stat-card {
            border: none; border-left: 4px solid; border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            transition: transform 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card.primary { border-left-color: #4e73df; }
        .stat-card.success { border-left-color: #1cc88a; }
        .stat-card.info { border-left-color: #36b9cc; }
        .stat-card.warning { border-left-color: #f6c23e; }
        .stat-card.danger { border-left-color: #e74a3b; }
        .stat-number { font-size: 1.8rem; font-weight: 700; color: #5a5c69; }
        .stat-label { font-size: 0.85rem; text-transform: uppercase; color: #858796; font-weight: 700; }
        
        .table-card { background: white; border-radius: 10px; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); overflow: hidden; }
        .table-card thead { background: #4e73df; color: white; }
        .table-card th { padding: 15px; font-weight: 600; border: none; }
        .table-card td { padding: 12px 15px; vertical-align: middle; }
        .table-card tbody tr:hover { background: #f8f9fc; }
        
        .badge-status { padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .badge-hadir { background: #d1fae5; color: #065f46; }
        .badge-izin { background: #dbeafe; color: #1e40af; }
        .badge-sakit { background: #fef3c7; color: #92400e; }
        .badge-terlambat { background: #fee2e2; color: #991b1b; }
        .badge-tidak-hadir { background: #fee2e2; color: #991b1b; }
        .badge-role-mhs { background: #4e73df; color: white; }
        .badge-role-dsn { background: #1cc88a; color: white; }
        .badge-verify { padding: 5px 10px; border-radius: 15px; font-size: 0.75rem; font-weight: 600; }
        .badge-disetujui { background: #d1fae5; color: #065f46; }
        .badge-ditolak { background: #fee2e2; color: #991b1b; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        
        .btn-hapus {
            padding: 5px 10px;
            border-radius: 5px;
            border: none;
            background: #e74a3b;
            color: white;
            transition: all 0.3s;
        }
        .btn-hapus:hover {
            background: #c0392b;
            transform: scale(1.1);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <a class="sidebar-brand" href="/kampus-absensi/public/index.php?page=admin_dashboard">
            <i class="fas fa-university me-2"></i> ADMIN PANEL
        </a>
        <ul class="nav flex-column mt-3">
            <li class="nav-item"><a class="nav-link" href="/kampus-absensi/public/index.php?page=admin_dashboard"><i class="fas fa-fw fa-tachometer-alt"></i> Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="/kampus-absensi/public/index.php?page=admin_kelola_mahasiswa"><i class="fas fa-fw fa-user-graduate"></i> Mahasiswa</a></li>
            <li class="nav-item"><a class="nav-link" href="/kampus-absensi/public/index.php?page=admin_kelola_dosen"><i class="fas fa-fw fa-chalkboard-teacher"></i> Dosen</a></li>
            <li class="nav-item"><a class="nav-link" href="/kampus-absensi/public/index.php?page=admin_kelola_matkul"><i class="fas fa-fw fa-book"></i> Mata Kuliah</a></li>
            <li class="nav-item"><a class="nav-link" href="/kampus-absensi/public/index.php?page=admin_kelola_jadwal"><i class="fas fa-fw fa-calendar-alt"></i> Jadwal Kuliah</a></li>
            <li class="nav-item"><a class="nav-link active" href="/kampus-absensi/public/index.php?page=admin_rekap_absensi"><i class="fas fa-fw fa-clipboard-list"></i> Rekap Absensi</a></li>
            <li class="nav-item"><a class="nav-link" href="/kampus-absensi/public/index.php?page=admin_upload_wajah"><i class="fas fa-fw fa-camera"></i> Upload Wajah</a></li>
            <li class="nav-item mt-5"><a class="nav-link" href="/kampus-absensi/public/logout.php"><i class="fas fa-fw fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="topbar">
            <h4 class="mb-0" style="color: #5a5c69;"><i class="fas fa-clipboard-list me-2"></i>Rekap Absensi</h4>
            <div class="d-flex align-items-center">
                <span class="me-3" style="color: #5a5c69;"><i class="fas fa-user-circle me-2"></i><?= htmlspecialchars($_SESSION['nama']) ?></span>
                <a href="/kampus-absensi/public/logout.php" class="btn btn-sm btn-outline-danger"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
        <div class="content-wrapper">
            <!-- ALERT SUCCESS -->
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i>Data absensi berhasil dihapus!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- ALERT ERROR -->
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php
                    $error = $_GET['error'];
                    if ($error === 'id_kosong') {
                        echo 'ID absensi tidak valid!';
                    } elseif ($error === 'data_tidak_ditemukan') {
                        echo 'Data absensi tidak ditemukan di database!';
                    } elseif ($error === 'hapus_gagal') {
                        echo 'Gagal menghapus data absensi!';
                    } else {
                        echo 'Error: ' . htmlspecialchars(urldecode($error));
                    }
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Statistik -->
            <div class="row mb-4">
                <div class="col-xl-2 col-md-4 mb-3">
                    <div class="card stat-card primary p-3">
                        <div class="stat-label mb-1">Total</div>
                        <div class="stat-number"><?= $total ?></div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 mb-3">
                    <div class="card stat-card success p-3">
                        <div class="stat-label mb-1">Hadir</div>
                        <div class="stat-number"><?= $hadir ?></div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 mb-3">
                    <div class="card stat-card info p-3">
                        <div class="stat-label mb-1">Izin/Sakit</div>
                        <div class="stat-number"><?= $izin ?></div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 mb-3">
                    <div class="card stat-card warning p-3">
                        <div class="stat-label mb-1">Terlambat</div>
                        <div class="stat-number"><?= $terlambat ?></div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 mb-3">
                    <div class="card stat-card danger p-3">
                        <div class="stat-label mb-1">Tidak Hadir</div>
                        <div class="stat-number"><?= $tidak_hadir ?></div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 mb-3">
                    <div class="card stat-card primary p-3">
                        <div class="stat-label mb-1">Persentase Hadir</div>
                        <div class="stat-number"><?= $total > 0 ? round(($hadir/$total)*100, 1) : 0 ?>%</div>
                    </div>
                </div>
            </div>

            <!-- Tabel Absensi -->
            <div class="table-card">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Semua Data Absensi</h5>
                    <span class="badge bg-primary"><?= $total ?> data</span>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Nama</th>
                                <th>NIP/NIM</th>
                                <th>Role</th>
                                <th>Mata Kuliah</th>
                                <th>Dosen</th>
                                <th>Hari</th>
                                <th>Waktu</th>
                                <th>Status</th>
                                <th>%</th>
                                <th>Verifikasi</th>
                                <th>Bukti</th>
                                <th width="80">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($absensi) > 0): ?>
                                <?php foreach($absensi as $a): ?>
                                <tr>
                                    <td><strong><?= date('d/m/Y', strtotime($a['tanggal'])) ?></strong></td>
                                    <td><?= htmlspecialchars($a['nama_user']) ?></td>
                                    <td><?= htmlspecialchars($a['nip_nim']) ?></td>
                                    <td>
                                        <span class="badge badge-role-<?= $a['role'] === 'mahasiswa' ? 'mhs' : 'dsn' ?>">
                                            <?= ucfirst($a['role']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($a['nama_matkul'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($a['nama_dosen'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($a['hari'] ?? '-') ?></td>
                                    <td><?= date('H:i', strtotime($a['waktu_scan'])) ?></td>
                                    <td>
                                        <span class="badge-status badge-<?= 
                                            strtolower(str_replace(['Terlambat_Murni', 'Terlambat_Beralasan', 'Tidak Hadir'], 
                                            ['terlambat', 'terlambat', 'tidak-hadir'], $a['status_sistem'])) 
                                        ?>">
                                            <?= htmlspecialchars(str_replace('_', ' ', $a['status_sistem'])) ?>
                                        </span>
                                    </td>
                                    <td><strong><?= number_format($a['persentase_final'] ?? $a['persentase_harian'], 0) ?>%</strong></td>
                                    <td>
                                        <span class="badge-verify badge-<?= strtolower($a['status_verifikasi']) ?>">
                                            <?= $a['status_verifikasi'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($a['bukti_path']): ?>
                                            <a href="/kampus-absensi/public/<?= htmlspecialchars($a['bukti_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn-hapus" onclick="hapusAbsensi(<?= $a['id'] ?>, '<?= addslashes($a['nama_user']) ?>', '<?= date('d/m/Y', strtotime($a['tanggal'])) ?>')" title="Hapus Data Absensi">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="13" class="text-center py-5 text-muted"><i class="fas fa-inbox fa-2x mb-2"></i><br>Belum ada data absensi.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Hapus Absensi (Hidden) -->
    <form id="formHapusAbsensi" method="POST" action="/kampus-absensi/public/process_hapus_absensi.php" style="display:none;">
        <input type="hidden" name="id" id="hapus_absensi_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function hapusAbsensi(id, nama, tanggal) {
            if (confirm('⚠️ PERINGATAN!\n\nYakin ingin menghapus data absensi?\n\nNama: ' + nama + '\nTanggal: ' + tanggal + '\n\nData yang dihapus TIDAK BISA dikembalikan!')) {
                document.getElementById('hapus_absensi_id').value = id;
                document.getElementById('formHapusAbsensi').submit();
            }
        }
    </script>
</body>
</html>