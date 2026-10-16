<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak - 403</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg">
                <div class="card-body text-center p-5">
                    <h1 class="display-1 text-danger mb-3">403</h1>
                    <h2 class="mb-4">Akses Ditolak!</h2>
                    <p class="lead text-muted mb-4">
                        Maaf, Anda tidak memiliki izin untuk mengakses halaman ini.
                    </p>
                    <div class="d-grid gap-2">
                        <a href="/kampus-absensi/public/index.php?page=dashboard" class="btn btn-primary btn-lg">
                            Kembali ke Dashboard
                        </a>
                        <a href="/kampus-absensi/public/index.php?page=logout" class="btn btn-outline-secondary">
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>