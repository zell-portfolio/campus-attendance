<?php
$folders = [
    __DIR__ . '/uploads/faces',
    __DIR__ . '/uploads/videos',
    __DIR__ . '/uploads/pdfs'
];

foreach ($folders as $folder) {
    if (!file_exists($folder)) {
        if (mkdir($folder, 0777, true)) {
            echo "✅ Folder dibuat: " . basename($folder) . "<br>";
        } else {
            echo "❌ Gagal membuat folder: " . basename($folder) . "<br>";
        }
    } else {
        echo "✓ Folder sudah ada: " . basename($folder) . "<br>";
    }
}

echo "<br><a href='/kampus-absensi/public/index.php?page=admin_upload_wajah'>Kembali ke Upload Wajah</a>";
?>