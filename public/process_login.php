<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once __DIR__ . "/../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    echo "<h3>DEBUG LOGIN:</h3>";
    echo "Email yang diinput: " . htmlspecialchars($email) . "<br>";
    echo "Password yang diinput: " . htmlspecialchars($password) . "<br><hr>";

    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        // 1. Cari user berdasarkan email
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo "❌ <b>GAGAL:</b> Email tidak ditemukan di database.<br>";
            echo "<a href='/kampus-absensi/public/index.php?page=login'>Kembali ke Login</a>";
            exit();
        }
        
        echo "✅ User ditemukan: " . htmlspecialchars($user['nama']) . " (" . $user['role'] . ")<br>";
        
        // 2. Cek Password
        $passwordMatch = password_verify($password, $user['password']);
        
        if ($passwordMatch) {
            echo "✅ <b>Password Cocok!</b> Memproses login...<br>";
            
            // Simpan ke session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nip_nim'] = $user['nip_nim'];
            
            // Redirect sesuai role
            if ($user['role'] === 'admin') {
                header("Location: /kampus-absensi/public/index.php?page=admin_dashboard");
            } elseif ($user['role'] === 'dosen') {
                header("Location: /kampus-absensi/public/index.php?page=dosen_dashboard");
            } elseif ($user['role'] === 'mahasiswa') {
                header("Location: /kampus-absensi/public/index.php?page=mahasiswa_dashboard");
            }
            exit();
            
        } else {
            echo "❌ <b>GAGAL:</b> Password salah!<br>";
            echo "Hash di database: " . htmlspecialchars($user['password']) . "<br>";
            echo "<a href='/kampus-absensi/public/index.php?page=login'>Kembali ke Login</a>";
            exit();
        }
        
    } catch (Exception $e) {
        echo "❌ <b>ERROR DATABASE:</b> " . $e->getMessage();
    }
} else {
    header("Location: /kampus-absensi/public/index.php?page=login");
    exit();
}
?>