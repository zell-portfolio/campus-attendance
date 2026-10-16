<?php
require_once __DIR__ . "/../../config/database.php";
$database = new Database();
$conn = $database->getConnection();
$mahasiswa = $conn->query("SELECT * FROM users WHERE role='mahasiswa' ORDER BY nama ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Mahasiswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #4e73df; --primary-dark: #224abe; --sidebar-width: 260px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: #f8f9fc; font-family: 'Segoe UI', Tahoma, sans-serif; }
        .sidebar { position: fixed; top: 0; left: 0; width: var(--sidebar-width); height: 100vh; background: linear-gradient(180deg, var(--primary) 10%, var(--primary-dark) 100%); color: white; z-index: 1000; overflow-y: auto; }
        .sidebar-brand { height: 70px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; font-weight: 800; color: white; text-decoration: none; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar .nav-link { color: rgba(255,255,255,.8); padding: 15px 20px; display: flex; align-items: center; transition: all 0.3s; text-decoration: none; }
        .sidebar .nav-link:hover { color: white; background: rgba(255,255,255,0.1); }
        .sidebar .nav-link.active { color: white; font-weight: 700; background: rgba(255,255,255,0.15); }
        .sidebar .nav-link i { margin-right: 10px; width: 20px; text-align: center; }
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        .topbar { height: 70px; background: white; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15); display: flex; align-items: center; justify-content: space-between; padding: 0 30px; position: sticky; top: 0; z-index: 100; }
        .content-wrapper { padding: 30px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <a class="sidebar-brand" href="/kampus-absensi/public/index.php?page=admin_dashboard"><i class="fas fa-university me-2"></i> ADMIN PANEL</a>
        <ul class="nav flex-column mt-3">
            <li class="nav-item"><a class="nav-link" href="/kampus-absensi/public/index.php?page=admin_dashboard"><i class="fas fa-fw fa-tachometer-alt"></i> Dashboard</a></li>
            <li class="nav-item"><a class="nav-link active" href="/kampus-absensi/public/index.php?page=admin_kelola_mahasiswa"><i class="fas fa-fw fa-user-graduate"></i> Mahasiswa</a></li>
            <li class="nav-item"><a class="nav-link" href="/kampus-absensi/public/index.php?page=admin_kelola_dosen"><i class="fas fa-fw fa-chalkboard-teacher"></i> Dosen</a></li>
            <li class="nav-item"><a class="nav-link" href="/kampus-absensi/public/index.php?page=admin_kelola_matkul"><i class="fas fa-fw fa-book"></i> Mata Kuliah</a></li>
            <li class="nav-item"><a class="nav-link" href="/kampus-absensi/public/index.php?page=admin_kelola_jadwal"><i class="fas fa-fw fa-calendar-alt"></i> Jadwal Kuliah</a></li>

            <li class="nav-item"><a class="nav-link" href="/kampus-absensi/public/index.php?page=admin_rekap_absensi"><i class="fas fa-fw fa-clipboard-list"></i> Rekap Absensi</a></li>
            <li class="nav-item"><a class="nav-link" href="/kampus-absensi/public/index.php?page=admin_upload_wajah"><i class="fas fa-fw fa-camera"></i> Upload Wajah</a></li>
            <li class="nav-item mt-5"><a class="nav-link" href="/kampus-absensi/public/logout.php"><i class="fas fa-fw fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="topbar">
            <h4 class="mb-0" style="color: #5a5c69;">Kelola Mahasiswa</h4>
            <div class="d-flex align-items-center">
                <span class="me-3" style="color: #5a5c69;"><i class="fas fa-user-circle me-2"></i><?= htmlspecialchars($_SESSION['nama']) ?></span>
                <a href="/kampus-absensi/public/logout.php" class="btn btn-sm btn-outline-danger"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
        <div class="content-wrapper">
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i>Data berhasil disimpan!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0"><i class="fas fa-user-graduate text-primary me-2"></i>Daftar Mahasiswa</h5>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahModal"><i class="fas fa-plus me-2"></i>Tambah Mahasiswa</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light"><tr><th>NIM</th><th>Nama</th><th>Email</th><th>Foto</th><th width="150">Aksi</th></tr></thead>
                            <tbody>
                                <?php foreach($mahasiswa as $m): 
                                    $fotoUrl = !empty($m['foto_wajah_path']) ? '/kampus-absensi/public/' . $m['foto_wajah_path'] : '';
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($m['nip_nim']) ?></td>
                                    <td><?= htmlspecialchars($m['nama']) ?></td>
                                    <td><?= htmlspecialchars($m['email']) ?></td>
                                    <td>
                                        <?php if(!empty($m['foto_wajah_path'])): ?>
                                            <img src="<?= $fotoUrl ?>" class="rounded-circle" width="50" height="50" style="object-fit: cover; border: 2px solid #ddd;" 
                                                 onerror="this.onerror=null; this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2250%22 height=%2250%22><rect fill=%22%23fee2e2%22 width=%2250%22 height=%2250%22/><text fill=%22%23ef4444%22 x=%2250%%22 y=%2250%%22 text-anchor=%22middle%22 font-size=%2210%22>ERROR</text></svg>'; this.title='File tidak ditemukan di server';">
                                            <span class="badge bg-success ms-2"><i class="fas fa-check"></i> Ada</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Belum</span a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick="editMahasiswa(<?= $m['id'] ?>, '<?= htmlspecialchars($m['nip_nim'], ENT_QUOTES) ?>', '<?= htmlspecialchars($m['nama'], ENT_QUOTES) ?>', '<?= htmlspecialchars($m['email'], ENT_QUOTES) ?>')"><i class="fas fa-edit"></i></button>
                                        <form method="POST" action="/kampus-absensi/public/process_hapus_mahasiswa.php" style="display:inline;" onsubmit="return confirm('Yakin hapus?')">
                                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="tambahModal" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <form method="POST" action="/kampus-absensi/public/process_tambah_mahasiswa.php">
                <div class="modal-header"><h5 class="modal-title">Tambah Mahasiswa</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label>NIM</label><input type="text" name="nip_nim" class="form-control" required></div>
                    <div class="mb-3"><label>Nama</label><input type="text" name="nama" class="form-control" required></div>
                    <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control" required></div>
                    <div class="mb-3"><label>Password</label><input type="password" name="password" class="form-control" required></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div></div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <form method="POST" action="/kampus-absensi/public/process_edit_mahasiswa.php">
                <div class="modal-header"><h5 class="modal-title">Edit Mahasiswa</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3"><label>NIM</label><input type="text" name="nip_nim" id="edit_nip_nim" class="form-control" required></div>
                    <div class="mb-3"><label>Nama</label><input type="text" name="nama" id="edit_nama" class="form-control" required></div>
                    <div class="mb-3"><label>Email</label><input type="email" name="email" id="edit_email" class="form-control" required></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Update</button></div>
            </form>
        </div></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editMahasiswa(id, nip, nama, email) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nip_nim').value = nip;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_email').value = email;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
    </script>
</body>
</html>