<?php
require_once __DIR__ . "/../config/database.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Batch Extract Face Descriptors</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #4e73df; color: white; }
        .btn-extract {
            background: #4e73df;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-extract:hover { background: #224abe; }
        .btn-extract:disabled { background: #ccc; cursor: not-allowed; }
        .status-success { color: green; font-weight: bold; }
        .status-error { color: red; font-weight: bold; }
        .status-processing { color: orange; }
        img { width: 80px; height: 80px; object-fit: cover; border-radius: 5px; }
        #log { margin-top: 20px; padding: 10px; background: #f5f5f5; border: 1px solid #ddd; max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px; }
    </style>
</head>
<body>
    <h2>Batch Extract Face Descriptors</h2>
    <p>Script ini akan extract face descriptor dari SEMUA foto wajah yang sudah ada.</p>
    <button onclick="extractAll()" class="btn-extract" style="margin-bottom: 20px;">🔄 Extract Semua</button>
    <hr>
    
    <?php
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $stmt = $conn->query("SELECT id, nama, nip_nim, foto_wajah_path, face_descriptor FROM users WHERE foto_wajah_path IS NOT NULL AND foto_wajah_path != ''");
        $users = $stmt->fetchAll();
        
        echo "<h3>Ditemukan " . count($users) . " user dengan foto wajah</h3>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Nama</th><th>NIM/NIP</th><th>Foto</th><th>Status Descriptor</th><th>Aksi</th></tr>";
        
        foreach ($users as $user) {
            $fotoPath = __DIR__ . '/' . $user['foto_wajah_path'];
            $fileExists = file_exists($fotoPath);
            $hasDescriptor = !empty($user['face_descriptor']);
            
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['nama']}</td>";
            echo "<td>{$user['nip_nim']}</td>";
            echo "<td>";
            if ($fileExists) {
                echo "<img src='/kampus-absensi/public/{$user['foto_wajah_path']}'>";
            } else {
                echo "File tidak ada";
            }
            echo "</td>";
            echo "<td id='status-{$user['id']}'>";
            if ($hasDescriptor) {
                echo "<span class='status-success'>✓ Sudah ada descriptor</span>";
            } else {
                echo "Belum ada descriptor";
            }
            echo "</td>";
            echo "<td><button onclick='extractDescriptor({$user['id']})' class='btn-extract' id='btn-{$user['id']}'>Extract</button></td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
    ?>
    
    <div id="log"><strong>Log:</strong><br></div>

    <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <script>
    let modelsLoaded = false;
    const logEl = document.getElementById('log');

    function log(msg) {
        logEl.innerHTML += msg + '<br>';
        logEl.scrollTop = logEl.scrollHeight;
        console.log(msg);
    }

    async function loadModels() {
        if (modelsLoaded) return;
        log('Loading models...');
        try {
            const MODEL_URL = '/kampus-absensi/public/assets/models';
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
            ]);
            modelsLoaded = true;
            log('✅ Models loaded successfully');
        } catch (e) {
            log('❌ Error loading models: ' + e.message);
        }
    }

    async function extractDescriptor(userId) {
        if (!modelsLoaded) {
            await loadModels();
        }
        
        const statusEl = document.getElementById('status-' + userId);
        const btnEl = document.getElementById('btn-' + userId);
        
        statusEl.innerHTML = '<span class="status-processing"> Processing...</span>';
        btnEl.disabled = true;
        
        try {
            // Ambil foto dari database
            log(`Fetching foto for user ${userId}...`);
            const response = await fetch(`/kampus-absensi/public/api_get_foto.php?user_id=${userId}`);
            const data = await response.json();
            
            log(`Response: ${JSON.stringify(data)}`);
            
            if (!data.success || !data.foto_path) {
                statusEl.innerHTML = `<span class="status-error">❌ ${data.message || 'Foto tidak ditemukan'}</span>`;
                btnEl.disabled = false;
                return;
            }
            
            // Load image dan extract descriptor
            log(`Loading image: ${data.foto_path}`);
            const img = await faceapi.fetchImage('/kampus-absensi/public/' + data.foto_path);
            log('Image loaded, detecting face...');
            
            const detection = await faceapi.detectSingleFace(img, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks()
                .withFaceDescriptor();
            
            if (!detection) {
                statusEl.innerHTML = '<span class="status-error">❌ Tidak ada wajah terdeteksi di foto</span>';
                btnEl.disabled = false;
                return;
            }
            
            log(`Face detected! Descriptor length: ${detection.descriptor.length}`);
            
            // Simpan descriptor ke database
            const descriptorArray = Array.from(detection.descriptor);
            const descriptorJson = JSON.stringify(descriptorArray);
            
            log(`Saving descriptor to database...`);
            const saveResponse = await fetch('/kampus-absensi/public/extract_descriptor.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    descriptor: descriptorJson,
                    user_id: userId
                })
            });
            
            const saveResult = await saveResponse.json();
            log(`Save response: ${JSON.stringify(saveResult)}`);
            
            if (saveResult.success) {
                statusEl.innerHTML = '<span class="status-success">✅ Descriptor tersimpan!</span>';
            } else {
                statusEl.innerHTML = `<span class="status-error">❌ Gagal: ${saveResult.message}</span>`;
            }
            
        } catch (e) {
            log(`❌ Error: ${e.message}`);
            statusEl.innerHTML = `<span class="status-error">❌ Error: ${e.message}</span>`;
            btnEl.disabled = false;
        }
    }

    async function extractAll() {
        if (!modelsLoaded) {
            await loadModels();
        }
        
        const buttons = document.querySelectorAll('.btn-extract');
        for (let btn of buttons) {
            if (!btn.disabled && btn.textContent === 'Extract') {
                const userId = btn.id.replace('btn-', '');
                await extractDescriptor(userId);
                await new Promise(r => setTimeout(r, 500)); // Delay antar extract
            }
        }
    }

    // Load models saat halaman siap
    window.addEventListener('load', () => {
        loadModels();
    });
    </script>
</body>
</html>