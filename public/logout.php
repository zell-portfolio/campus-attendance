<?php
session_start();
session_unset();
session_destroy();
header("Location: /kampus-absensi/public/index.php?page=login");
exit();
?>