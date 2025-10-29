<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['level'] != 1) {
    header("Location: ../login.php");
    exit();
}

// Style CSS untuk grafik
echo "<style>
    .detail-container {
        width: 100%;
        margin-bottom: 20px;
    }
    .detail-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    .detail-table th {
        text-align: left;
        padding: 10px;
        border-bottom: 1px solid #ddd;
    }
    .detail-table td {
        padding: 10px;
        border-bottom: 1px solid #ddd;
    }
    .chart-container {
        width: 100%;
        height: 300px;
        border: 1px solid #ddd;
        position: relative;
        padding: 10px;
        margin-bottom: 20px;
    }
    .chart-bars {
        display: flex;
        justify-content: space-between;
        height: 250px;
    }
    .bar-container {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .bar {
        margin-top: auto;
        width: 30px;
    }
    .regular-bar {
        background-color: #36a2eb;
    }
    .prediction-bar {
        background-color: #ff6384;
    }
    .bar-label {
        font-size: 10px;
        word-wrap: break-word;
        width: 100%;
        text-align: center;
        margin-top: 5px;
    }
    .bar-value {
        font-weight: bold;
        margin-top: 5px;
    }
</style>";

include '../config/koneksi.php';

// Set zona waktu ke Indonesia (WIB)
date_default_timezone_set('Asia/Jakarta');

// Tambahkan fungsi translateMonthName ke file ini
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

// Cek apakah ID valid
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_prediksi = $_GET['id'];
    
    // Ambil detail prediksi
    $query = "SELECT p.*, b.nama_barang, 
            DATE_FORMAT(p.waktu_prediksi, '%d %M %Y %H:%i') as waktu_eng,
            DATE_FORMAT(p.waktu_prediksi, '%Y-%m-%d %H:%i:%s') as waktu_raw 
            FROM prediksi p
            JOIN barang b ON p.id_barang = b.id_barang
            WHERE p.id_prediksi = $id_prediksi";
    
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $prediksi = mysqli_fetch_assoc($result);

        // Terjemahkan waktu prediksi ke bahasa Indonesia
        $timestamp_prediksi = strtotime($prediksi['waktu_raw']);
        $waktu_indo = date('d F Y H:i', $timestamp_prediksi);
        $waktu_indo = translateMonthName($waktu_indo);
        
        // Ambil data penjualan barang terpilih untuk grafik
        $id_barang = $prediksi['id_barang'];
        $data_penjualan = mysqli_query($conn, "SELECT *, DATE_FORMAT(bulan, '%M %Y') as nama_bulan_eng,
                                              DATE_FORMAT(bulan, '%Y-%m-%d') as bulan_raw 
                                              FROM penjualan 
                                              WHERE id_barang='$id_barang' 
                                              ORDER BY bulan ASC");

        $nilai_penjualan = [];
        $bulan_penjualan = [];
        $data_bulan_raw = [];

        while ($row = mysqli_fetch_assoc($data_penjualan)) {
            $nilai_penjualan[] = $row['jumlah'];
            $data_bulan_raw[] = $row['bulan_raw']; // Simpan semua data bulan raw

            // Terjemahkan nama bulan ke bahasa Indonesia
            $timestamp_bulan = strtotime($row['bulan_raw']);
            $nama_bulan_indo = date('F Y', $timestamp_bulan);
            $nama_bulan_indo = translateMonthName($nama_bulan_indo);
            $bulan_penjualan[] = $nama_bulan_indo;
        }

        // Tentukan bulan prediksi (bulan setelah bulan terakhir)
        $bulan_terakhir = end($bulan_penjualan);
        reset($bulan_penjualan); // Reset pointer array

         // Dapatkan bulan terakhir dalam format raw
         $bulan_terakhir_raw = end($data_bulan_raw);
         reset($data_bulan_raw);

        // Tambah 1 bulan
        $timestamp_bulan_berikutnya = strtotime('+1 month', strtotime($bulan_terakhir_raw));
        $bulan_berikutnya = date('F Y', $timestamp_bulan_berikutnya);

        // Terjemahkan bulan prediksi ke bahasa Indonesia
        $bulan_prediksi = translateMonthName($bulan_berikutnya);

        // Untuk data chart
        $nilai_penjualan_chart = $nilai_penjualan;
        $bulan_penjualan_chart = $bulan_penjualan;

        // Tambahkan bulan prediksi dan nilainya
        $bulan_penjualan_chart[] = $bulan_prediksi . " (Prediksi)";
        $nilai_penjualan_chart[] = $prediksi['hasil'];
        
        // Konversi ke format JSON untuk grafik (jika dibutuhkan nanti)
        $nilai_json = json_encode($nilai_penjualan_chart);
        $bulan_json = json_encode($bulan_penjualan_chart);
        
        // Output detail
        echo "<div class='detail-container'>";
        echo "<h2>Detail Prediksi</h2>";
        echo "<table class='detail-table'>";
        echo "<tr><th>Nama Barang</th><td>" . htmlspecialchars($prediksi['nama_barang']) . "</td></tr>";
        echo "<tr><th>Metode</th><td>" . htmlspecialchars($prediksi['metode']) . "</td></tr>";
        echo "<tr><th>Hasil Prediksi</th><td>" . htmlspecialchars($prediksi['hasil']) . "</td></tr>";
        echo "<tr><th>MSE</th><td>" . htmlspecialchars($prediksi['mse']) . "</td></tr>";
        echo "<tr><th>Waktu Prediksi</th><td>" . htmlspecialchars($waktu_indo) . "</td></tr>";
        echo "</table>";
        
        // Tambahkan grafik CSS
        echo "<h3>Grafik Penjualan dan Prediksi</h3>";
        echo "<div class='chart-container'>";
        echo "<div class='chart-bars'>";
        
        $max_nilai = max($nilai_penjualan_chart);
        $min_nilai = min($nilai_penjualan_chart);
        $range = $max_nilai - $min_nilai;
        
        foreach($nilai_penjualan_chart as $index => $nilai) {
            $height = ($nilai - $min_nilai) / $range * 200;
            if ($height < 5) $height = 5; // Minimal height untuk visibilitas
            $bar_class = ($index == count($nilai_penjualan_chart) - 1) ? 'prediction-bar' : 'regular-bar';
            $width = 100 / count($nilai_penjualan_chart);
            
            echo "<div class='bar-container' style='width: {$width}%;'>";
            echo "<div class='bar {$bar_class}' style='height: {$height}px;'></div>";
            echo "<div class='bar-label'>" . htmlspecialchars($bulan_penjualan_chart[$index]) . "</div>";
            echo "<div class='bar-value'>" . htmlspecialchars($nilai) . "</div>";
            echo "</div>";
        }
        
        echo "</div>"; // .chart-bars
        echo "</div>"; // .chart-container
        echo "</div>"; // .detail-container
        
        
        
    } else {
        echo "<p>Data prediksi tidak ditemukan.</p>";
    }
} else {
    echo "<p>Parameter ID tidak valid.</p>";
}
?>