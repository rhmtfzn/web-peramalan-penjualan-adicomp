<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['level'] != 1) {
    header("Location: ../login.php");
    exit();
}

include '../config/koneksi.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_prediksi = $_GET['id'];
    
    // Hapus data prediksi
    $query = "DELETE FROM prediksi WHERE id_prediksi = $id_prediksi";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        // Sukses
        $_SESSION['pesan'] = "Data prediksi berhasil dihapus!";
    } else {
        // Gagal
        $_SESSION['pesan'] = "Gagal menghapus data prediksi: " . mysqli_error($conn);
    }
} else {
    $_SESSION['pesan'] = "Parameter ID tidak valid!";
}

// Redirect kembali ke halaman prediksi
header("Location: prediksi.php");
exit();
?>