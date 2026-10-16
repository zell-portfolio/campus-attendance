<?php
session_start();
if ($_SESSION['role'] !== 'admin') { header("Location: /kampus-absensi/public/index.php?page=login"); exit(); }

require_once __DIR__ . "/../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nip_nim = $_POST['nip_nim'];
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        $stmt = $conn->prepare("INSERT INTO users (nip_nim, nama, email, password, role) VALUES (?, ?, ?, ?, 'mahasiswa')");
        $stmt->execute([$nip_nim, $nama, $email, $password]);
        header("Location: /kampus-absensi/public/index.php?page=admin_kelola_mahasiswa&success=1");
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>