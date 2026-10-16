<?php
session_start();

// Untuk batch script, kita izinkan tanpa session check dulu (bisa diperketat nanti)
// if ($_SESSION['role'] !== 'admin') {
//     http_response_code(403);
//     exit(json_encode(['error' => 'Unauthorized']));
// }

require_once __DIR__ . "/../config/database.php";

header('Content-Type: application/json');

// Baca input dari POST
$input = json_decode(file_get_contents('php://input'), true);

// Debug: log input
error_log("Input received: " . print_r($input, true));

$descriptor = $input['descriptor'] ?? null;
$user_id = $input['user_id'] ?? null;

if (empty($descriptor) || empty($user_id)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Data tidak lengkap. Descriptor: ' . ($descriptor ? 'ada' : 'kosong') . ', User ID: ' . ($user_id ? $user_id : 'kosong')
    ]);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $stmt = $conn->prepare("UPDATE users SET face_descriptor = ? WHERE id = ?");
    $result = $stmt->execute([$descriptor, $user_id]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Descriptor tersimpan untuk user ID: ' . $user_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal execute query']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>