<?php
session_start();
require_once __DIR__ . "/../config/database.php";

// Cek admin
if ($_SESSION['role'] !== 'admin') {
    header("Location: /kampus-absensi/public/index.php?page=login");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Ambil semua user (mahasiswa + dosen)
$mahasiswa = $conn->query("SELECT id, nama, nip_nim FROM users WHERE role='mahasiswa' ORDER BY nama ASC")->fetchAll();
$dosen = $conn->query("SELECT id, nama, nip_nim FROM users WHERE role='dosen' ORDER BY nama ASC")->fetchAll();

// Handle upload
$hasil_upload = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_upload'])) {
    $uploadDir = __DIR__ . "/uploads/faces/";
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $files = $_FILES['foto_upload'];
    $count = count($files['name']);
    
    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] !== 0) continue;
        
        $fileName = $files['name'][$i];
        $tmpName = $files['tmp_name'][$i];
        
        // Cari user berdasarkan NIM/NIP di nama file
    
        preg_match('/^(MHS\d{3}|[A-Z]?\d{2}SI\d{3}|D\d{3})/i', $fileName, $matches);
        $kode = $matches[1] ?? '';
        
        if (empty($kode)) {
            $hasil_upload[] = ['file' => $fileName, 'status' => 'error', 'pesan' => 'NIM/NIP tidak ditemukan di nama file'];
            continue;
        }
        
        // Cari user dengan NIM/NIP ini
        $stmt = $conn->prepare("SELECT id, nama, role FROM users WHERE nip_nim = ?");
        $stmt->execute([$kode]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $hasil_upload[] = ['file' => $fileName, 'status' => 'error', 'pesan' => "User dengan kode $kode tidak ditemukan"];
            continue;
        }
        
        // Upload file
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = 'face_' . $user['id'] . '_' . time() . '.' . $ext;
        $targetPath = $uploadDir . $newFileName;
        
        if (move_uploaded_file($tmpName, $targetPath)) {
            $fotoPath = 'uploads/faces/' . $newFileName;
            
            // Update database
            $stmt = $conn->prepare("UPDATE users SET foto_wajah_path = ? WHERE id = ?");
            $stmt->execute([$fotoPath, $user['id']]);
            
            $roleLabel = $user['role'] === 'dosen' ? 'Dosen' : 'Mahasiswa';
            $hasil_upload[] = [
                'file' => $fileName,
                'status' => 'success',
                'pesan' => "Berhasil upload untuk {$roleLabel}: {$user['nama']} (ID: {$user['id']})",
                'user_id' => $user['id']
            ];
        } else {
            $hasil_upload[] = ['file' => $fileName, 'status' => 'error', 'pesan' => 'Gagal upload file'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Upload Massal Foto Wajah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fc; font-family: 'Segoe UI', sans-serif; }
        .container { max-width: 1100px; margin: 40px auto; }
        .card { border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .upload-zone {
            border: 3px dashed #4e73df;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            background: #f8f9fc;
            cursor: pointer;
            transition: all 0.3s;
        }
        .upload-zone:hover { background: #e8ecf8; border-color: #224abe; }
        .upload-zone.dragover { background: #d4daf0; }
        .hasil-item { padding: 10px; border-radius: 8px; margin-bottom: 8px; }
        .hasil-success { background: #d1fae5; color: #065f46; }
        .hasil-error { background: #fee2e2; color: #991b1b; }
        .user-list { max-height: 400px; overflow-y: auto; }
        .user-item { padding: 10px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
        .user-item.has-photo { background: #f0fdf4; }
        .user-item.no-photo { background: #fef2f2; }
        .badge-status { padding: 5px 10px; border-radius: 20px; font-size: 0.8rem; }
        .role-badge-mhs { background: #4e73df; color: white; padding: 3px 10px; border-radius: 15px; font-size: 0.75rem; }
        .role-badge-dsn { background: #1cc88a; color: white; padding: 3px 10px; border-radius: 15px; font-size: 0.75rem; }
        .section-title { 
            padding: 10px 15px; 
            border-radius: 10px; 
            margin: 20px 0 10px;
            font-weight: 600;
        }
        .section-mhs { background: #e0e7ff; color: #3730a3; }
        .section-dsn { background: #d1fae5; color: #065f46; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card p-4 mb-4">
            <h2 class="mb-3"><i class="fas fa-upload text-primary me-2"></i>Upload Massal Foto Wajah</h2>
            <p class="text-muted">Upload foto mahasiswa & dosen sekaligus. Nama file harus diawali NIM/NIP.</p>
            
            <div class="alert alert-warning">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Format nama file:</strong>
                <ul class="mb-0 mt-2">
                    <li>Mahasiswa: <code>MHS032 Nama Mahasiswa</code></li>
                    <li>Dosen: <code>Nama Dosen</code></li>
                </ul>
            </div>
            
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="upload-zone" id="uploadZone" onclick="document.getElementById('fileInput').click()">
                    <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                    <h5>Klik di sini atau drag & drop file foto</h5>
                    <p class="text-muted mb-0">Bisa pilih banyak file sekaligus (Ctrl+A atau Shift+Click)</p>
                    <input type="file" id="fileInput" name="foto_upload[]" multiple accept="image/*" style="display:none;" onchange="showSelectedFiles()">
                </div>
                
                <div id="selectedFiles" class="mt-3"></div>
                
                <button type="submit" class="btn btn-primary btn-lg w-100 mt-3" id="btnUpload" style="display:none;">
                    <i class="fas fa-upload me-2"></i>Upload Semua Foto
                </button>
            </form>
        </div>

        <?php if(!empty($hasil_upload)): ?>
        <div class="card p-4 mb-4">
            <h4 class="mb-3"><i class="fas fa-check-circle text-success me-2"></i>Hasil Upload</h4>
            <?php foreach($hasil_upload as $h): ?>
                <div class="hasil-item hasil-<?= $h['status'] ?>">
                    <i class="fas fa-<?= $h['status'] === 'success' ? 'check-circle' : 'times-circle' ?> me-2"></i>
                    <strong><?= htmlspecialchars($h['file']) ?></strong>: <?= htmlspecialchars($h['pesan']) ?>
                </div>
            <?php endforeach; ?>
            
            <?php 
            $successCount = count(array_filter($hasil_upload, fn($h) => $h['status'] === 'success'));
            if ($successCount > 0):
            ?>
            <div class="alert alert-info mt-3">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Langkah selanjutnya:</strong> Buka halaman 
                <a href="/kampus-absensi/public/batch_extract_descriptors.php" class="alert-link">Batch Extract Descriptors</a> 
                untuk mengekstrak face descriptor dari foto yang baru diupload.
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Daftar Dosen -->
        <div class="card p-4 mb-4">
            <h4 class="mb-3"><i class="fas fa-chalkboard-teacher text-success me-2"></i>Daftar Dosen (<?= count($dosen) ?>)</h4>
            <div class="user-list">
                <?php foreach($dosen as $d): 
                    $stmt = $conn->prepare("SELECT foto_wajah_path, face_descriptor FROM users WHERE id = ?");
                    $stmt->execute([$d['id']]);
                    $user = $stmt->fetch();
                    $hasPhoto = !empty($user['foto_wajah_path']);
                    $hasDesc = !empty($user['face_descriptor']);
                ?>
                <div class="user-item <?= $hasPhoto ? 'has-photo' : 'no-photo' ?>">
                    <div>
                        <strong><?= htmlspecialchars($d['nama']) ?></strong>
                        <span class="role-badge-dsn ms-2">Dosen</span>
                        <br><small class="text-muted"><?= htmlspecialchars($d['nip_nim']) ?></small>
                    </div>
                    <div>
                        <?php if($hasPhoto && $hasDesc): ?>
                            <span class="badge-status bg-success text-white">✓ Foto + Descriptor</span>
                        <?php elseif($hasPhoto): ?>
                            <span class="badge-status bg-warning text-dark">⚠ Foto saja</span>
                        <?php else: ?>
                            <span class="badge-status bg-secondary text-white">✗ Belum</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Daftar Mahasiswa -->
        <div class="card p-4">
            <h4 class="mb-3"><i class="fas fa-user-graduate text-primary me-2"></i>Daftar Mahasiswa (<?= count($mahasiswa) ?>)</h4>
            <div class="user-list">
                <?php foreach($mahasiswa as $m): 
                    $stmt = $conn->prepare("SELECT foto_wajah_path, face_descriptor FROM users WHERE id = ?");
                    $stmt->execute([$m['id']]);
                    $user = $stmt->fetch();
                    $hasPhoto = !empty($user['foto_wajah_path']);
                    $hasDesc = !empty($user['face_descriptor']);
                ?>
                <div class="user-item <?= $hasPhoto ? 'has-photo' : 'no-photo' ?>">
                    <div>
                        <strong><?= htmlspecialchars($m['nama']) ?></strong>
                        <span class="role-badge-mhs ms-2">Mahasiswa</span>
                        <br><small class="text-muted"><?= htmlspecialchars($m['nip_nim']) ?></small>
                    </div>
                    <div>
                        <?php if($hasPhoto && $hasDesc): ?>
                            <span class="badge-status bg-success text-white">✓ Foto + Descriptor</span>
                        <?php elseif($hasPhoto): ?>
                            <span class="badge-status bg-warning text-dark">⚠ Foto saja</span>
                        <?php else: ?>
                            <span class="badge-status bg-secondary text-white"> Belum</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        const uploadZone = document.getElementById('uploadZone');
        const fileInput = document.getElementById('fileInput');
        const selectedFiles = document.getElementById('selectedFiles');
        const btnUpload = document.getElementById('btnUpload');

        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });
        uploadZone.addEventListener('dragleave', () => {
            uploadZone.classList.remove('dragover');
        });
        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
            fileInput.files = e.dataTransfer.files;
            showSelectedFiles();
        });

        function showSelectedFiles() {
            const files = fileInput.files;
            if (files.length === 0) {
                selectedFiles.innerHTML = '';
                btnUpload.style.display = 'none';
                return;
            }
            
            let html = '<div class="alert alert-info"><i class="fas fa-file-image me-2"></i><strong>' + files.length + ' file dipilih:</strong></div><ul class="list-group">';
            for (let file of files) {
                html += '<li class="list-group-item"><i class="fas fa-image me-2"></i>' + file.name + ' <small class="text-muted">(' + (file.size/1024/1024).toFixed(2) + ' MB)</small></li>';
            }
            html += '</ul>';
            selectedFiles.innerHTML = html;
            btnUpload.style.display = 'block';
        }
    </script>
</body>
</html>