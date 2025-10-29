<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['level'] != 1) {
    header("Location: ../login.php");
    exit();
}

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

// Ambil data barang untuk dropdown
$barang = mysqli_query($conn, "SELECT * FROM barang");

// Inisialisasi variabel untuk menyimpan hasil perbandingan
$hasil_perbandingan = '';
$data_wma = null;
$data_ses = null;
$metode_terbaik = '';
$perbedaan_mse = '';
$chart_data = '';

// Proses perbandingan
if (isset($_POST['bandingkan'])) {
    $id_barang = $_POST['id_barang'];
    
    // Ambil data prediksi terbaru untuk WMA dan SES berdasarkan barang yang dipilih
    $query_wma = "SELECT p.*, b.nama_barang, DATE_FORMAT(p.waktu_prediksi, '%d %M %Y %H:%i') as waktu_indo
                  FROM prediksi p
                  JOIN barang b ON p.id_barang = b.id_barang
                  WHERE p.id_barang = '$id_barang' AND p.metode = 'WMA'
                  ORDER BY p.waktu_prediksi DESC
                  LIMIT 1";
    
    $query_ses = "SELECT p.*, b.nama_barang, DATE_FORMAT(p.waktu_prediksi, '%d %M %Y %H:%i') as waktu_indo
                  FROM prediksi p
                  JOIN barang b ON p.id_barang = b.id_barang
                  WHERE p.id_barang = '$id_barang' AND p.metode = 'SES'
                  ORDER BY p.waktu_prediksi DESC
                  LIMIT 1";
    
    $result_wma = mysqli_query($conn, $query_wma);
    $result_ses = mysqli_query($conn, $query_ses);
    
    if (mysqli_num_rows($result_wma) > 0 && mysqli_num_rows($result_ses) > 0) {
        $data_wma = mysqli_fetch_assoc($result_wma);
        $data_ses = mysqli_fetch_assoc($result_ses);
        
        // Konversi format tanggal ke Indonesia
        $data_wma['waktu_indo'] = translateMonthName($data_wma['waktu_indo']);
        $data_ses['waktu_indo'] = translateMonthName($data_ses['waktu_indo']);
        
        // Hitung perbedaan MSE
        $mse_wma = floatval($data_wma['mse']);
        $mse_ses = floatval($data_ses['mse']);
        $perbedaan_mse = abs($mse_wma - $mse_ses);
        $perbedaan_persen = 0;
        
        // Persentase perbedaan terhadap nilai terkecil
        if ($mse_wma > 0 && $mse_ses > 0) {
            $nilai_min = min($mse_wma, $mse_ses);
            $perbedaan_persen = round(($perbedaan_mse / $nilai_min) * 100, 2);
        }
        
        // Tentukan metode terbaik berdasarkan MSE terendah
        if ($mse_wma < $mse_ses) {
            $metode_terbaik = 'WMA';
        } elseif ($mse_ses < $mse_wma) {
            $metode_terbaik = 'SES';
        } else {
            $metode_terbaik = 'Keduanya sama';
        }
        
        // Data untuk grafik perbandingan
        $chart_data = [
            ['metode' => 'WMA', 'mse' => $mse_wma],
            ['metode' => 'SES', 'mse' => $mse_ses]
        ];
        
        // Simpan hasil perbandingan ke database
        $waktu = date('Y-m-d H:i:s');
        
        // Konversi hasil perbandingan menjadi format JSON sebelum disimpan
        $hasil = [
            'metode_terbaik' => $metode_terbaik,
            'mse_wma' => $mse_wma,
            'mse_ses' => $mse_ses,
            'perbedaan_mse' => $perbedaan_mse,
            'perbedaan_persen' => $perbedaan_persen,
            'prediksi_wma' => $data_wma['hasil'],
            'prediksi_ses' => $data_ses['hasil'],
            'id_prediksi_wma' => $data_wma['id_prediksi'],
            'id_prediksi_ses' => $data_ses['id_prediksi']
        ];
        
        $hasil_json = json_encode($hasil);
        
        // Simpan ke database - tabel perbandingan
        mysqli_query($conn, "INSERT INTO perbandingan (id_prediksi, waktu_perbandingan, hasil_perbandingan) 
                     VALUES ('{$data_wma['id_prediksi']}', '$waktu', '$hasil_json')");
    } else {
        $hasil_perbandingan = "Data prediksi tidak lengkap. Pastikan Anda telah melakukan prediksi dengan kedua metode (WMA dan SES) untuk barang yang dipilih.";
    }
}

// Ambil data riwayat perbandingan
$query_riwayat = "SELECT 
                     pb.id_perbandingan,
                     b.nama_barang, 
                     DATE_FORMAT(pb.waktu_perbandingan, '%d %M %Y %H:%i') as waktu_eng,
                     pb.hasil_perbandingan
                  FROM perbandingan pb
                  JOIN prediksi p ON pb.id_prediksi = p.id_prediksi
                  JOIN barang b ON p.id_barang = b.id_barang
                  ORDER BY pb.waktu_perbandingan DESC
                  LIMIT 50"; // Batasi 50 data terakhir

$riwayat = mysqli_query($conn, $query_riwayat);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bandingkan Metode</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/prediksi.css">
    <link rel="stylesheet" href="../css/bandingkan.css">
    <!-- Tambahkan Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="container">
    <!-- Sidebar -->
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
                <li><a href="kelola_user.php"><span class="icon"><ion-icon name="people-outline"></ion-icon></span>
                        <span class="title">Kelola User</span></a></li>
                <li><a href="prediksi.php"><span class="icon"><ion-icon name="trending-up-outline"></ion-icon></span>
                        <span class="title">Prediksi</span></a></li>
                <li><a href="bandingkan.php"><span class="icon"><ion-icon name="help-outline"></ion-icon></span>
                        <span class="title">Bandingkan</span></a></li>
                <li><a href="../logout.php"><span class="icon"><ion-icon name="log-out-outline"></ion-icon></span>
                        <span class="title">Logout</span></a></li>
            </ul>
    </div>

    <!-- Main Content -->
    <div class="main">
        <div class="topbar">
            <div class="toggle">
                <ion-icon name="menu-outline"></ion-icon>
            </div>
        </div>

        <div class="content">
            <div class="tabs">
                <button class="tab-button active" onclick="openTab('bandingkan-tab')">Bandingkan Metode</button>
                <button class="tab-button" onclick="openTab('riwayat-tab')">Riwayat Perbandingan</button>
            </div>

            <div id="bandingkan-tab" class="tab-content active">
                <h2>Perbandingan Metode WMA dan SES</h2>
                <form method="post">
                    <label for="id_barang">Pilih Barang:</label>
                    <select name="id_barang" id="id_barang" required>
                        <option value="">-- Pilih Barang --</option>
                        <?php 
                        // Reset pointer mysqli result
                        mysqli_data_seek($barang, 0);
                        while ($row = mysqli_fetch_assoc($barang)) : ?>
                            <option value="<?= $row['id_barang'] ?>" <?= isset($_POST['id_barang']) && $_POST['id_barang'] == $row['id_barang'] ? 'selected' : '' ?>>
                                <?= $row['nama_barang'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <button type="submit" name="bandingkan">BANDINGKAN</button>
                </form>

                <?php if (!empty($hasil_perbandingan)) : ?>
                    <div class="alert alert-warning">
                        <?= $hasil_perbandingan ?>
                    </div>
                <?php elseif ($data_wma && $data_ses) : ?>
                    <div class="comparison-card">
                        <h3>Hasil Perbandingan untuk <?= htmlspecialchars($data_wma['nama_barang']) ?></h3>
                        
                        <div class="comparison-result">
                            <!-- Card WMA -->
                            <div class="method-card <?= ($metode_terbaik == 'WMA') ? 'best-method' : '' ?>">
                                <h3>
                                    Metode WMA
                                    <?php if ($metode_terbaik == 'WMA') : ?>
                                        <span class="winner-badge">Terbaik</span>
                                    <?php endif; ?>
                                </h3>
                                <div class="method-detail">
                                    <p>
                                        <span class="label">MSE:</span>
                                        <span class="value"><?= $data_wma['mse'] ?></span>
                                    </p>
                                    <p>
                                        <span class="label">Hasil Prediksi:</span>
                                        <span class="value"><?= $data_wma['hasil'] ?></span>
                                    </p>
                                    <p>
                                        <span class="label">Waktu Prediksi:</span>
                                        <span class="value"><?= $data_wma['waktu_indo'] ?></span>
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Card SES -->
                            <div class="method-card <?= ($metode_terbaik == 'SES') ? 'best-method' : '' ?>">
                                <h3>
                                    Metode SES
                                    <?php if ($metode_terbaik == 'SES') : ?>
                                        <span class="winner-badge">Terbaik</span>
                                    <?php endif; ?>
                                </h3>
                                <div class="method-detail">
                                    <p>
                                        <span class="label">MSE:</span>
                                        <span class="value"><?= $data_ses['mse'] ?></span>
                                    </p>
                                    <p>
                                        <span class="label">Hasil Prediksi:</span>
                                        <span class="value"><?= $data_ses['hasil'] ?></span>
                                    </p>
                                    <p>
                                        <span class="label">Waktu Prediksi:</span>
                                        <span class="value"><?= $data_ses['waktu_indo'] ?></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="conclusion">
                            <h4>Kesimpulan:</h4>
                            <?php if ($metode_terbaik == 'Keduanya sama') : ?>
                                <p>Kedua metode memiliki nilai MSE yang sama (<?= $data_wma['mse'] ?>), sehingga keduanya memiliki tingkat akurasi yang sama dalam memprediksi penjualan <?= htmlspecialchars($data_wma['nama_barang']) ?>.</p>
                            <?php else : ?>
                                <p>Berdasarkan perbandingan MSE, metode <strong><?= $metode_terbaik ?></strong> lebih akurat untuk memprediksi penjualan <?= htmlspecialchars($data_wma['nama_barang']) ?> dengan selisih MSE sebesar <?= round($perbedaan_mse, 4) ?> (<?= $perbedaan_persen ?>% lebih baik).</p>
                                
                                <?php if ($metode_terbaik == 'WMA') : ?>
                                    <p>Metode WMA memberikan hasil prediksi yang lebih akurat karena memberikan bobot berbeda untuk setiap periode, dengan periode terbaru mendapat bobot lebih besar.</p>
                                <?php else : ?>
                                    <p>Metode SES memberikan hasil prediksi yang lebih akurat karena menggunakan parameter alpha untuk menyesuaikan pengaruh data terbaru dan pola sebelumnya.</p>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <p>Hasil prediksi untuk bulan berikutnya:
                                <ul>
                                    <li>WMA: <?= $data_wma['hasil'] ?></li>
                                    <li>SES: <?= $data_ses['hasil'] ?></li>
                                </ul>
                            </p>
                        </div>
                        
                        <!-- Grafik perbandingan MSE -->
                        <div class="chart-container">
                            <canvas id="mseChart" data-wma="<?= $data_wma['mse'] ?>" data-ses="<?= $data_ses['mse'] ?>"></canvas>
                        </div>
                    </div>                    
                <?php endif; ?>
            </div>
            
            <div id="riwayat-tab" class="tab-content">
            <h2>Riwayat Perbandingan</h2>
            
            <?php if (mysqli_num_rows($riwayat) > 0): ?>
            <table class="riwayat-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Barang</th>
                        <th>Metode Terbaik</th>
                        <th>Selisih MSE</th>
                        <th>Waktu Perbandingan</th>
                        <th>Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($riwayat)):
                        // Pastikan hasil perbandingan adalah JSON yang valid
                        $hasil = json_decode($row['hasil_perbandingan'], true);
                        
                        // Konversi waktu ke format Indonesia
                        $waktu_indo = translateMonthName($row['waktu_eng']);
                        
                        $badge_class = '';
                        $metode_terbaik = '-';
                        $selisih_mse = '-';
                        $selisih_persen = '';
                        
                        // Periksa apakah data JSON valid dan berisi informasi yang dibutuhkan
                        if ($hasil && is_array($hasil)) {
                            if (isset($hasil['metode_terbaik'])) {
                                $metode_terbaik = htmlspecialchars($hasil['metode_terbaik']);
                                
                                if ($metode_terbaik == 'WMA') {
                                    $badge_class = 'badge-wma';
                                } elseif ($metode_terbaik == 'SES') {
                                    $badge_class = 'badge-ses';
                                }
                            }
                            
                            if (isset($hasil['perbedaan_mse'])) {
                                $selisih_mse = round($hasil['perbedaan_mse'], 4);
                                if (isset($hasil['perbedaan_persen'])) {
                                    $selisih_persen = " ({$hasil['perbedaan_persen']}%)";
                                }
                            }
                        }
                    ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                        <td>
                            <?php if ($metode_terbaik != '-'): ?>
                                <span class="badge <?= $badge_class ?>"><?= $metode_terbaik ?></span>
                            <?php else: ?>
                                <span><?= $metode_terbaik ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($selisih_mse != '-'): ?>
                                <?= $selisih_mse . $selisih_persen ?>
                            <?php else: ?>
                                <span><?= $selisih_mse ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($waktu_indo) ?></td>
                        <td class="tindakan">
                            <button class="btn-hapus" onclick="hapusPerbandingan(<?= $row['id_perbandingan'] ?>)">Hapus</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>Belum ada data riwayat perbandingan.</p>
            <?php endif; ?>
        </div>
        </div>
    </div>
</div>

<script src="../js/dashboard.js"></script>
<script src="../js/bandingkan.js"></script>
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>