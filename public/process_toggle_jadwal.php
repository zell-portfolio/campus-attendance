<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /kampus-absensi/public/index.php?page=login");
    exit();
}

require_once __DIR__ . "/../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $action = $_POST['action'] ?? ''; // 'open' atau 'close'
    
    if (empty($id) || !in_array($action, ['open', 'close'])) {
        header("Location: /kampus-absensi/public/index.php?page=admin_kelola_jadwal&error=invalid_action");
        exit();
    }
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        if ($action === 'open') {
            // Buka absen (force buka, override jam)
            $stmt = $conn->prepare("UPDATE jadwal SET is_open = 1, manual_override = 1 WHERE id = ?");
        } else {
            // Tutup absen (force tutup)
            $stmt = $conn->prepare("UPDATE jadwal SET is_open = 0, manual_override = 1 WHERE id = ?");
        }
        $stmt->execute([$id]);
        
        header("Location: /kampus-absensi/public/index.php?page=admin_kelola_jadwal&success=toggle_" . $action);
        exit();
        
    } catch (Exception $e) {
        header("Location: /kampus-absensi/public/index.php?page=admin_kelola_jadwal&error=" . urlencode($e->getMessage()));
        exit();
    }
}

header("Location: /kampus-absensi/public/index.php?page=admin_kelola_jadwal");
exit();
?>