<?php
require_once __DIR__ . "/../../config/database.php";
$database = new Database();
$conn = $database->getConnection();

$mahasiswa_count = $conn->query("SELECT COUNT(*) FROM users WHERE role='mahasiswa'")->fetchColumn();
$dosen_count = $conn->query("SELECT COUNT(*) FROM users WHERE role='dosen'")->fetchColumn();
$matkul_count = $conn->query("SELECT COUNT(*) FROM matkul")->fetchColumn();
$absensi_count = $conn->query("SELECT COUNT(*) FROM absensi")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4e73df;
            --primary-dark: #224abe;
            --sidebar-width: 260px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: #f8f9fc; font-family: 'Segoe UI', Tahoma, sans-serif; }
        
        /* FIXED SIDEBAR - TIDAK IKUT SCROLL */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, var(--primary) 10%, var(--primary-dark) 100%);
            color: white;
            z-index: 1000;
            overflow-y: auto;
            overflow-x: hidden;
        }
        .sidebar-brand {
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: 800;
            color: white;
            text-decoration: none;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 15px 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            text-decoration: none;
        }
        .sidebar .nav-link:hover { color: white; background: rgba(255,255,255,0.1); }
        .sidebar .nav-link.active { color: white; font-weight: 700; background: rgba(255,255,255,0.15); }
        .sidebar .nav-link i { margin-right: 10px; width: 20px; text-align: center; }
        
        /* MAIN CONTENT - YANG SCROLL */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }
        .topbar {
            height: 70px;
            background: white;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .content-wrapper { padding: 30px; }
        
        /* Stats Cards */
        .card-stat {
            border: none;
            border-left: 4px solid;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            transition: transform 0.3s;
        }
        .card-stat:hover { transform: translateY(-5px); }
        .card-stat.primary { border-left-color: var(--primary); }
        .card-stat.success { border-left-color: #1cc88a; }
        .card-stat.info { border-left-color: #36b9cc; }
        .card-stat.warning { border-left-color: #f6c23e; }
        .stat-number { font-size: 1.8rem; font-weight: 700; color: #5a5c69; }
        .stat-label { font-size: 0.85rem; text-transform: uppercase; color: #858796; font-weight: 700; }
        
        /* Menu Cards */
        .menu-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            transition: all 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .menu-card:hover { transform: translateY(-3px); box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2); color: inherit; }
        .menu-icon {
            width: 60px; height: 60px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; margin-bottom: 15px; color: white;
        }
        .bg-gradient-primary { background: linear-gradient(45deg, #4e73df, #224abe); }
        .bg-gradient-success { background: linear-gradient(45deg, #1cc88a, #13855c); }
        .bg-gradient-info { background: linear-gradient(45deg, #36b9cc, #258391); }
        .bg-gradient-warning { background: linear-gradient(45deg, #f6c23e, #dda20a); }
    </style>
</head>
<body>
    <!-- FIXED SIDEBAR -->
    <div class="sidebar">
        <a class="sidebar-brand" href="/kampus-absensi/public/index.php?page=admin_dashboard">
            <i class="fas fa-university me-2"></i> ADMIN PANEL
        </a>
        <ul class="nav flex-column mt-3">
            <li class="nav-item">
                <a class="nav-link active" href="/kampus-absensi/public/index.php?page=admin_dashboard">
                    <i class="fas fa-fw fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/kampus-absensi/public/index.php?page=admin_kelola_mahasiswa">
                    <i class="fas fa-fw fa-user-graduate"></i> Mahasiswa
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/kampus-absensi/public/index.php?page=admin_kelola_dosen">
                    <i class="fas fa-fw fa-chalkboard-teacher"></i> Dosen
                </a>
            </li>
           <li class="nav-item"><a class="nav-link" href="/kampus-absensi/public/index.php?page=admin_kelola_matkul"><i class="fas fa-fw fa-book"></i> Mata Kuliah</a></li>
           <li class="nav-item"><a class="nav-link" href="/kampus-absensi/public/index.php?page=admin_kelola_jadwal"><i class="fas fa-fw fa-calendar-alt"></i> Jadwal Kuliah</a></li>

<li class="nav-item"><a class="nav-link" href="/kampus-absensi/public/index.php?page=admin_rekap_absensi"><i class="fas fa-fw fa-clipboard-list"></i> Rekap Absensi</a></li>
<li class="nav-item"><a class="nav-link" href="/kampus-absensi/public/index.php?page=admin_upload_wajah"><i class="fas fa-fw fa-camera"></i> Upload Wajah</a></li>

<li class="nav-item mt-5"><a class="nav-link" href="/kampus-absensi/public/logout.php"><i class="fas fa-fw fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT (SCROLLABLE) -->
    <div class="main-content">
        <div class="topbar">
            <h4 class="mb-0" style="color: #5a5c69;">Dashboard</h4>
            <div class="d-flex align-items-center">
                <span class="me-3" style="color: #5a5c69;">
                    <i class="fas fa-user-circle me-2"></i><?= htmlspecialchars($_SESSION['nama']) ?>
                </span>
                <a href="/kampus-absensi/public/logout.php" class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>

        <div class="content-wrapper">
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card card-stat primary p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label mb-1">Mahasiswa</div>
                                <div class="stat-number"><?= $mahasiswa_count ?></div>
                            </div>
                            <i class="fas fa-user-graduate fa-2x" style="opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card card-stat success p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label mb-1">Dosen</div>
                                <div class="stat-number"><?= $dosen_count ?></div>
                            </div>
                            <i class="fas fa-chalkboard-teacher fa-2x" style="opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card card-stat info p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label mb-1">Mata Kuliah</div>
                                <div class="stat-number"><?= $matkul_count ?></div>
                            </div>
                            <i class="fas fa-book fa-2x" style="opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card card-stat warning p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label mb-1">Total Absensi</div>
                                <div class="stat-number"><?= $absensi_count ?></div>
                            </div>
                            <i class="fas fa-clipboard-list fa-2x" style="opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
            </div>

            <h5 class="mb-3" style="color: #5a5c69;">Menu Manajemen</h5>
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <a href="/kampus-absensi/public/index.php?page=admin_kelola_mahasiswa" class="menu-card text-center p-4">
                        <div class="menu-icon bg-gradient-primary mx-auto"><i class="fas fa-user-graduate"></i></div>
                        <h6 class="mb-1">Kelola Mahasiswa</h6>
                        <p class="text-muted small mb-0">Tambah, edit, hapus data mahasiswa</p>
                    </a>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <a href="/kampus-absensi/public/index.php?page=admin_kelola_dosen" class="menu-card text-center p-4">
                        <div class="menu-icon bg-gradient-success mx-auto"><i class="fas fa-chalkboard-teacher"></i></div>
                        <h6 class="mb-1">Kelola Dosen</h6>
                        <p class="text-muted small mb-0">Tambah, edit, hapus data dosen</p>
                    </a>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
    <a href="/kampus-absensi/public/index.php?page=admin_rekap_absensi" class="menu-card text-center p-4">
        <div class="menu-icon bg-gradient-danger mx-auto" style="background: linear-gradient(45deg, #e74a3b, #c0392b);">
            <i class="fas fa-clipboard-list"></i>
        </div>
        <h6 class="mb-1">Rekap Absensi</h6>
        <p class="text-muted small mb-0">Lihat semua data absensi</p>
    </a>
</div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <a href="/kampus-absensi/public/index.php?page=admin_kelola_matkul" class="menu-card text-center p-4">
                        <div class="menu-icon bg-gradient-info mx-auto"><i class="fas fa-book"></i></div>
                        <h6 class="mb-1">Kelola Matkul</h6>
                        <p class="text-muted small mb-0">Kelola data mata kuliah</p>
                    </a>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <a href="/kampus-absensi/public/index.php?page=admin_upload_wajah" class="menu-card text-center p-4">
                        <div class="menu-icon bg-gradient-warning mx-auto"><i class="fas fa-camera"></i></div>
                        <h6 class="mb-1">Upload Wajah</h6>
                        <p class="text-muted small mb-0">Upload foto untuk face recognition</p>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>