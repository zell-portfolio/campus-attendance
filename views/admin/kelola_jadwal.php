<?php
date_default_timezone_set('Asia/Makassar');
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /kampus-absensi/public/index.php?page=login");
    exit();
}

require_once __DIR__ . "/../../config/database.php";
$database = new Database();
$conn = $database->getConnection();

$stmt = $conn->query("
    SELECT j.id, j.hari, j.jam_mulai, j.jam_selesai, j.semester, j.tahun_ajaran, 
           j.matkul_id, j.dosen_id, j.is_open, j.manual_override,
           m.nama_matkul, m.sks, u.nama as nama_dosen 
    FROM jadwal j 
    LEFT JOIN matkul m ON j.matkul_id = m.id 
    LEFT JOIN users u ON j.dosen_id = u.id 
    ORDER BY FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'), j.jam_mulai ASC
");
$jadwal_list = $stmt->fetchAll();

$matkul_list = $conn->query("SELECT id, nama_matkul, sks FROM matkul ORDER BY nama_matkul ASC")->fetchAll();
$dosen_list = $conn->query("SELECT id, nama FROM users WHERE role='dosen' ORDER BY nama ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Jadwal Kuliah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #4e73df; --primary-dark: #224abe; --sidebar-width: 260px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background-color: #f8f9fc; }
        .sidebar { position: fixed; top: 0; left: 0; width: var(--sidebar-width); height: 100vh; background: linear-gradient(180deg, var(--primary) 10%, var(--primary-dark) 100%); color: white; z-index: 1000; overflow-y: auto; }
        .sidebar-brand { height: 70px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: 800; color: white; text-decoration: none; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar .nav-link { color: rgba(255,255,255,.8); padding: 15px 20px; display: flex; align-items: center; transition: all 0.3s; text-decoration: none; }
        .sidebar .nav-link:hover { color: white; background: rgba(255,255,255,0.1); }
        .sidebar .nav-link.active { color: white; font-weight: 700; background: rgba(255,255,255,0.15); }
        .sidebar .nav-link i { margin-right: 10px; width: 20px; text-align: center; }
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        .topbar { height: 70px; background: white; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); display: flex; align-items: center; justify-content: space-between; padding: 0 30px; position: sticky; top: 0; z-index: 100; }
        .content-wrapper { padding: 30px; }
        .badge-hari { background: #0ea5e9; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
        .btn-action { padding: 5px 10px; margin: 0 2px; }
        .status-open { background: #10b981; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status-closed { background: #ef4444; color: white; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status-auto { background: #6b7280; color: white; padding: 3px 8px; border-radius: 10px; font-size: 0.7rem; }
        .status-force { background: #f59e0b; color: white; padding: 3px 8px; border-radius: 10px; font-size: 0.7rem; }
    </style>
</head>
<body>
    <div class="sidebar">
        <a class="sidebar-brand" href="/kampus-absensi/public/index.php?page=admin_dashboard"><i class="fas fa-university me-2"></i> ADMIN PANEL</a>
        <ul class="nav flex-column mt-3">
            <li class="nav-item"><a class="nav-link" href="/kampus-absensi/public/index.php?page=admin_dashboard"><i class="fas fa-fw fa-tachometer-alt"></i> Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="/kampus-absensi/public/index.php?page=admin_kelola_mahasiswa"><i class="fas fa-fw fa-user-graduate"></i> Mahasiswa</a></li>
            <li class="nav-item"><a class="nav-link" href="/kampus-absensi/public/index.php?page=admin_kelola_dosen"><i class="fas fa-fw fa-chalkboard-teacher"></i> Dosen</a></li>
            <li class="nav-item"><a class="nav-link" href="/kampus-absensi/public/index.php?page=admin_kelola_matkul"><i class="fas fa-fw fa-book"></i> Mata Kuliah</a></li>
            <li class="nav-item"><a class="nav-link active" href="/kampus-absensi/public/index.php?page=admin_kelola_jadwal"><i class="fas fa-fw fa-calendar-alt"></i> Jadwal Kuliah</a></li>
            <li class="nav-item"><a class="nav-link" href="/kampus-absensi/public/index.php?page=admin_rekap_absensi"><i class="fas fa-fw fa-clipboard-list"></i> Rekap Absensi</a></li>
            <li class="nav-item"><a class="nav-link" href="/kampus-absensi/public/index.php?page=admin_upload_wajah"><i class="fas fa-fw fa-camera"></i> Upload Wajah</a></li>
            <li class="nav-item mt-5"><a class="nav-link" href="/kampus-absensi/public/logout.php"><i class="fas fa-fw fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="topbar">
            <h4 class="mb-0" style="color: #5a5c69;"><i class="fas fa-calendar-alt me-2"></i>Kelola Jadwal Kuliah</h4>
            <div class="d-flex align-items-center">
                <span class="me-3" style="color: #5a5c69;"><i class="fas fa-user-circle me-2"></i><?= htmlspecialchars($_SESSION['nama']) ?></span>
                <a href="/kampus-absensi/public/logout.php" class="btn btn-sm btn-outline-danger"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
        <div class="content-wrapper">
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php
                    $success = $_GET['success'];
                    if ($success === 'toggle_open') echo 'Absensi berhasil DIBUKA!';
                    elseif ($success === 'toggle_close') echo 'Absensi berhasil DITUTUP!';
                    else echo 'Jadwal berhasil disimpan!';
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i>Error: <?= htmlspecialchars(urldecode($_GET['error'])) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Kontrol Absensi:</strong> 
                <span class="status-open">🟢 BUKA</span> = Absensi aktif (admin force buka, bisa di luar jam) | 
                <span class="status-closed">🔴 TUTUP</span> = Absensi nonaktif | 
                <span class="status-auto">️ OTOMATIS</span> = Ikuti jam jadwal
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0"><i class="fas fa-list text-primary me-2"></i>Daftar Jadwal</h5>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahModal">
                        <i class="fas fa-plus me-2"></i>Tambah Jadwal
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Hari</th>
                                    <th>Jam</th>
                                    <th>Mata Kuliah</th>
                                    <th>SKS</th>
                                    <th>Dosen</th>
                                    <th>Status Absen</th>
                                    <th width="180">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($jadwal_list as $j): 
                                    // Tentukan status
                                    if ($j['manual_override'] == 1) {
                                        if ($j['is_open'] == 1) {
                                            $statusBadge = '<span class="status-open">🟢 BUKA (Force)</span>';
                                            $toggleBtn = '<form method="POST" action="/kampus-absensi/public/process_toggle_jadwal.php" style="display:inline;"><input type="hidden" name="id" value="'.$j['id'].'"><input type="hidden" name="action" value="close"><button type="submit" class="btn btn-sm btn-danger btn-action" title="Tutup Absensi"><i class="fas fa-lock"></i></button></form>';
                                        } else {
                                            $statusBadge = '<span class="status-closed">🔴 TUTUP (Force)</span>';
                                            $toggleBtn = '<form method="POST" action="/kampus-absensi/public/process_toggle_jadwal.php" style="display:inline;"><input type="hidden" name="id" value="'.$j['id'].'"><input type="hidden" name="action" value="open"><button type="submit" class="btn btn-sm btn-success btn-action" title="Buka Absensi"><i class="fas fa-lock-open"></i></button></form>';
                                        }
                                    } else {
                                        $statusBadge = '<span class="status-auto">⚙️ Otomatis</span>';
                                        $toggleBtn = '
                                            <form method="POST" action="/kampus-absensi/public/process_toggle_jadwal.php" style="display:inline;">
                                                <input type="hidden" name="id" value="'.$j['id'].'">
                                                <input type="hidden" name="action" value="open">
                                                <button type="submit" class="btn btn-sm btn-success btn-action" title="Force Buka"><i class="fas fa-lock-open"></i></button>
                                            </form>
                                            <form method="POST" action="/kampus-absensi/public/process_toggle_jadwal.php" style="display:inline;">
                                                <input type="hidden" name="id" value="'.$j['id'].'">
                                                <input type="hidden" name="action" value="close">
                                                <button type="submit" class="btn btn-sm btn-danger btn-action" title="Force Tutup"><i class="fas fa-lock"></i></button>
                                            </form>';
                                    }
                                ?>
                                <tr>
                                    <td><span class="badge-hari"><?= htmlspecialchars($j['hari']) ?></span></td>
                                    <td><strong><?= date('H:i', strtotime($j['jam_mulai'])) ?> - <?= date('H:i', strtotime($j['jam_selesai'])) ?></strong></td>
                                    <td><?= htmlspecialchars($j['nama_matkul']) ?></td>
                                    <td><span class="badge bg-secondary"><?= $j['sks'] ?> SKS</span></td>
                                    <td><?= htmlspecialchars($j['nama_dosen']) ?></td>
                                    <td><?= $statusBadge ?></td>
                                    <td>
                                        <?= $toggleBtn ?>
                                        <button class="btn btn-sm btn-warning btn-action" onclick="editJadwal(<?= $j['id'] ?>, '<?= $j['hari'] ?>', '<?= $j['jam_mulai'] ?>', '<?= $j['jam_selesai'] ?>', <?= $j['matkul_id'] ?>, <?= $j['dosen_id'] ?>, '<?= $j['semester'] ?? '' ?>', '<?= $j['tahun_ajaran'] ?? '' ?>')" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger btn-action" onclick="hapusJadwal(<?= $j['id'] ?>, '<?= addslashes($j['nama_matkul']) ?>')" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(count($jadwal_list) == 0): ?>
                                    <tr><td colspan="7" class="text-center py-4 text-muted"><i class="fas fa-inbox fa-2x mb-2"></i><br>Belum ada data jadwal.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah -->
    <div class="modal fade" id="tambahModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="/kampus-absensi/public/process_tambah_jadwal.php">
                    <div class="modal-header"><h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Tambah Jadwal</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Hari *</label><select name="hari" class="form-select" required><option value="">-- Pilih --</option><option value="Senin">Senin</option><option value="Selasa">Selasa</option><option value="Rabu">Rabu</option><option value="Kamis">Kamis</option><option value="Jumat">Jumat</option><option value="Sabtu">Sabtu</option></select></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Mata Kuliah *</label><select name="matkul_id" class="form-select" required><option value="">-- Pilih --</option><?php foreach($matkul_list as $m): ?><option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama_matkul']) ?> (<?= $m['sks'] ?> SKS)</option><?php endforeach; ?></select></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Jam Mulai *</label><input type="time" name="jam_mulai" class="form-control" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Jam Selesai *</label><input type="time" name="jam_selesai" class="form-control" required></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Dosen *</label><select name="dosen_id" class="form-select" required><option value="">-- Pilih --</option><?php foreach($dosen_list as $d): ?><option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nama']) ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-3 mb-3"><label class="form-label">Semester *</label><select name="semester" class="form-select" required><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4" selected>4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option></select></div>
                            <div class="col-md-3 mb-3"><label class="form-label">Tahun Ajaran *</label><input type="text" name="tahun_ajaran" class="form-control" value="2024/2025" required></div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="/kampus-absensi/public/process_edit_jadwal.php">
                    <div class="modal-header"><h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Jadwal</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Hari *</label><select name="hari" id="edit_hari" class="form-select" required><option value="Senin">Senin</option><option value="Selasa">Selasa</option><option value="Rabu">Rabu</option><option value="Kamis">Kamis</option><option value="Jumat">Jumat</option><option value="Sabtu">Sabtu</option></select></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Mata Kuliah *</label><select name="matkul_id" id="edit_matkul_id" class="form-select" required><?php foreach($matkul_list as $m): ?><option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama_matkul']) ?> (<?= $m['sks'] ?> SKS)</option><?php endforeach; ?></select></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Jam Mulai *</label><input type="time" name="jam_mulai" id="edit_jam_mulai" class="form-control" required></div>
                            <div class="col-md-6 mb-3"><label class="form-label">Jam Selesai *</label><input type="time" name="jam_selesai" id="edit_jam_selesai" class="form-control" required></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3"><label class="form-label">Dosen *</label><select name="dosen_id" id="edit_dosen_id" class="form-select" required><?php foreach($dosen_list as $d): ?><option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nama']) ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-3 mb-3"><label class="form-label">Semester *</label><select name="semester" id="edit_semester" class="form-select" required><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option></select></div>
                            <div class="col-md-3 mb-3"><label class="form-label">Tahun Ajaran *</label><input type="text" name="tahun_ajaran" id="edit_tahun_ajaran" class="form-control" required></div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Update</button></div>
                </form>
            </div>
        </div>
    </div>

    <form id="formHapus" method="POST" action="/kampus-absensi/public/process_hapus_jadwal.php" style="display:none;">
        <input type="hidden" name="id" id="hapus_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editJadwal(id, hari, jam_mulai, jam_selesai, matkul_id, dosen_id, semester, tahun_ajaran) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_hari').value = hari;
            document.getElementById('edit_jam_mulai').value = jam_mulai;
            document.getElementById('edit_jam_selesai').value = jam_selesai;
            document.getElementById('edit_matkul_id').value = matkul_id;
            document.getElementById('edit_dosen_id').value = dosen_id;
            document.getElementById('edit_semester').value = semester || '4';
            document.getElementById('edit_tahun_ajaran').value = tahun_ajaran || '2024/2025';
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        function hapusJadwal(id, matkul_nama) {
            if (confirm('Yakin hapus jadwal "' + matkul_nama + '"?')) {
                document.getElementById('hapus_id').value = id;
                document.getElementById('formHapus').submit();
            }
        }
    </script>
</body>
</html>