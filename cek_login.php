<?php
session_start();
include 'config/koneksi.php'; // Pastikan path benar

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Hindari SQL Injection
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Query database
    $query = "SELECT * FROM user WHERE username='$username' AND password='$password' LIMIT 1";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);

    if ($row) {
        // Set session user
        $_SESSION['id_user'] = $row['id_user'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['level'] = $row['level'];

        // Redirect berdasarkan level user
        if ($row['level'] == 1) {
            header("Location: admin/dashboard.php");
        } elseif ($row['level'] == 2) {
            header("Location: karyawan/dashboard.php");
        } else {
            $_SESSION['login_error'] = "Level user tidak dikenali!";
            header("Location: login.php");
        }
        exit();
    } else {
        $_SESSION['login_error'] = "Username atau password salah!";
        header("Location: login.php");
        exit();
    }
}
?>