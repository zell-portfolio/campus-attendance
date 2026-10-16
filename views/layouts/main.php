<?php if(!Auth::check()) { header("Location: /kampus-absensi/public/index.php?page=login"); exit(); } ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Absensi Kampus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar { min-height: 100vh; background: #343a40; }
        .sidebar a { color: white; padding: 10px; display: block; }
        .sidebar a:hover { background: #495057; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-2 sidebar p-3">
            <h4 class="text-white mb-4">Absensi Kampus</h4>
            <p class="text-white-50">Halo, <?= $_SESSION['nama'] ?></p>
            <hr class="bg-white">
            
            <?php if($_SESSION['role'] === 'mahasiswa'): ?>
                <a href="/kampus-absensi/public/index.php?page=mahasiswa_dashboard">Dashboard</a>
                <a href="/kampus-absensi/public/index.php?page=mahasiswa_absen_hadir">Absen Hadir</a>
                <a href="/kampus-absensi/public/index.php?page=mahasiswa_absen_izin_sakit">Izin/Sakit</a>
                <a href="/kampus-absensi/public/index.php?page=mahasiswa_riwayat">Riwayat</a>
            <?php elseif($_SESSION['role'] === 'dosen'): ?>
                <a href="/kampus-absensi/public/index.php?page=dosen_dashboard">Dashboard</a>
                <a href="/kampus-absensi/public/index.php?page=dosen_absen">Absen Dosen</a>
                <a href="/kampus-absensi/public/index.php?page=dosen_kelola_absen">Kelola Absen</a>
                <a href="/kampus-absensi/public/index.php?page=dosen_riwayat">Riwayat</a>
            <?php elseif($_SESSION['role'] === 'admin'): ?>
                <a href="/kampus-absensi/public/index.php?page=admin_dashboard">Dashboard</a>
                <a href="/kampus-absensi/public/index.php?page=admin_kelola_mahasiswa">Kelola Mahasiswa</a>
                <a href="/kampus-absensi/public/index.php?page=admin_kelola_dosen">Kelola Dosen</a>
                <a href="/kampus-absensi/public/index.php?page=admin_kelola_matkul">Kelola Matkul</a>
                <a href="/kampus-absensi/public/index.php?page=admin_upload_wajah">Upload Wajah</a>
            <?php endif; ?>
            
            <hr class="bg-white">
            <a href="/kampus-absensi/public/index.php?page=logout" class="text-danger">Logout</a>
        </div>
        <div class="col-md-10 p-4">
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success">Data berhasil disimpan!</div>
            <?php endif; ?>