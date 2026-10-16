<?php
date_default_timezone_set('Asia/Makassar');
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mahasiswa') {
    header("Location: /kampus-absensi/public/index.php?page=login");
    exit();
}


require_once __DIR__ . "/../../config/database.php";
$database = new Database();
$conn = $database->getConnection();

$user_id = $_SESSION['user_id'];

// Cek foto & descriptor
$stmt = $conn->prepare("SELECT foto_wajah_path, face_descriptor FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || empty($user['foto_wajah_path']) || empty($user['face_descriptor'])) {
    header("Location: /kampus-absensi/public/index.php?page=mahasiswa_dashboard&no_photo=1");
    exit();
}

// CEK STATUS ABSEN: apakah ada jadwal yang BUKA saat ini?
$waktu_sekarang = date('H:i:s');
$hari_indo = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
$hari_ini = $hari_indo[date('l')];

// Query: cari jadwal hari ini yang BUKA (baik force buka ATAU dalam rentang jam)
$stmt = $conn->prepare("
    SELECT j.*, m.nama_matkul, u.nama as nama_dosen 
    FROM jadwal j 
    LEFT JOIN matkul m ON j.matkul_id = m.id 
    LEFT JOIN users u ON j.dosen_id = u.id 
    WHERE j.hari = ? AND (
        (j.manual_override = 1 AND j.is_open = 1) 
        OR 
        (j.manual_override = 0 AND j.is_open = 1 AND ? BETWEEN j.jam_mulai AND j.jam_selesai)
    )
    ORDER BY j.jam_mulai ASC
");
$stmt->execute([$hari_ini, $waktu_sekarang]);
$jadwal_buka = $stmt->fetchAll();

// Cek apakah sudah absen untuk jadwal yang buka
$jadwal_bisa_absen = [];
foreach ($jadwal_buka as $j) {
    $stmt = $conn->prepare("SELECT id FROM absensi WHERE user_id = ? AND jadwal_id = ? AND tanggal = ?");
    $stmt->execute([$user_id, $j['id'], date('Y-m-d')]);
    if (!$stmt->fetch()) {
        $jadwal_bisa_absen[] = $j;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Wajah - Absen Hadir</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #7c3aed 0%, #db2777 50%, #f43f5e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .scan-card {
            background: white;
            border-radius: 25px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
        }
        .scan-header {
            background: linear-gradient(135deg, #7c3aed, #db2777);
            padding: 25px 35px;
            color: white;
            text-align: center;
        }
        .scan-header h2 { font-family: 'Space Grotesk', sans-serif; margin: 0; font-size: 1.8rem; }
        .scan-header .date-info { margin-top: 8px; font-size: 0.9rem; opacity: 0.9; }
        .scan-body { padding: 30px; }
        
        .camera-wrapper {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border: 4px solid #7c3aed;
            background: #000;
        }
        #video { width: 100%; display: block; border-radius: 16px; }
        
        .camera-overlay {
            position: absolute;
            top: 15px; left: 15px; right: 15px;
            padding: 10px 15px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        .overlay-loading { background: rgba(254, 243, 199, 0.95); color: #92400e; }
        .overlay-success { background: rgba(209, 250, 229, 0.95); color: #065f46; }
        .overlay-error { background: rgba(254, 226, 226, 0.95); color: #991b1b; }
        .overlay-waiting { background: rgba(219, 234, 254, 0.95); color: #1e40af; }
        
        .main-status {
            text-align: center;
            padding: 20px;
            margin-top: 20px;
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s;
        }
        .status-loading { background: #fef3c7; color: #92400e; }
        .status-success { background: #d1fae5; color: #065f46; }
        .status-error { background: #fee2e2; color: #991b1b; }
        .status-waiting { background: #dbeafe; color: #1e40af; }
        
        .processing-indicator { display: none; text-align: center; padding: 15px; margin-top: 15px; }
        .processing-indicator.active { display: block; }
        
        .spinner {
            width: 40px; height: 40px;
            border: 4px solid #f3f4f6;
            border-top: 4px solid #7c3aed;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        .face-frame {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 250px; height: 300px;
            border: 3px dashed rgba(255,255,255,0.5);
            border-radius: 50%;
            pointer-events: none;
        }
        
        .btn-kembali {
            display: block;
            width: 100%;
            margin-top: 20px;
            padding: 15px;
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .btn-kembali:hover {
            background: linear-gradient(135deg, #4b5563, #374151);
            transform: translateY(-2px);
            color: white;
        }
        
        .info-box-scan {
            background: linear-gradient(135deg, #faf5ff, #fdf2f8);
            border-left: 4px solid #7c3aed;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="scan-card">
        <div class="scan-header">
            <h2><i class="fas fa-camera me-2"></i>Scan Wajah</h2>
            <div class="date-info">
                <i class="fas fa-calendar-day me-1"></i> <?= date('l, d F Y') ?> | <?= date('H:i') ?> WIB
            </div>
        </div>
        <div class="scan-body">
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Absensi berhasil!</div>
            <?php endif; ?>

            <div class="info-box-scan">
                <i class="fas fa-info-circle me-2" style="color: #7c3aed;"></i>
                <strong>Hari ini:</strong> <?= date('l, d F Y') ?> | <?= date('H:i') ?> WIB
            </div>

            <?php if(count($jadwal_bisa_absen) == 0): ?>
                <!-- TIDAK ADA SESI ABSEN AKTIF -->
                <div class="alert alert-warning text-center">
                    <i class="fas fa-lock fa-2x mb-2"></i>
                    <h5>Tidak Ada Sesi Absen Aktif</h5>
                    <p class="mb-0">
                        <?php if(count($jadwal_buka) == 0): ?>
                            Saat ini tidak ada jadwal kuliah yang sedang berlangsung, atau absensi sedang ditutup oleh admin. Silakan coba lagi sesuai jadwal.
                        <?php else: ?>
                            Anda sudah absen untuk semua jadwal yang aktif hari ini.
                        <?php endif; ?>
                    </p>
                    <a href="/kampus-absensi/public/index.php?page=mahasiswa_dashboard" class="btn btn-secondary mt-3">
                        <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                    </a>
                </div>
            <?php else: ?>
                <!-- ABSENSI TERBUKA - TAMPILKAN KAMERA -->
                <div class="alert alert-success text-center">
                    <i class="fas fa-unlock fa-2x mb-2"></i>
                    <h5>Absensi Terbuka!</h5>
                    <p class="mb-1">Jadwal aktif saat ini:</p>
                    <?php foreach($jadwal_bisa_absen as $j): ?>
                        <strong><?= htmlspecialchars($j['nama_matkul']) ?></strong> 
                        (<?= date('H:i', strtotime($j['jam_mulai'])) ?> - <?= date('H:i', strtotime($j['jam_selesai'])) ?>)
                        <br><small class="text-muted">Dosen: <?= htmlspecialchars($j['nama_dosen']) ?></small>
                        <hr>
                    <?php endforeach; ?>
                </div>

                <!-- Kamera -->
                <div class="camera-wrapper">
                    <video id="video" autoplay muted playsinline></video>
                    <canvas id="canvas" style="display:none;"></canvas>
                    <div class="face-frame"></div>
                    <div id="cameraOverlay" class="camera-overlay overlay-loading">
                        <i class="fas fa-spinner fa-spin"></i> Memuat sistem...
                    </div>
                </div>

                <div id="mainStatus" class="main-status status-loading">
                    <i class="fas fa-spinner fa-spin"></i> Inisialisasi sistem...
                </div>

                <div id="processingIndicator" class="processing-indicator">
                    <div class="spinner"></div>
                    <div style="font-weight: 600; color: #7c3aed;">Memproses absensi...</div>
                    <div style="font-size: 0.85rem; color: #6b7280;">Mohon tunggu sebentar</div>
                </div>

                <a href="/kampus-absensi/public/index.php?page=mahasiswa_dashboard" class="btn-kembali">
                    <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if(count($jadwal_bisa_absen) > 0): ?>
    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script>
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const cameraOverlay = document.getElementById('cameraOverlay');
        const mainStatus = document.getElementById('mainStatus');
        const processingIndicator = document.getElementById('processingIndicator');

        let referenceDescriptor = null;
        let detectionCount = 0;
        let modelsLoaded = false;
        let isProcessing = false;
        const MATCH_THRESHOLD = 0.5;

        function euclideanDistance(desc1, desc2) {
            let sum = 0;
            for (let i = 0; i < desc1.length; i++) {
                sum += Math.pow(desc1[i] - desc2[i], 2);
            }
            return Math.sqrt(sum);
        }

        function setOverlay(type, message) {
            cameraOverlay.className = 'camera-overlay overlay-' + type;
            const icons = {
                loading: 'fa-spinner fa-spin',
                success: 'fa-check-circle',
                error: 'fa-times-circle',
                waiting: 'fa-video'
            };
            cameraOverlay.innerHTML = `<i class="fas ${icons[type]}"></i> ${message}`;
        }

        function setMainStatus(type, message) {
            mainStatus.className = 'main-status status-' + type;
            mainStatus.innerHTML = message;
        }

        async function loadModels() {
            try {
                setOverlay('loading', 'Memuat AI...');
                setMainStatus('loading', '<i class="fas fa-spinner fa-spin"></i> Memuat model AI...');
                
                const MODEL_URL = '/kampus-absensi/public/assets/models';
                await Promise.all([
                    faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                    faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                    faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
                ]);
                
                modelsLoaded = true;
                setOverlay('waiting', 'Sistem siap');
                setMainStatus('loading', '<i class="fas fa-check-circle"></i> Model AI siap! Mengaktifkan kamera...');
                
                await loadReferenceDescriptor();
                setTimeout(() => startCamera(), 500);
            } catch (error) {
                console.error('Error loading models:', error);
                setOverlay('error', 'Gagal memuat AI');
                setMainStatus('error', '❌ Gagal memuat model AI. Refresh halaman untuk coba lagi.');
            }
        }

        async function loadReferenceDescriptor() {
            try {
                const response = await fetch('/kampus-absensi/public/api_get_descriptor.php');
                const data = await response.json();
                
                if (data.success && data.descriptor) {
                    referenceDescriptor = data.descriptor;
                } else {
                    setMainStatus('error', '❌ Foto referensi tidak valid. Hubungi admin!');
                    setOverlay('error', 'Error referensi');
                }
            } catch (error) {
                console.error('Error loading reference:', error);
            }
        }

        async function startCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ 
                    video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' }
                });
                video.srcObject = stream;
                setOverlay('waiting', 'Kamera aktif');
                setMainStatus('waiting', '📷 Kamera aktif. Arahkan wajah ke kamera...');
            } catch (err) {
                console.error('Camera error:', err);
                setOverlay('error', 'Kamera error');
                setMainStatus('error', '❌ Tidak bisa mengakses kamera. Izinkan akses kamera di browser.');
            }
        }

        async function autoSubmitAbsensi(distance) {
            if (isProcessing) return;
            isProcessing = true;
            
            setMainStatus('loading', '<div class="spinner"></div> Memproses absensi...');
            setOverlay('loading', 'Memproses...');
            processingIndicator.classList.add('active');
            
            try {
                const response = await fetch('/kampus-absensi/public/auto_absen.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ face_distance: distance.toFixed(4) })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    setMainStatus('success', '✅ Absensi berhasil! Mengalihkan ke riwayat...');
                    setOverlay('success', 'Berhasil!');
                    
                    setTimeout(() => {
                        window.location.href = '/kampus-absensi/public/index.php?page=mahasiswa_riwayat&absen_success=1';
                    }, 1500);
                } else {
                    setMainStatus('error', '❌ ' + (result.message || 'Gagal absensi'));
                    setOverlay('error', 'Gagal');
                    isProcessing = false;
                    processingIndicator.classList.remove('active');
                }
            } catch (error) {
                console.error('Auto submit error:', error);
                setMainStatus('error', '❌ Error koneksi server');
                isProcessing = false;
                processingIndicator.classList.remove('active');
            }
        }

        video.addEventListener('play', () => {
            setInterval(async () => {
                if (!modelsLoaded || !referenceDescriptor || isProcessing) return;
                
                try {
                    const detection = await faceapi.detectSingleFace(
                        video,
                        new faceapi.TinyFaceDetectorOptions({
                            inputSize: 512,
                            scoreThreshold: 0.5
                        })
                    ).withFaceLandmarks().withFaceDescriptor();
                    
                    if (detection) {
                        const currentDescriptor = detection.descriptor;
                        const distance = euclideanDistance(currentDescriptor, referenceDescriptor);
                        const isMatch = distance < MATCH_THRESHOLD;
                        
                        if (isMatch) {
                            detectionCount++;
                            setOverlay('success', `Wajah cocok (${detectionCount}/3)`);
                            
                            if (detectionCount >= 3) {
                                setMainStatus('success', `✅ WAJAH COCOK! (Distance: ${distance.toFixed(3)}) - Memproses absensi...`);
                                await autoSubmitAbsensi(distance);
                            } else {
                                setMainStatus('success', `✅ Wajah terdeteksi... Memvalidasi (${detectionCount}/3)`);
                            }
                        } else {
                            detectionCount = 0;
                            setOverlay('error', 'Wajah tidak cocok!');
                            setMainStatus('error', `❌ WAJAH TIDAK COCOK! (Distance: ${distance.toFixed(3)})`);
                        }
                    } else {
                        detectionCount = 0;
                        setOverlay('waiting', 'Mencari wajah...');
                        setMainStatus('waiting', '📷 Wajah tidak terdeteksi. Arahkan wajah ke kamera...');
                    }
                } catch (error) {
                    console.error('Detection error:', error);
                }
            }, 500);
        });

        window.addEventListener('load', loadModels);
    </script>
    <?php endif; ?>
</body>
</html>