<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Absensi Kampus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Fredoka+One&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Animated Background Elements */
        .bg-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }

        .shape {
            position: absolute;
            opacity: 0.15;
            animation: float 20s infinite ease-in-out;
        }

        .shape:nth-child(1) {
            top: 10%;
            left: 10%;
            font-size: 80px;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            top: 20%;
            right: 15%;
            font-size: 100px;
            animation-delay: 2s;
        }

        .shape:nth-child(3) {
            bottom: 20%;
            left: 20%;
            font-size: 90px;
            animation-delay: 4s;
        }

        .shape:nth-child(4) {
            bottom: 15%;
            right: 10%;
            font-size: 110px;
            animation-delay: 6s;
        }

        .shape:nth-child(5) {
            top: 50%;
            left: 50%;
            font-size: 70px;
            animation-delay: 8s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-30px) rotate(10deg);
            }
        }

        /* Login Container */
        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }

        /* Login Card */
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 50px 40px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Logo & Title */
        .logo-section {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo-icon {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .logo-icon i {
            font-size: 45px;
            color: white;
        }

        .login-title {
            font-family: 'Fredoka One', cursive;
            font-size: 2.2rem;
            color: #2d3748;
            margin-bottom: 8px;
            letter-spacing: 1px;
        }

        .login-subtitle {
            color: #718096;
            font-size: 0.95rem;
            font-weight: 400;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-label {
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 8px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-label i {
            color: #667eea;
        }

        .form-control {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }

        .form-control::placeholder {
            color: #a0aec0;
        }

        /* Submit Button */
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }

        .btn-login:active {
            transform: translateY(-1px);
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.5s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #e2e8f0;
        }

        .footer-text {
            color: #718096;
            font-size: 0.85rem;
            margin-bottom: 10px;
        }

        .footer-icons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 15px;
        }

        .footer-icons i {
            font-size: 1.5rem;
            color: #a0aec0;
            transition: all 0.3s ease;
        }

        .footer-icons i:hover {
            color: #667eea;
            transform: translateY(-3px);
        }

        /* Decorative Elements */
        .decoration {
            position: absolute;
            font-size: 60px;
            opacity: 0.1;
            z-index: 0;
        }

        .decoration-1 {
            top: -30px;
            right: -30px;
            color: #667eea;
        }

        .decoration-2 {
            bottom: -30px;
            left: -30px;
            color: #764ba2;
        }

        /* Responsive */
        @media (max-width: 576px) {
            .login-card {
                padding: 40px 25px;
            }

            .login-title {
                font-size: 1.8rem;
            }

            .logo-icon {
                width: 75px;
                height: 75px;
            }

            .logo-icon i {
                font-size: 38px;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background Shapes -->
    <div class="bg-shapes">
        <div class="shape"></div>
        <div class="shape">🎓</div>
        <div class="shape">✏️</div>
        <div class="shape">📝</div>
        <div class="shape">🎒</div>
    </div>

    <!-- Login Container -->
    <div class="login-container">
        <div class="login-card">
            <!-- Decorative Elements -->
            <div class="decoration decoration-1">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="decoration decoration-2">
                <i class="fas fa-book-open"></i>
            </div>

            <!-- Logo & Title -->
            <div class="logo-section">
                <div class="logo-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h1 class="login-title">SmartAbsen Kampus</h1>
                <p class="login-subtitle">Selamat datang! Silakan login untuk melanjutkan</p>
            </div>

            <!-- Alert Messages -->
            <?php if(isset($_GET['logged_out'])): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <span>Anda telah berhasil logout. Sampai jumpa!</span>
                </div>
            <?php endif; ?>

            <?php if(isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <!-- Login Form -->
<form method="POST" action="/kampus-absensi/public/index.php?page=login">
    <div class="form-group">
        <label class="form-label">
            <i class="fas fa-envelope"></i> Email
        </label>
        <input type="email" class="form-control" name="email" required autofocus placeholder="nama@email.com">
    </div>

    <div class="form-group">
        <label class="form-label">
            <i class="fas fa-lock"></i> Password
        </label>
        <input type="password" class="form-control" name="password" required placeholder="Masukkan password">
    </div>

    <?php if(isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <button type="submit" class="btn-login">
        <i class="fas fa-sign-in-alt"></i> Login
    </button>
</form>

            <!-- Footer -->
            <div class="login-footer">
                <p class="footer-text">Sistem Absensi Berbasis Face Recognition</p>
                <div class="footer-icons">
                    <i class="fas fa-book" title="Belajar"></i>
                    <i class="fas fa-graduation-cap" title="Pendidikan"></i>
                    <i class="fas fa-laptop" title="Teknologi"></i>
                    <i class="fas fa-users" title="Komunitas"></i>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>