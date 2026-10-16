<?php
session_start();
if ($_SESSION['role'] !== 'admin') { header("Location: /kampus-absensi/public/index.php?page=login"); exit(); }
require_once __DIR__ . "/../config/database.php";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode = $_POST['kode_matkul']; $nama = $_POST['nama_matkul']; $sks = $_POST['sks'];
    try {
        $database = new Database(); $conn = $database->getConnection();
        $stmt = $conn->prepare("INSERT INTO matkul (kode_matkul, nama_matkul, sks) VALUES (?, ?, ?)");
        $stmt->execute([$kode, $nama, $sks]);
        header("Location: /kampus-absensi/public/index.php?page=admin_kelola_matkul&success=1");
    } catch (Exception $e) { echo "Error: " . $e->getMessage(); }
}
?>