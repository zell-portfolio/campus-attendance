<?php
require_once __DIR__ . "/../../config/database.php";
$database = new Database();
$conn = $database->getConnection();

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT j.*, m.nama_matkul, u.nama as nama_dosen FROM jadwal j LEFT JOIN matkul m ON j.matkul_id = m.id LEFT JOIN users u ON j.dosen_id = u.id ORDER BY FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'), j.jam_mulai");
$stmt->execute();
$jadwal_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajukan Izin / Sakit</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body {
            background: linear-gradient(135deg, #7c3aed 0%, #db2777 50%, #f43f5e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .form-card {
            background: white;
            border-radius: 25px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 700px;
            width: 100%;
        }
        .form-header {
            background: linear-gradient(135deg, #f59e0b, #ef4444);
            padding: 30px;
            color: white;
            text-align: center;
        }
        .form-header h2 { font-family: 'Space Grotesk', sans-serif; margin: 0; font-size: 2rem; }
        .form-header p { opacity: 0.95; margin: 10px 0 0; }
        .form-body { padding: 35px; }
        .form-label { font-weight: 600; color: #1f2937; margin-bottom: 8px; }
        .form-control, .form-select, .form-textarea {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        .form-control:focus, .form-select:focus, .form-textarea:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }
        .btn-submit-form {
            background: linear-gradient(135deg, #f59e0b, #ef4444);
            border: none;
            padding: 15px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            color: white;
            transition: all 0.3s;
        }
        .btn-submit-form:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(239, 68, 68, 0.4);
            color: white;
        }
        .btn-back-form {
            background: #f3f4f6;
            color: #7c3aed;
            border: none;
            padding: 12px 25px;
            border-radius: 12px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-back-form:hover { background: #e5e7eb; color: #7c3aed; }
        .info-box-form {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border-left: 4px solid #f59e0b;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .file-preview {
            margin-top: 10px;
            padding: 10px;
            border-radius: 8px;
            background: #f9fafb;
            border: 2px dashed #e5e7eb;
            display: none;
        }
        .file-preview.show { display: block; }
        .file-preview i { font-size: 2rem; color: #f59e0b; margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="form-card">
        <div class="form-header">
            <i class="fas fa-file-alt fa-3x mb-3" style="color: #fde047;"></i>
            <h2>Ajukan Izin / Sakit</h2>
            <p>Isi form untuk mengajukan izin atau sakit</p>
        </div>
        <div class="form-body">
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Pengajuan berhasil! Menunggu verifikasi dosen.</div>
            <?php endif; ?>
            <?php if(isset($_GET['already'])): ?>
                <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Anda sudah absen untuk jadwal ini hari ini.</div>
            <?php endif; ?>
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php
                    $error = $_GET['error'];
                    if ($error === 'upload_failed') echo 'Upload file gagal. Pastikan file tidak terlalu besar.';
                    elseif ($error === 'invalid_type') echo 'Tipe file tidak valid.';
                    elseif ($error === 'missing_data') echo 'Semua field harus diisi.';
                    else echo 'Terjadi kesalahan. Silakan coba lagi.';
                    ?>
                </div>
            <?php endif; ?>

            <div class="info-box-form">
                <i class="fas fa-info-circle me-2" style="color: #f59e0b;"></i>
                <strong>Hari ini:</strong> <?= date('l, d F Y') ?> | <?= date('H:i') ?> WIB
            </div>

            <form method="POST" action="/kampus-absensi/public/process_izin_sakit.php" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-tag me-2"></i>Status</label>
                    <select name="status" class="form-select" id="statusSelect" required onchange="toggleBukti()">
                        <option value="">-- Pilih Status --</option>
                        <option value="Izin"> Izin</option>
                        <option value="Sakit">🏥 Sakit</option>
                        <option value="Terlambat"> Terlambat (Dengan Alasan)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-book me-2"></i>Pilih Mata Kuliah</label>
                    <select name="jadwal_id" class="form-select" required>
                        <option value="">-- Pilih Jadwal --</option>
                        <?php foreach($jadwal_list as $j): ?>
                            <option value="<?= $j['id'] ?>">
                                <?= htmlspecialchars($j['nama_matkul']) ?> - <?= $j['hari'] ?> <?= date('H:i', strtotime($j['jam_mulai'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label"><i class="fas fa-comment me-2"></i>Alasan</label>
                    <textarea name="alasan" class="form-control form-textarea" rows="4" required placeholder="Jelaskan alasan Anda..."></textarea>
                </div>

                <div class="mb-3" id="buktiSection">
                    <label class="form-label"><i class="fas fa-paperclip me-2"></i>Upload Bukti</label>
                    <div class="alert alert-info" id="infoBukti">
                        <i class="fas fa-info-circle"></i> Pilih status untuk melihat jenis file yang diizinkan
                    </div>
                    <input type="file" name="bukti" class="form-control" id="fileBukti" required onchange="previewFile()">
                    <small class="text-muted" id="fileHelp"></small>
                    
                    <div id="filePreview" class="file-preview text-center">
                        <i class="fas fa-file"></i>
                        <div id="fileName"></div>
                    </div>
                </div>

                <button type="submit" class="btn btn-submit-form w-100 mb-3">
                    <i class="fas fa-paper-plane me-2"></i>Kirim Pengajuan
                </button>
                <a href="/kampus-absensi/public/index.php?page=mahasiswa_dashboard" class="btn-back-form w-100 text-center">
                    <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                </a>
            </form>
        </div>
    </div>

    <script>
        function toggleBukti() {
            const status = document.getElementById('statusSelect').value;
            const infoBukti = document.getElementById('infoBukti');
            const fileBukti = document.getElementById('fileBukti');
            const fileHelp = document.getElementById('fileHelp');

            if (status === 'Sakit') {
                infoBukti.innerHTML = '<i class="fas fa-file-pdf"></i> Upload surat keterangan dokter (PDF)';
                infoBukti.className = 'alert alert-info';
                fileBukti.accept = '.pdf,application/pdf';
                fileHelp.textContent = 'Format: PDF (Max 5MB)';
            } else if (status === 'Izin' || status === 'Terlambat') {
                infoBukti.innerHTML = '<i class="fas fa-video"></i> Upload video klarifikasi dengan suara';
                infoBukti.className = 'alert alert-warning';
                fileBukti.accept = 'video/*';
                fileHelp.textContent = 'Format: MP4/WebM (Max 50MB) - Pastikan ada suara!';
            } else {
                infoBukti.innerHTML = '<i class="fas fa-info-circle"></i> Pilih status untuk melihat jenis file';
                infoBukti.className = 'alert alert-info';
                fileBukti.accept = '';
                fileHelp.textContent = '';
            }
        }

        function previewFile() {
            const fileInput = document.getElementById('fileBukti');
            const filePreview = document.getElementById('filePreview');
            const fileName = document.getElementById('fileName');
            
            if (fileInput.files && fileInput.files[0]) {
                const file = fileInput.files[0];
                fileName.textContent = file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
                filePreview.classList.add('show');
            }
        }
    </script>
</body>
</html>