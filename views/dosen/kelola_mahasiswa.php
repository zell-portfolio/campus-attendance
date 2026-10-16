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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="/kampus-absensi/public/index.php?page=admin_dashboard">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
            <span class="navbar-text text-white">Kelola Mahasiswa</span>
        </div>
    </nav>

    <div class="container">
        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success">✅ Data berhasil disimpan!</div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-user-graduate"></i> Daftar Mahasiswa</h5>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahModal">
                    <i class="fas fa-plus"></i> Tambah Mahasiswa
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>NIM</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Foto Wajah</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($mahasiswa as $m): ?>
                            <tr>
                                <td><?= htmlspecialchars($m['nip_nim']) ?></td>
                                <td><?= htmlspecialchars($m['nama']) ?></td>
                                <td><?= htmlspecialchars($m['email']) ?></td>
                                <td>
                                    <?php if($m['foto_wajah_path']): ?>
                                        <img src="/kampus-absensi/public/<?= htmlspecialchars($m['foto_wajah_path']) ?>" 
                                             alt="Foto" style="width: 50px; height: 50px; object-fit: cover; border-radius: 50%;">
                                        <span class="badge bg-success">✓</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">✗ Belum</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" action="/kampus-absensi/public/process_hapus_mahasiswa.php" 
                                          style="display:inline;" onsubmit="return confirm('Yakin hapus?')">
                                        <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                        <button class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
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

    <!-- Modal Tambah -->
    <div class="modal fade" id="tambahModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="/kampus-absensi/public/process_tambah_mahasiswa.php">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Mahasiswa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>NIM</label>
                            <input type="text" name="nip_nim" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>