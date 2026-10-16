<?php
session_start();
if ($_SESSION['role'] !== 'admin') { header("Location: /kampus-absensi/public/index.php?page=login"); exit(); }
require_once __DIR__ . "/../config/database.php";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    try {
        $database = new Database(); $conn = $database->getConnection();
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'dosen'");
        $stmt->execute([$id]);
        header("Location: /kampus-absensi/public/index.php?page=admin_kelola_dosen&success=1");
    } catch (Exception $e) { echo "Error: " . $e->getMessage(); }
}
?>