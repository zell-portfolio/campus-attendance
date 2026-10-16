<?php
date_default_timezone_set('Asia/Makassar');
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// ==========================================
// 1. HANDLE LOGIN (POST REQUEST)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && isset($_POST['password'])) {
    require_once __DIR__ . "/../config/database.php";
    
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Login BERHASIL: Simpan data ke session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nip_nim'] = $user['nip_nim'];
            
            // Redirect ke dashboard sesuai role
            if ($user['role'] === 'admin') {
                header("Location: /kampus-absensi/public/index.php?page=admin_dashboard");
            } elseif ($user['role'] === 'dosen') {
                header("Location: /kampus-absensi/public/index.php?page=dosen_dashboard");
            } elseif ($user['role'] === 'mahasiswa') {
                header("Location: /kampus-absensi/public/index.php?page=mahasiswa_dashboard");
            }
            exit();
        } else {
            // Login GAGAL: Set variabel error untuk ditampilkan di halaman login
            $error = "Email atau password salah!";
        }
    } catch (Exception $e) {
        $error = "Terjadi kesalahan sistem: " . $e->getMessage();
    }
}

// ==========================================
// 2. HANDLE LOGOUT (GET REQUEST)
// ==========================================
if (isset($_GET['page']) && $_GET['page'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: /kampus-absensi/public/index.php?page=login&logged_out=1");
    exit();
}

// ==========================================
// 3. HELPER FUNCTIONS (PROTEKSI HALAMAN)
// ==========================================
function cekLogin() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header("Location: /kampus-absensi/public/index.php?page=login");
        exit();
    }
}

function cekRole($role_yang_diizinkan) {
    cekLogin();
    if ($_SESSION['role'] !== $role_yang_diizinkan) {
        header("Location: /kampus-absensi/public/index.php?page=login");
        exit();
    }
}

// ==========================================
// 4. ROUTING (PENGALIHAN HALAMAN)
// ==========================================
$page = $_GET['page'] ?? 'login';

switch ($page) {
    // --- AUTH ---
    case 'login':
        // Jika sudah login, jangan tampilkan halaman login, langsung lempar ke dashboard
        if (isset($_SESSION['role'])) {
            header("Location: /kampus-absensi/public/index.php?page=" . $_SESSION['role'] . "_dashboard");
            exit();
        }
        require __DIR__ . "/../views/auth/login.php";
        break;
    
    // --- MAHASISWA ---
    case 'mahasiswa_dashboard':
        cekRole('mahasiswa');
        require __DIR__ . "/../views/mahasiswa/dashboard.php";
        break;
    case 'mahasiswa_absen_hadir':
        cekRole('mahasiswa');
        require __DIR__ . "/../views/mahasiswa/absen_hadir.php";
        break;
    case 'mahasiswa_absen_izin_sakit':
        cekRole('mahasiswa');
        require __DIR__ . "/../views/mahasiswa/absen_izin_sakit.php";
        break;
    case 'mahasiswa_riwayat':
        cekRole('mahasiswa');
        require __DIR__ . "/../views/mahasiswa/riwayat_absen.php";
        break;
    case 'mahasiswa_detail_matkul':
        cekRole('mahasiswa');
        require __DIR__ . "/../views/mahasiswa/detail_matkul.php";
        break;
    
    // --- DOSEN ---
    case 'dosen_dashboard':
        cekRole('dosen');
        require __DIR__ . "/../views/dosen/dashboard.php";
        break;
    case 'dosen_absen':
        cekRole('dosen');
        require __DIR__ . "/../views/dosen/absen_dosen.php";
        break;
    case 'dosen_kelola_absen':
        cekRole('dosen');
        require __DIR__ . "/../views/dosen/kelola_absen_mhs.php";
        break;
    case 'dosen_riwayat':
        cekRole('dosen');
        require __DIR__ . "/../views/dosen/riwayat_absen.php";
        break;
    case 'dosen_jadwal':
        cekRole('dosen');
        require __DIR__ . "/../views/dosen/jadwal.php";
        break;
    
    // --- ADMIN ---
    case 'admin_dashboard':
        cekRole('admin');
        require __DIR__ . "/../views/admin/dashboard.php";
        break;
    case 'admin_kelola_mahasiswa':
        cekRole('admin');
        require __DIR__ . "/../views/admin/kelola_mahasiswa.php";
        break;
    case 'admin_kelola_dosen':
        cekRole('admin');
        require __DIR__ . "/../views/admin/kelola_dosen.php";
        break;
    case 'admin_kelola_matkul':
        cekRole('admin');
        require __DIR__ . "/../views/admin/kelola_matkul.php";
        break;
    case 'admin_kelola_jadwal':
    cekRole('admin');
    require __DIR__ . "/../views/admin/kelola_jadwal.php";
    break;
    case 'admin_upload_wajah':
        cekRole('admin');
        require __DIR__ . "/../views/admin/upload_wajah.php";
        break;
    case 'admin_rekap_absensi':
        cekRole('admin');
        require __DIR__ . "/../views/admin/rekap_absensi.php";
        break;
    
    
    // --- DEFAULT (JIKA URL TIDAK DIKENAL) ---
    default:
        if (isset($_SESSION['role'])) {
            header("Location: /kampus-absensi/public/index.php?page=" . $_SESSION['role'] . "_dashboard");
        } else {
            header("Location: /kampus-absensi/public/index.php?page=login");
        }
        exit();
}
?>