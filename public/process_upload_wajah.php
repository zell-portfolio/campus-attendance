<?php
date_default_timezone_set('Asia/Makassar');
session_start();
if ($_SESSION['role'] !== 'admin') { header("Location: /kampus-absensi/public/index.php?page=login"); exit(); }
require_once __DIR__ . "/../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? '';
    $face_descriptor = $_POST['face_descriptor'] ?? '';
    
    if (empty($user_id) || !isset($_FILES['foto_wajah']) || $_FILES['foto_wajah']['error'] !== 0) {
        header("Location: /kampus-absensi/public/index.php?page=admin_upload_wajah&error=Upload Gagal");
        exit();
    }
    
    $file = $_FILES['foto_wajah'];
    $uploadDir = __DIR__ . "/uploads/faces/";
    if (!file_exists($uploadDir)) { mkdir($uploadDir, 0777, true); }
    
    $fileName = 'face_' . $user_id . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $targetPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $foto_path = 'uploads/faces/' . $fileName;
        try {
            $database = new Database();
            $conn = $database->getConnection();
            $stmt = $conn->prepare("UPDATE users SET foto_wajah_path = ?, face_descriptor = ? WHERE id = ?");
            $stmt->execute([$foto_path, $face_descriptor, $user_id]);
            header("Location: /kampus-absensi/public/index.php?page=admin_upload_wajah&success=1");
            exit();
        } catch (Exception $e) {
            header("Location: /kampus-absensi/public/index.php?page=admin_upload_wajah&error=Database Error");
            exit();
        }
    } else {
        header("Location: /kampus-absensi/public/index.php?page=admin_upload_wajah&error=Gagal memindahkan file");
        exit();
    }
}
?>