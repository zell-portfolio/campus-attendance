<?php if(!Auth::check()) { header("Location: /kampus-absensi/public/index.php?page=login"); exit(); } ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Aplikasi Absensi Kampus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="/kampus-absensi/public/index.php?page=dashboard">Absensi Kampus</a>
        <div class="navbar-nav ms-auto">
            <span class="navbar-text text-white me-3">Halo, <?= $_SESSION['nama'] ?> (<?= $_SESSION['role'] ?>)</span>
            <a class="nav-link" href="/kampus-absensi/public/index.php?page=logout">Logout</a>
        </div>
    </div>
</nav>