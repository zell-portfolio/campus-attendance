<?php
require_once __DIR__ . "/../config/database.php";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$user_id = $_GET['user_id'] ?? '';

if (empty($user_id)) {
    echo json_encode(['success' => false, 'message' => 'User ID diperlukan']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $stmt = $conn->prepare("SELECT foto_wajah_path FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user || empty($user['foto_wajah_path'])) {
        echo json_encode(['success' => false, 'message' => 'Foto tidak ditemukan untuk user ID: ' . $user_id]);
        exit();
    }
    
    // Cek apakah file benar-benar ada
    $fullPath = __DIR__ . '/' . $user['foto_wajah_path'];
    if (!file_exists($fullPath)) {
        echo json_encode(['success' => false, 'message' => 'File foto tidak ada di server: ' . $user['foto_wajah_path']]);
        exit();
    }
    
    echo json_encode([
        'success' => true,
        'foto_path' => $user['foto_wajah_path'],
        'user_id' => $user_id
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>