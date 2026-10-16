<?php
session_start();
if ($_SESSION['role'] !== 'admin') { header("Location: /kampus-absensi/public/index.php?page=login"); exit(); }
require_once __DIR__ . "/../config/database.php";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id']; $nip_nim = $_POST['nip_nim']; $nama = $_POST['nama']; $email = $_POST['email'];
    try {
        $database = new Database(); $conn = $database->getConnection();
        $stmt = $conn->prepare("UPDATE users SET nip_nim = ?, nama = ?, email = ? WHERE id = ?");
        $stmt->execute([$nip_nim, $nama, $email, $id]);
        header("Location: /kampus-absensi/public/index.php?page=admin_kelola_dosen&success=1");
    } catch (Exception $e) { echo "Error: " . $e->getMessage(); }
}
?>