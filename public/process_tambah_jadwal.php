<?php
session_start();
if ($_SESSION['role'] !== 'admin') { 
    header("Location: /kampus-absensi/public/index.php?page=login"); 
    exit(); 
}
require_once __DIR__ . "/../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hari = $_POST['hari'];
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];
    $matkul_id = $_POST['matkul_id'];
    $dosen_id = $_POST['dosen_id'];
    $semester = $_POST['semester'];
    $tahun_ajaran = $_POST['tahun_ajaran'];

    try {
        $database = new Database();
        $conn = $database->getConnection();
        $stmt = $conn->prepare("INSERT INTO jadwal (hari, jam_mulai, jam_selesai, matkul_id, dosen_id, semester, tahun_ajaran) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$hari, $jam_mulai, $jam_selesai, $matkul_id, $dosen_id, $semester, $tahun_ajaran]);
        header("Location: /kampus-absensi/public/index.php?page=admin_kelola_jadwal&success=1");
        exit();
    } catch (Exception $e) {
        header("Location: /kampus-absensi/public/index.php?page=admin_kelola_jadwal&error=" . urlencode($e->getMessage()));
        exit();
    }
}
?>