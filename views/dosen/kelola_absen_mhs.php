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

// Ambil semua pengajuan pending untuk dosen ini
$stmt = $conn->prepare("
    SELECT a.*, u.nama as nama_mahasiswa, u.nip_nim, m.nama_matkul, j.hari, j.jam_mulai, j.jam_selesai
    FROM absensi a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN jadwal j ON a.jadwal_id = j.id
    LEFT JOIN matkul m ON j.matkul_id = m.id
    WHERE j.dosen_id = ? AND a.status_verifikasi = 'Pending' AND u.role = 'mahasiswa'
    ORDER BY a.tanggal DESC, a.waktu_scan DESC
");
$stmt->execute([$dosen_id]);
$pending_list = $stmt->fetchAll();

$pending_count = count($pending_list);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Verifikasi Absen Mahasiswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%); min-height: 100vh; }
        .header-verif { background: linear-gradient(135deg, #064e3b 0%, #059669 100%); padding: 40px; color: white; margin-bottom: 30px; }
        .header-verif h1 { font-family: 'Playfair Display', serif; margin: 0; font-size: 2.2rem; }
        .header-verif p { opacity: 0.9; margin: 10px 0 0; }
        .btn-back-verif { background: rgba(255,255,255,0.2); color: white; border: 2px solid rgba(255,255,255,0.3); padding: 10px 20px; border-radius: 10px; text-decoration: none; transition: all 0.3s; display: inline-block; }
        .btn-back-verif:hover { background: rgba(255,255,255,0.3); color: white; }
        .badge-pending { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; padding: 8px 16px; border-radius: 20px; font-weight: 600; display: inline-block; margin-bottom: 20px; }
        .card-pengajuan { background: white; border-radius: 20px; padding: 30px; margin-bottom: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border-left: 5px solid #f59e0b; }
        .card-header-pengajuan { background: linear-gradient(135deg, #fef3c7, #fde68a); padding: 20px; border-radius: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .card-header-pengajuan h4 { margin: 0; color: #064e3b; font-weight: 600; }
        .badge-status-pengajuan { padding: 8px 16px; border-radius: 20px; font-weight: 600; font-size: 0.85rem; }
        .badge-izin { background: #dbeafe; color: #1e40af; }
        .badge-sakit { background: #fef3c7; color: #92400e; }
        .badge-terlambat { background: #fee2e2; color: #991b1b; }
        .info-row { display: flex; margin-bottom: 12px; }
        .info-label { font-weight: 600; color: #064e3b; min-width: 150px; display: flex; align-items: center; }
        .info-label i { margin-right: 8px; width: 20px; }
        .info-value { color: #6b7280; flex: 1; }
        .form-verif { background: #f9fafb; padding: 20px; border-radius: 12px; margin-top: 20px; border: 1px solid #e5e7eb; }
        .form-verif label { font-weight: 600; color: #064e3b; margin-bottom: 8px; }
        .form-verif select, .form-verif input { border: 2px solid #e5e7eb; border-radius: 8px; padding: 10px 15px; }
        .form-verif select:focus, .form-verif input:focus { border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1); }
        .btn-verif { background: linear-gradient(135deg, #059669, #064e3b); color: white; border: none; padding: 12px 30px; border-radius: 10px; font-weight: 600; transition: all 0.3s; cursor: pointer; }
        .btn-verif:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(5, 150, 105, 0.4); color: white; }
        .empty-state { text-align: center; padding: 60px 20px; color: #9ca3af; }
        .empty-state i { font-size: 4rem; margin-bottom: 20px; opacity: 0.5; }
        .hint-text { font-size: 0.8rem; color: #6b7280; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="header-verif">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-tasks me-3"></i>Verifikasi Absen Mahasiswa</h1>
                    <p>Review dan setujui pengajuan izin/sakit/terlambat</p>
                </div>
                <a href="/kampus-absensi/public/index.php?page=dosen_dashboard" class="btn-back-verif">
                    <i class="fas fa-arrow-left me-2"></i>Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <span class="badge-pending"><i class="fas fa-clock me-2"></i><?= $pending_count ?> Pengajuan Pending</span>

        <?php if($pending_count > 0): ?>
            <?php foreach($pending_list as $p): ?>
            <div class="card-pengajuan">
                <div class="card-header-pengajuan">
                    <div>
                        <h4><i class="fas fa-user-graduate me-2"></i><?= htmlspecialchars($p['nama_mahasiswa']) ?></h4>
                        <small class="text-muted">NIM: <?= htmlspecialchars($p['nip_nim']) ?></small>
                    </div>
                    <span class="badge-status-pengajuan badge-<?= strtolower(str_replace(' ', '_', $p['status_sistem'])) ?>">
                        <?= ucfirst(str_replace('_', ' ', $p['status_sistem'])) ?>
                    </span>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="info-row"><div class="info-label"><i class="fas fa-calendar"></i>Tanggal</div><div class="info-value">: <?= date('d F Y', strtotime($p['tanggal'])) ?></div></div>
                        <div class="info-row"><div class="info-label"><i class="fas fa-book"></i>Mata Kuliah</div><div class="info-value">: <?= htmlspecialchars($p['nama_matkul']) ?></div></div>
                        <div class="info-row"><div class="info-label"><i class="fas fa-clock"></i>Waktu</div><div class="info-value">: <?= date('H:i', strtotime($p['waktu_scan'])) ?></div></div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row"><div class="info-label"><i class="fas fa-comment"></i>Alasan</div><div class="info-value">: <?= htmlspecialchars($p['alasan'] ?? '-') ?></div></div>
                        <?php if(!empty($p['bukti_path'])): ?>
                        <div class="info-row">
                            <div class="info-label"><i class="fas fa-paperclip"></i>Bukti</div>
                            <div class="info-value">: <a href="/kampus-absensi/public/<?= htmlspecialchars($p['bukti_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye me-1"></i>Lihat Bukti</a></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- FORM VERIFIKASI -->
                <div class="form-verif">
                    <form method="POST" action="/kampus-absensi/public/process_verifikasi_absen.php">
                        <input type="hidden" name="absensi_id" value="<?= $p['id'] ?>">
                        <input type="hidden" name="original_status" value="<?= htmlspecialchars($p['status_sistem']) ?>">
                        
                        <div class="row align-items-end">
                            <div class="col-md-4">
                                <label for="status_verif_<?= $p['id'] ?>"><i class="fas fa-check-circle me-1"></i>Keputusan</label>
                                <select name="status_verifikasi" id="status_verif_<?= $p['id'] ?>" class="form-select" onchange="togglePersentase(<?= $p['id'] ?>)" required>
                                    <option value="Disetujui">✅ Setujui (100%)</option>
                                    <option value="Ditolak">❌ Tolak (Atur Persentase)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="persentase_<?= $p['id'] ?>"><i class="fas fa-percentage me-1"></i>Persentase Kehadiran (%)</label>
                                <input type="number" name="persentase" id="persentase_<?= $p['id'] ?>" class="form-control" min="0" max="100" value="100" required>
                                <div class="hint-text" id="hint_<?= $p['id'] ?>">Setujui = 100%, Tolak = isi 0-100 (0% = Alpa)</div>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-verif w-100">
                                    <i class="fas fa-check me-2"></i>Proses Verifikasi
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-check-circle"></i>
                <h4>Tidak Ada Pengajuan Pending</h4>
                <p>Semua pengajuan absensi mahasiswa sudah diverifikasi.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePersentase(id) {
            const status = document.getElementById('status_verif_' + id).value;
            const input = document.getElementById('persentase_' + id);
            const hint = document.getElementById('hint_' + id);
            
            if (status === 'Disetujui') {
                input.value = 100;
                input.readOnly = true;
                input.style.background = '#f3f4f6';
                hint.textContent = 'Disetujui otomatis 100%';
                hint.style.color = '#059669';
            } else {
                input.value = 0;
                input.readOnly = false;
                input.style.background = '#fff';
                input.focus();
                hint.textContent = 'Isi 0-100 (0 = Tidak Hadir/Alpa)';
                hint.style.color = '#ef4444';
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            <?php foreach($pending_list as $p): ?>
            togglePersentase(<?= $p['id'] ?>);
            <?php endforeach; ?>
        });
    </script>
</body>
</html>