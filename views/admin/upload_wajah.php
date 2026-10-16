<?php
require_once __DIR__ . "/../../config/database.php";
$database = new Database();
$conn = $database->getConnection();
$users = $conn->query("SELECT id, nama, nip_nim, role, foto_wajah_path, face_descriptor FROM users WHERE role IN ('mahasiswa', 'dosen') ORDER BY role, nama ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Upload Foto Wajah</title>
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
        .foto-preview { width: 60px; height: 60px; object-fit: cover; border-radius: 50%; border: 2px solid #ddd; }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .status-complete { background: #d1fae5; color: #065f46; }
        .status-partial { background: #fef3c7; color: #92400e; }
        .status-none { background: #f3f4f6; color: #6b7280; }
        .btn-upload { background: #9ca3af; color: white; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; transition: all 0.3s; cursor: not-allowed; }
        .btn-upload.ready { background: #10b981; cursor: pointer; }
        .btn-upload.ready:hover { background: #059669; }
        .btn-upload.processing { background: #3b82f6; animation: pulse 1s infinite; cursor: wait; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
        .model-status { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .model-status.ready { background: #d1fae5; border-left-color: #10b981; }
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
            <li class="nav-item"><a class="nav-link" href="/kampus-absensi/public/index.php?page=admin_kelola_jadwal"><i class="fas fa-fw fa-calendar-alt"></i> Jadwal Kuliah</a></li>

            <li class="nav-item"><a class="nav-link" href="/kampus-absensi/public/index.php?page=admin_rekap_absensi"><i class="fas fa-fw fa-clipboard-list"></i> Rekap Absensi</a></li>
            <li class="nav-item"><a class="nav-link active" href="/kampus-absensi/public/index.php?page=admin_upload_wajah"><i class="fas fa-fw fa-camera"></i> Upload Wajah</a></li>
            <li class="nav-item mt-5"><a class="nav-link" href="/kampus-absensi/public/logout.php"><i class="fas fa-fw fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="topbar">
            <h4 class="mb-0" style="color: #5a5c69;">Upload Foto Wajah</h4>
            <div class="d-flex align-items-center">
                <span class="me-3" style="color: #5a5c69;"><i class="fas fa-user-circle me-2"></i><?= htmlspecialchars($_SESSION['nama']) ?></span>
                <a href="/kampus-absensi/public/logout.php" class="btn btn-sm btn-outline-danger"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
        <div class="content-wrapper">
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i>Foto dan descriptor berhasil disimpan otomatis!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle me-2"></i>Error: <?= htmlspecialchars($_GET['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            
            <div id="modelStatus" class="model-status"><i class="fas fa-spinner fa-spin"></i><span>Memuat model AI...</span></div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-white"><h5 class="mb-0"><i class="fas fa-camera text-warning me-2"></i>Upload Foto Wajah</h5></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light"><tr><th>NIP/NIM</th><th>Nama</th><th>Role</th><th>Foto</th><th>Status</th><th>Aksi</th></tr></thead>
                            <tbody>
                                <?php foreach($users as $u): 
                                    $hasPhoto = !empty($u['foto_wajah_path']);
                                    $hasDesc = !empty($u['face_descriptor']);
                                    $statusClass = ($hasPhoto && $hasDesc) ? 'status-complete' : (($hasPhoto) ? 'status-partial' : 'status-none');
                                    $statusText = ($hasPhoto && $hasDesc) ? '✓ Lengkap' : (($hasPhoto) ? '⚠ Foto saja' : 'Belum ada');
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($u['nip_nim']) ?></td>
                                    <td><?= htmlspecialchars($u['nama']) ?></td>
                                    <td><span class="badge bg-<?= $u['role'] === 'dosen' ? 'success' : 'primary' ?>"><?= ucfirst($u['role']) ?></span></td>
                                    <td><?php if($hasPhoto): ?><img src="/kampus-absensi/public/<?= htmlspecialchars($u['foto_wajah_path']) ?>" class="foto-preview" onerror="this.style.opacity='0.3'"><?php else: ?><span class="text-muted">-</span><?php endif; ?></td>
                                    <td><span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                                    <td>
                                        <form method="POST" action="/kampus-absensi/public/process_upload_wajah.php" enctype="multipart/form-data" class="upload-form" data-user-id="<?= $u['id'] ?>">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="face_descriptor" class="face-descriptor-input">
                                            <input type="file" name="foto_wajah" class="form-control form-control-sm foto-input mb-2" accept="image/jpeg,image/png,image/jpg" required>
                                            <button type="submit" class="btn btn-upload btn-submit-upload" disabled>
                                                <i class="fas fa-lock"></i> Pilih Foto Dulu
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script>
        let modelsLoaded = false;
        const modelStatus = document.getElementById('modelStatus');

        async function loadModels() {
            try {
                const MODEL_URL = '/kampus-absensi/public/assets/models';
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                    faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                    faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
                ]);
                modelsLoaded = true;
                modelStatus.className = 'model-status ready';
                modelStatus.innerHTML = '<i class="fas fa-check-circle"></i><span>AI Siap! Silakan pilih foto.</span>';
            } catch (e) {
                modelStatus.innerHTML = '<i class="fas fa-exclamation-triangle"></i><span>Gagal memuat AI. Refresh halaman.</span>';
            }
        }
        window.addEventListener('load', loadModels);

        document.querySelectorAll('.upload-form').forEach(form => {
            const fileInput = form.querySelector('.foto-input');
            const descriptorInput = form.querySelector('.face-descriptor-input');
            const submitBtn = form.querySelector('.btn-submit-upload');

            fileInput.addEventListener('change', async function() {
                const file = this.files[0];
                if (!file) return;
                if (!modelsLoaded) { alert('Tunggu AI memuat terlebih dahulu.'); this.value = ''; return; }

                submitBtn.disabled = true;
                submitBtn.className = 'btn btn-upload processing';
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';

                try {
                    const img = await faceapi.fetchImage(URL.createObjectURL(file));
                    const detection = await faceapi.detectSingleFace(img, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks().withFaceDescriptor();

                    if (!detection) {
                        alert('❌ Tidak ada wajah terdeteksi! Gunakan foto yang jelas.');
                        this.value = '';
                        submitBtn.disabled = true;
                        submitBtn.className = 'btn btn-upload';
                        submitBtn.innerHTML = '<i class="fas fa-lock"></i> Pilih Foto Dulu';
                        return;
                    }

                    descriptorInput.value = JSON.stringify(Array.from(detection.descriptor));
                    submitBtn.disabled = false;
                    submitBtn.className = 'btn btn-upload ready';
                    submitBtn.innerHTML = '<i class="fas fa-check"></i> Siap Upload';
                } catch (e) {
                    alert('Error: ' + e.message);
                    this.value = '';
                    submitBtn.disabled = true;
                    submitBtn.className = 'btn btn-upload';
                    submitBtn.innerHTML = '<i class="fas fa-lock"></i> Pilih Foto Dulu';
                }
            });
        });
    </script>
</body>
</html>