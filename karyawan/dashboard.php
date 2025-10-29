<?php
session_start();
// Perbaikan autentikasi session
if (!isset($_SESSION['username']) || $_SESSION['level'] != 2) {
    header("Location: ../login.php");
    exit();
}

// Koneksi ke database
include '../config/koneksi.php';

// Set zona waktu ke Indonesia (WIB)
date_default_timezone_set('Asia/Jakarta');

// Fungsi untuk menerjemahkan nama bulan dari Inggris ke Indonesia
function translateMonthName($month_name) {
    $en_months = [
        'January', 'February', 'March', 'April', 'May', 'June', 
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    $id_months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    return str_replace($en_months, $id_months, $month_name);
}

// Query untuk mengambil data penjualan
$query = "SELECT DATE_FORMAT(bulan, '%M %Y') AS nama_bulan, SUM(jumlah) AS total_penjualan
FROM penjualan
GROUP BY YEAR(bulan), MONTH(bulan)
ORDER BY YEAR(bulan), MONTH(bulan)";
$result = mysqli_query($conn, $query);

$bulan = [];
$total_penjualan = [];

while ($row = mysqli_fetch_assoc($result)) {
    // Terjemahkan nama bulan ke Bahasa Indonesia
    $bulan[] = translateMonthName($row['nama_bulan']); 
    $total_penjualan[] = $row['total_penjualan'];
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Karyawan Dashboard</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="container">
        <div class="navigation">
            <ul>
                <li><a href="#"><span class="icon"><ion-icon name="desktop-outline"></ion-icon></span>
                        <span class="title"><strong>ADI COMP</strong></span>
                <li><a href="dashboard.php"><span class="icon"><ion-icon name="home-outline"></ion-icon></span>
                        <span class="title">Dashboard</span></a></li>
                <li><a href="kelola_barang.php" class="active"><span class="icon"><ion-icon name="cube-outline"></ion-icon></span>
                        <span class="title">Kelola Barang</span></a></li>
                <li><a href="kelola_data.php"><span class="icon"><ion-icon name="file-tray-full-outline"></ion-icon></span>
                        <span class="title">Kelola Data</span></a></li>
                <li><a href="../logout.php"><span class="icon"><ion-icon name="log-out-outline"></ion-icon></span>
                        <span class="title">Logout</span></a></li>
            </ul>
        </div>
        <div class="main">
            <div class="topbar">
                <div class="toggle">
                    <ion-icon name="menu-outline"></ion-icon>
                </div>
            </div>
            <div class="content">
                <h2>Welcome to Karyawan Dashboard</h2>
                <p>Ini adalah halaman utama karyawan dashboard yang berisi grafik total penjualan per-bulan.</p>
                <div style="width: 80%; margin: auto;">
                    <canvas id="penjualanChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <script>
        var ctx = document.getElementById('penjualanChart').getContext('2d');
        var penjualanChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($bulan); ?>,
                datasets: [{
                    label: 'Total Penjualan',
                    data: <?php echo json_encode($total_penjualan); ?>,
                    backgroundColor: 'rgba(220, 53, 69, 0.5)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
    <script src="../js/dashboard.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>

</html>