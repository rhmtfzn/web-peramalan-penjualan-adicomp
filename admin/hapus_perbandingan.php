<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['level'] != 1) {
    header("Location: ../login.php");
    exit();
}

include '../config/koneksi.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_perbandingan = $_GET['id'];
    
    // Hapus data perbandingan
    $query = "DELETE FROM perbandingan WHERE id_perbandingan = $id_perbandingan";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        // Sukses
        $_SESSION['pesan'] = "Data perbandingan berhasil dihapus!";
    } else {
        // Gagal
        $_SESSION['pesan'] = "Gagal menghapus data perbandingan: " . mysqli_error($conn);
    }
} else {
    $_SESSION['pesan'] = "Parameter ID tidak valid!";
}

// Redirect kembali ke halaman perbandingan
header("Location: bandingkan.php");
exit();
?>