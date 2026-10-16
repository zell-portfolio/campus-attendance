<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit(json_encode(['error' => 'Unauthorized']));
}

require_once __DIR__ . "/../config/database.php";

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $stmt = $conn->prepare("SELECT face_descriptor FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user || empty($user['face_descriptor'])) {
        echo json_encode(['success' => false, 'message' => 'Descriptor tidak ditemukan']);
        exit();
    }
    
    echo json_encode([
        'success' => true,
        'descriptor' => json_decode($user['face_descriptor'])
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>