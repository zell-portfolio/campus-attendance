<?php
date_default_timezone_set('Asia/Makassar');


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: /kampus-absensi/public/index.php?page=login");
    exit();
}

require_once __DIR__ . "/../../config/database.php";
$database = new Database();
$conn = $database->getConnection();

$user_id = $_SESSION['user_id'];

// Hari ini dalam bahasa Indonesia
$hari_indo = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
$hari_ini = $hari_indo[date('l')];
$waktu_sekarang = date('H:i:s');

$hari_list = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$jadwal_per_hari = [];

foreach ($hari_list as $hari) {
    $stmt = $conn->prepare("SELECT j.*, m.nama_matkul, m.sks, u.nama as nama_dosen FROM jadwal j LEFT JOIN matkul m ON j.matkul_id = m.id LEFT JOIN users u ON j.dosen_id = u.id WHERE j.hari = ? ORDER BY j.jam_mulai ASC");
    $stmt->execute([$hari]);
    $jadwal_per_hari[$hari] = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jadwal Mata Kuliah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { background: linear-gradient(135deg, #faf5ff 0%, #fdf2f8 100%); min-height: 100vh; }
        
        .page-header-mhs {
            background: linear-gradient(135deg, #7c3aed 0%, #db2777 100%);
            padding: 40px;
            color: white;
            margin-bottom: 30px;
        }
        .page-header-mhs h1 { font-family: 'Space Grotesk', sans-serif; margin: 0; font-size: 2.2rem; }
        .page-header-mhs p { opacity: 0.95; margin: 10px 0 0; }
        
        .back-btn-mhs {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid rgba(255,255,255,0.3);
            padding: 10px 20px;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
        }
        .back-btn-mhs:hover { background: rgba(255,255,255,0.3); color: white; }
        
        .hari-card-mhs {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            overflow: hidden;
        }
        .hari-header-mhs {
            background: linear-gradient(135deg, #7c3aed, #db2777);
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .hari-header-mhs h4 { margin: 0; font-family: 'Space Grotesk', sans-serif; }
        .hari-body-mhs { padding: 20px 30px; }
        
        .jadwal-item-mhs {
            display: flex;
            align-items: center;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #faf5ff, #fdf2f8);
            border-left: 5px solid #7c3aed;
            transition: all 0.3s;
        }
        .jadwal-item-mhs:hover { transform: translateX(10px); box-shadow: 0 5px 15px rgba(124, 58, 237, 0.2); }
        .jadwal-time-mhs {
            background: linear-gradient(135deg, #7c3aed, #db2777);
            color: white;
            padding: 15px 20px;
            border-radius: 12px;
            font-weight: 600;
            margin-right: 25px;
            min-width: 130px;
            text-align: center;
        }
        .jadwal-info-mhs { flex-grow: 1; }
        .jadwal-info-mhs h5 { color: #1f2937; font-weight: 600; margin-bottom: 5px; }
        .jadwal-info-mhs p { color: #6b7280; margin: 0; font-size: 0.9rem; }

        /* 2 JENIS TOMBOL SAJA */
        .btn-absen {
            border-radius: 50px; 
            padding: 10px 25px; 
            font-weight: 600; 
            text-decoration: none; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px;
            border: none;
            transition: all 0.3s;
            min-width: 120px;
            justify-content: center;
        }
        .btn-absen-buka {
            background: linear-gradient(135deg, #7c3aed, #db2777); 
            color: white; 
            cursor: pointer;
        }
        .btn-absen-buka:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 5px 15px rgba(124, 58, 237, 0.4); 
            color: white;
        }
        .btn-absen-tutup {
            background: #e5e7eb; 
            color: #9ca3af; 
            cursor: not-allowed;
        }
        
        /* Highlight hari ini */
        .hari-card-mhs.hari-ini {
            box-shadow: 0 10px 40px rgba(124, 58, 237, 0.3);
            border: 2px solid #7c3aed;
        }
        .hari-header-mhs.hari-ini {
            background: linear-gradient(135deg, #db2777, #f43f5e);
        }
        .badge-hari-ini {
            background: white;
            color: #db2777;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="page-header-mhs">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="fas fa-calendar-alt me-3"></i>Jadwal Mata Kuliah</h1>
                    <p>Hari ini: <strong><?= $hari_ini ?>, <?= date('d F Y') ?></strong> | <?= date('H:i') ?> WITA</p>
                </div>
                <a href="/kampus-absensi/public/index.php?page=mahasiswa_dashboard" class="back-btn-mhs">
                    <i class="fas fa-arrow-left me-2"></i>Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php foreach($hari_list as $hari): ?>
            <?php if(count($jadwal_per_hari[$hari]) > 0): 
                $is_hari_ini = ($hari === $hari_ini);
            ?>
            <div class="hari-card-mhs <?= $is_hari_ini ? 'hari-ini' : '' ?>">
                <div class="hari-header-mhs <?= $is_hari_ini ? 'hari-ini' : '' ?>">
                    <h4>
                        <i class="fas fa-calendar-day me-2"></i><?= $hari ?>
                        <?= $is_hari_ini ? '<span class="badge-hari-ini ms-2">HARI INI</span>' : '' ?>
                    </h4>
                    <span class="badge bg-light text-dark"><?= count($jadwal_per_hari[$hari]) ?> Kelas</span>
                </div>
                <div class="hari-body-mhs">
                    <?php foreach($jadwal_per_hari[$hari] as $j): 
                        
                        // --- LOGIC 2 STATUS SAJA: BUKA atau TUTUP ---
                        $status_tombol = 'tutup'; // Default
                        
                        // Cek apakah sudah absen hari ini
                        $stmt_absen = $conn->prepare("SELECT id FROM absensi WHERE user_id = ? AND jadwal_id = ? AND tanggal = ?");
                        $stmt_absen->execute([$user_id, $j['id'], date('Y-m-d')]);
                        $sudah_absen = $stmt_absen->fetch();

                        if ($sudah_absen) {
                            // Sudah absen → tetap "Tutup" (tidak bisa absen lagi)
                            $status_tombol = 'tutup';
                        } elseif ($j['manual_override'] == 1 && $j['is_open'] == 1) {
                            // Admin force buka → BUKA (bisa di luar jam/hari)
                            $status_tombol = 'buka';
                        } elseif ($hari === $hari_ini && $waktu_sekarang >= $j['jam_mulai'] && $waktu_sekarang <= $j['jam_selesai']) {
                            // Hari ini DAN dalam rentang jam → BUKA
                            $status_tombol = 'buka';
                        } else {
                            // Selain itu → TUTUP
                            $status_tombol = 'tutup';
                        }
                    ?>
                    <div class="jadwal-item-mhs">
                        <div class="jadwal-time-mhs">
                            <div style="font-size: 0.75rem; opacity: 0.8;">MULAI</div>
                            <div style="font-size: 1.1rem;"><?= date('H:i', strtotime($j['jam_mulai'])) ?></div>
                            <div style="font-size: 0.75rem; opacity: 0.8; margin-top: 5px;">SELESAI</div>
                            <div style="font-size: 1.1rem;"><?= date('H:i', strtotime($j['jam_selesai'])) ?></div>
                        </div>
                        <div class="jadwal-info-mhs">
                            <h5><?= htmlspecialchars($j['nama_matkul']) ?></h5>
                            <p>
                                <i class="fas fa-book me-1"></i> <?= $j['sks'] ?> SKS
                                <span class="mx-2">•</span>
                                <i class="fas fa-user-tie me-1"></i> <?= htmlspecialchars($j['nama_dosen']) ?>
                            </p>
                        </div>
                        
                        <!-- TOMBOL: CUMA 2 STATUS -->
                        <div>
                            <?php if ($status_tombol === 'buka'): ?>
                                <a href="/kampus-absensi/public/index.php?page=mahasiswa_absen_hadir&jadwal_id=<?= $j['id'] ?>" class="btn-absen btn-absen-buka">
                                    <i class="fas fa-check-circle"></i> Absen
                                </a>
                            <?php else: ?>
                                <button class="btn-absen btn-absen-tutup" disabled>
                                    <i class="fas fa-lock"></i> Tutup
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
        
        <?php if(array_sum(array_map('count', $jadwal_per_hari)) == 0): ?>
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">Tidak Ada Jadwal</h4>
                <p class="text-muted">Belum ada jadwal kuliah yang terdaftar untuk minggu ini.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>