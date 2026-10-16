<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Register Absensi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title text-center">Register</h3>
                    <?php if(isset($error)): ?> <div class="alert alert-danger"><?= $error ?></div> <?php endif; ?>
                    <form method="POST" action="/kampus-absensi/public/index.php?page=register">
                        <div class="mb-3"><label>NIP/NIM</label><input type="text" name="nip_nim" class="form-control" required></div>
                        <div class="mb-3"><label>Nama</label><input type="text" name="nama" class="form-control" required></div>
                        <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control" required></div>
                        <div class="mb-3"><label>Password</label><input type="password" name="password" class="form-control" required></div>
                        <div class="mb-3">
                            <label>Role</label>
                            <select name="role" class="form-select" required>
                                <option value="mahasiswa">Mahasiswa</option>
                                <option value="dosen">Dosen</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Register</button>
                    </form>
                    <p class="mt-3 text-center">Sudah punya akun? <a href="/kampus-absensi/public/index.php?page=login">Login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>