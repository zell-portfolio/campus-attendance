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
$hari_indo = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
$hari_ini = $hari_indo[date('l')];
$waktu_sekarang = date('H:i:s');
$tanggal_sekarang = date('Y-m-d');

// Ambil semua jadwal dosen
$stmt = $conn->prepare("SELECT j.*, m.nama_matkul FROM jadwal j LEFT JOIN matkul m ON j.matkul_id = m.id WHERE j.dosen_id = ? ORDER BY FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'), j.jam_mulai");
$stmt->execute([$dosen_id]);
$all_jadwal = $stmt->fetchAll();

// Filter hanya jadwal yang BUKA dan BELUM diabsen hari ini
$jadwal_buka = [];
$sudah_absen_hari_ini = false;

foreach ($all_jadwal as $j) {
    // Cek apakah sudah absen untuk jadwal ini hari ini
    $stmt_cek = $conn->prepare("SELECT id FROM absensi WHERE user_id = ? AND jadwal_id = ? AND tanggal = ?");
    $stmt_cek->execute([$dosen_id, $j['id'], $tanggal_sekarang]);
    $sudah_absen = $stmt_cek->fetch();

    if ($sudah_absen) {
        $sudah_absen_hari_ini = true;
    }

    // Cek status buka/tutup
    $is_open = false;
    if ($j['manual_override'] == 1 && $j['is_open'] == 1) {
        $is_open = true; // Admin force buka
    } elseif ($j['hari'] === $hari_ini && $waktu_sekarang >= $j['jam_mulai'] && $waktu_sekarang <= $j['jam_selesai']) {
        $is_open = true; // Sesuai hari dan jam
    }

    // Masukkan ke array jika BUKA dan BELUM diabsen
    if ($is_open && !$sudah_absen) {
        $jadwal_buka[] = $j;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Absen Dosen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { background: linear-gradient(135deg, #064e3b 0%, #059669 50%, #10b981 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .absen-card { background: white; border-radius: 25px; box-shadow: 0 30px 60px rgba(0,0,0,0.3); overflow: hidden; max-width: 600px; width: 100%; }
        .absen-header { background: linear-gradient(135deg, #064e3b, #059669); padding: 40px; color: white; text-align: center; }
        .absen-header h2 { font-family: 'Playfair Display', serif; margin: 0; font-size: 2rem; }
        .absen-body { padding: 40px; }
        .form-label { font-weight: 600; color: #064e3b; }
        .form-control, .form-select { border: 2px solid #e5e7eb; border-radius: 12px; padding: 12px 15px; }
        .form-control:focus, .form-select:focus { border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1); }
        .btn-submit { background: linear-gradient(135deg, #059669, #064e3b); border: none; padding: 15px; border-radius: 12px; font-weight: 600; font-size: 1.1rem; color: white; transition: all 0.3s; }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(5, 150, 105, 0.4); color: white; }
        .btn-back { background: #f3f4f6; color: #064e3b; border: none; padding: 12px 25px; border-radius: 12px; font-weight: 500; display: inline-block; text-decoration: none; transition: all 0.3s; }
        .btn-back:hover { background: #e5e7eb; color: #064e3b; }
        .info-box { background: linear-gradient(135deg, #fef3c7, #fde68a); border-left: 4px solid #d4af37; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .closed-box { background: linear-gradient(135deg, #fee2e2, #fecaca); border-left: 4px solid #ef4444; padding: 20px; border-radius: 10px; text-align: center; }
        .closed-box i { font-size: 3rem; color: #ef4444; margin-bottom: 15px; }
        .closed-box h4 { color: #991b1b; font-weight: 600; margin-bottom: 10px; }
        .closed-box p { color: #7f1d1d; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="absen-card">
        <div class="absen-header">
            <i class="fas fa-user-check fa-3x mb-3" style="color: #d4af37;"></i>
            <h2>Absensi Dosen</h2>
            <p>Catat kehadiran Anda hari ini</p>
        </div>
        <div class="absen-body">
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Absensi berhasil dicatat!</div>
            <?php endif; ?>

            <div class="info-box">
                <i class="fas fa-info-circle me-2" style="color: #d4af37;"></i>
                <strong>Hari ini:</strong> <?= $hari_ini ?>, <?= date('d F Y') ?> | <?= date('H:i') ?> WITA
            </div>

            <?php if (count($jadwal_buka) > 0): ?>
                <!-- FORM ABSEN (Hanya muncul jika ada jadwal yang BUKA) -->
                <form method="POST" action="/kampus-absensi/public/process_absen_dosen.php">
                    <div class="mb-4">
                        <label class="form-label"><i class="fas fa-book me-2"></i>Pilih Mata Kuliah</label>
                        <select name="jadwal_id" class="form-select" required>
                            <option value="">-- Pilih Jadwal yang Aktif --</option>
                            <?php foreach($jadwal_buka as $j): ?>
                                <option value="<?= $j['id'] ?>" <?= (isset($_GET['jadwal_id']) && $_GET['jadwal_id'] == $j['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($j['nama_matkul']) ?> - <?= $j['hari'] ?> (<?= date('H:i', strtotime($j['jam_mulai'])) ?> - <?= date('H:i', strtotime($j['jam_selesai'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text text-muted mt-2">
                            <i class="fas fa-lightbulb me-1"></i> Hanya jadwal yang sedang aktif (sesuai jam atau dibuka admin) yang ditampilkan.
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label"><i class="fas fa-tag me-2"></i>Status Kehadiran</label>
                        <select name="status" class="form-select" required>
                            <option value="Hadir">✅ Hadir</option>
                            <option value="Izin">📝 Izin</option>
                            <option value="Sakit">🏥 Sakit</option>
                            <option value="Terlambat">⏰ Terlambat</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-submit w-100 mb-3">
                        <i class="fas fa-paper-plane me-2"></i>Kirim Absensi
                    </button>
                </form>
            <?php else: ?>
                <!-- PESAN TUTUP (Muncul jika tidak ada jadwal yang bisa diabsen) -->
                <div class="closed-box">
                    <i class="fas fa-lock"></i>
                    <h4>Absensi Ditutup</h4>
                    <p>
                        <?php if ($sudah_absen_hari_ini): ?>
                            Anda sudah melakukan absensi untuk jadwal hari ini.
                        <?php else: ?>
                            Tidak ada jadwal kuliah yang sedang aktif saat ini, atau absensi sedang ditutup oleh admin.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>

            <a href="/kampus-absensi/public/index.php?page=dosen_dashboard" class="btn-back w-100 text-center mt-3">
                <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
            </a>
        </div>
    </div>
</body>
</html>