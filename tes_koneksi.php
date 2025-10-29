<?php
include 'koneksi.php';

if ($conn) {
    echo "Koneksi ke database adicomp BERHASIL!";
} else {
    echo "Koneksi ke database adicomp GAGAL: " . mysqli_connect_error();
}
?>