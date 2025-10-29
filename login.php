<?php
session_start();
if (isset($_SESSION['username'])) {
    if ($_SESSION['level'] == 1) {
        header("Location: admin/dashboard.php"); // Jika admin sudah login, langsung ke dashboard admin
    } elseif ($_SESSION['level'] == 2) {
        header("Location: karyawan/dashboard.php"); // Jika karyawan, langsung ke dashboard karyawan
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="login-container">
        <h2>Silahkan Login</h2>
        <form action="cek_login.php" method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let errorMessage = "<?php echo $_SESSION['login_error'] ?? ''; ?>";
            if (errorMessage) {
                Swal.fire({
                    icon: 'error',
                    title: 'Login Gagal!',
                    text: errorMessage,
                    confirmButtonColor: '#d33'
                });
                <?php unset($_SESSION['login_error']); ?>
            }
        });
    </script>
</body>
</html>
