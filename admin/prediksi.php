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

function generateRekomendasi($stok, $prediksi) {
    $selisih = $stok - $prediksi;
    
    if ($selisih > 0) {
        return "Stok lebih dari prediksi: " . abs($selisih) . " unit";
    } elseif ($selisih < 0) {
        return "Stok kurang dari prediksi, disarankan untuk menambah " . abs($selisih) . " unit";
    } else {
        return "Stok sesuai dengan prediksi";
    }
}

// Ambil data barang untuk dropdown
$barang = mysqli_query($conn, "SELECT id_barang, nama_barang, stok FROM barang");

// Proses prediksi WMA
$hasil_prediksi = '';
$mse = '';
if (isset($_POST['prediksi'])) {
    $id_barang = $_POST['id_barang'];
    // Ambil data stok barang yang dipilih
    $query_stok = mysqli_query($conn, "SELECT stok FROM barang WHERE id_barang='$id_barang'");
    $data_stok = mysqli_fetch_assoc($query_stok);
    $stok_barang = $data_stok['stok'];
    $metode = $_POST['metode'];

    // Ambil data penjualan barang terpilih - DIURUTKAN BERDASARKAN BULAN
    $data_penjualan = mysqli_query($conn, "SELECT *, DATE_FORMAT(bulan, '%Y-%m-%d') as bulan_raw FROM penjualan WHERE id_barang='$id_barang' ORDER BY bulan ASC");

    $nilai = [];
    $bulan = [];
    $bulan_tampil = []; // Array untuk menyimpan format tampilan bulan

    while ($row = mysqli_fetch_assoc($data_penjualan)) {
        $nilai[] = $row['jumlah'];
        $bulan[] = $row['bulan_raw']; // Untuk komputasi
        
        // Format bulan untuk tampilan dalam bahasa Indonesia
        $timestamp = strtotime($row['bulan']);
        $nama_bulan = date('F Y', $timestamp); // Format: January 2024
        $nama_bulan = translateMonthName($nama_bulan);
        $bulan_tampil[] = $nama_bulan;
    }

    // Prediksi WMA
    if ($metode == 'wma') {
        $proses_perhitungan = '';
        $error_detail = '';

        $periode = 3; // jumlah bulan untuk perhitungan
        
        $bobot = [1, 2, 3]; // Bobot tetap: periode terlama ke terbaru
        
        $jumlah_data = count($nilai);

        if ($jumlah_data >= $periode) {
            $total_bobot = array_sum($bobot);
            
            // ===== BAGIAN 1: PREDIKSI UNTUK PERIODE BERIKUTNYA =====
            // Ambil 3 bulan terakhir untuk prediksi bulan berikutnya
            $prediksi = 0;
            
            // Hitung prediksi untuk periode selanjutnya (bulan berikutnya)
            for ($i = 0; $i < $periode; $i++) {
                $index = $jumlah_data - $periode + $i;
                $prediksi += $nilai[$index] * $bobot[$i];
            }
            
            $prediksi /= $total_bobot;
            $hasil_prediksi = round($prediksi, 4);
            $rekomendasi = generateRekomendasi($stok_barang, $hasil_prediksi);
            
            // ===== BAGIAN 2: PROSES PERHITUNGAN MSE =====
            $prediksi_array = [];
            $error_array = [];
            $error_kuadrat_array = [];
            $error_total = 0;
            $n = 0;
            
            // PERBAIKAN KRITIS: Perhitungan prediksi untuk setiap bulan mulai dari bulan ke-4
            // Debug: Tampilkan nilai awal untuk memastikan urutan benar
            // echo "Nilai bulan: ";
            // print_r($nilai);
            
            // Untuk bulan ke-4 (April) dan seterusnya
            for ($i = $periode; $i < $jumlah_data; $i++) {
                $prediksi_temp = 0;
                
                // Ambil 3 bulan sebelumnya untuk menghitung prediksi
                for ($j = 0; $j < $periode; $j++) {
                    // Indeks untuk 3 bulan sebelum bulan ke-i
                    $idx = $i - $periode + $j;
                    $prediksi_temp += $nilai[$idx] * $bobot[$j];
                }
                
                $prediksi_temp /= $total_bobot;
                $aktual = $nilai[$i];
                
                // Untuk bulan April (indeks 3 jika dimulai dari 0), data yang digunakan adalah:
                // Januari (indeks 0): $nilai[0] * $bobot[0] = 43 * 1 = 43
                // Februari (indeks 1): $nilai[1] * $bobot[1] = 36 * 2 = 72
                // Maret (indeks 2): $nilai[2] * $bobot[2] = 40 * 3 = 120
                // Total: (43 + 72 + 120) / 6 = 235 / 6 = 39.17
                
                $error = $aktual - $prediksi_temp;
                $error_kuadrat = pow($error, 2);
                
                // Simpan prediksi dan error untuk ditampilkan
                $prediksi_array[$i] = round($prediksi_temp, 4);
                $error_array[$i] = round($error, 4);
                $error_kuadrat_array[$i] = $error_kuadrat; // Biarkan aslinya dulu untuk akurasi
                
                $error_total += $error_kuadrat;
                $n++;
            }
            
            // Hitung MSE
            $mse = $n > 0 ? round($error_total / $n, 4) : 0;
            
            // ===== BAGIAN 3: TAMPILKAN PROSES PERHITUNGAN WMA =====
            $proses_perhitungan .= "<h4>📊 Proses Perhitungan WMA</h4>";
            $proses_perhitungan .= "<p>Menggunakan periode $periode bulan terakhir dengan bobot " . implode(", ", $bobot) . "</p>";
            $proses_perhitungan .= "<table border='1' cellpadding='5' cellspacing='0'>";
            $proses_perhitungan .= "<tr><th>Periode</th><th>Bulan</th><th>Penjualan</th><th>Bobot</th><th>Penjualan × Bobot</th></tr>";
            
            $total_pxb = 0;
            for ($i = 0; $i < $periode; $i++) {
                $index = $jumlah_data - $periode + $i;
                $penjualan = $nilai[$index];
                $bobot_item = $bobot[$i];
                $pxb = $penjualan * $bobot_item;
                $total_pxb += $pxb;
                
                $proses_perhitungan .= "<tr>
                <td>" . ($index + 1) . "</td>
                <td>" . (isset($bulan_tampil[$index]) ? $bulan_tampil[$index] : 'Bulan ' . ($index + 1)) . "</td>
                <td>$penjualan</td>
                <td>$bobot_item</td>
                <td>$pxb</td>
                </tr>";
            }
            
            $proses_perhitungan .= "</table>";
            $proses_perhitungan .= "<p><strong>Rumus WMA = (Σ Penjualan × Bobot) ÷ (Σ Bobot)</strong></p>";
            $proses_perhitungan .= "<p><strong>WMA = $total_pxb ÷ $total_bobot = $hasil_prediksi</strong></p>";
            
            // ===== BAGIAN 4: TAMPILKAN PERHITUNGAN ERROR (MSE) =====
            $error_detail .= "<h4>📉 Perhitungan MSE</h4>";
            $error_detail .= "<p>MSE dihitung dari selisih kuadrat antara nilai aktual dan nilai prediksi</p>";
            $error_detail .= "<table border='1' cellpadding='5' cellspacing='0'>";
            $error_detail .= "<tr>
                <th>Periode</th>
                <th>Bulan</th>
                <th>Data Aktual</th>
                <th>Prediksi WMA</th>
                <th>Error</th>
                <th>Error²</th>
            </tr>";
            
            // Detail perhitungan untuk setiap periode
            for ($i = $periode; $i < $jumlah_data; $i++) {
                $aktual = $nilai[$i];
                $prediksi_nilai = $prediksi_array[$i];
                $error_nilai = $error_array[$i];
                $error_kuadrat = $error_kuadrat_array[$i];
                
                $error_detail .= "<tr>
                    <td>" . ($i + 1) . "</td>
                    <td>" . (isset($bulan_tampil[$i]) ? $bulan_tampil[$i] : 'Bulan ' . ($i + 1)) . "</td>
                    <td>$aktual</td>
                    <td>$prediksi_nilai</td>
                    <td>$error_nilai</td>
                    <td>" . round($error_kuadrat, 4) . "</td>
                </tr>";
            }
            
            $error_detail .= "</table>";
            $error_detail .= "<p><strong>MSE = Σ(Error²) ÷ n = " . round($error_total, 4) . " ÷ $n = $mse</strong></p>";
            
            // Simpan ke database
            $waktu = date('Y-m-d H:i:s');
            mysqli_query($conn, "INSERT INTO prediksi (id_barang, metode, waktu_prediksi, hasil, mse) VALUES ('$id_barang', 'WMA', '$waktu', '$hasil_prediksi', '$mse')");
        } else {
            $hasil_prediksi = "Data penjualan kurang dari $periode bulan.";
        }
    }
    
    //prediksi SES
    elseif ($metode == 'ses') {
        $proses_perhitungan = '';
        $error_detail = '';
        $jumlah_data = count($nilai);
        $periode = 3; // Jumlah periode yang sama dengan WMA
        
        $alpha = 0.2; // Nilai alpha tetap
        
        if ($jumlah_data >= $periode) {
            // Inisialisasi untuk SES
            $prediksi_array = [];
            $error_array = [];
            $error_kuadrat_array = [];
            $error_total = 0;
            $n = 0;
            
            // Inisialisasi nilai awal SES = data pertama
            $prediksi_array[0] = $nilai[0];
            
            // Hitung prediksi untuk setiap periode mulai dari periode ke-2
            for ($i = 1; $i < $jumlah_data; $i++) {
                $prediksi_array[$i] = $alpha * $nilai[$i-1] + (1 - $alpha) * $prediksi_array[$i-1];
                
                // Hanya hitung error mulai dari bulan ke-4 (indeks 3)
                if ($i >= $periode) {
                    $aktual = $nilai[$i];
                    $error = $aktual - $prediksi_array[$i];
                    $error_kuadrat = pow($error, 2);
                    
                    $error_array[$i] = round($error, 4);
                    $error_kuadrat_array[$i] = $error_kuadrat;
                    
                    $error_total += $error_kuadrat;
                    $n++;
                }
            }
            
            // Prediksi untuk periode selanjutnya
            $hasil_prediksi = $alpha * $nilai[$jumlah_data-1] + (1 - $alpha) * $prediksi_array[$jumlah_data-1];
            $hasil_prediksi = round($hasil_prediksi, 4);
            $rekomendasi = generateRekomendasi($stok_barang, $hasil_prediksi);
            
            // Hitung MSE
            $mse = $n > 0 ? round($error_total / $n, 4) : 0;
            
            // === BAGIAN TAMPILKAN PROSES PERHITUNGAN SES ===
            $proses_perhitungan .= "<h4>📊 Proses Perhitungan SES</h4>";
            $proses_perhitungan .= "<p>Menggunakan nilai alpha = $alpha</p>";
            $proses_perhitungan .= "<p><strong>Rumus SES: F<sub>t</sub> = F<sub>t-1</sub> + α(Y<sub>t-1</sub> - F<sub>t-1</sub>)</strong></p>";
            $proses_perhitungan .= "<p>Dimana:</p>";
            $proses_perhitungan .= "<ul>";
            $proses_perhitungan .= "<li>F<sub>t</sub> = Peramalan periode t.</li>";
            $proses_perhitungan .= "<li>F<sub>t-1</sub> = nilai aktual pada periode saat ini.</li>";
            $proses_perhitungan .= "<li>Y<sub>t-1</sub> = Data aktual periode sebelumnya.</li>";
            $proses_perhitungan .= "<li>α = Alpha (nilai antara 0 dan 1), (dalam kasus ini, α = $alpha).</li>";
            $proses_perhitungan .= "</ul>";

            $proses_perhitungan .= "<table border='1' cellpadding='5' cellspacing='0'>";
            $proses_perhitungan .= "<tr>
                <th>Periode</th>
                <th>Bulan</th>
                <th>Penjualan</th>
                <th>Prediksi (F<sub>t-1</sub>)</th>
                <th>Perhitungan</th>
                <th>Hasil Prediksi (F<sub>t+1</sub>)</th>
            </tr>";
            
            // Tampilkan detail perhitungan
            for ($i = 0; $i < $jumlah_data; $i++) {
                $perhitungan = "";
                $hasil_t_plus_1 = "";
                
                if ($i == 0) {
                    $perhitungan = "Inisialisasi = " . $nilai[0];
                    $hasil_t_plus_1 = $nilai[0];
                } else {
                    // Sesuaikan format perhitungan dengan rumus F_t = F_(t-1) + α(Y_(t-1) - F_(t-1))
                    $ft_minus_1 = round($prediksi_array[$i-1], 4);
                    $yt_minus_1 = $nilai[$i-1];
                    $error_prev = $yt_minus_1 - $ft_minus_1;
                    
                    $perhitungan = "F<sub>t-1</sub> + α(Y<sub>t-1</sub> - F<sub>t-1</sub>) = $ft_minus_1 + $alpha × ($yt_minus_1 - $ft_minus_1)";
                    $hasil_t_plus_1 = round($prediksi_array[$i], 4);
                }
                
                $proses_perhitungan .= "<tr>
                    <td>" . ($i + 1) . "</td>
                    <td>" . (isset($bulan_tampil[$i]) ? $bulan_tampil[$i] : 'Bulan ' . ($i + 1)) . "</td>
                    <td>" . $nilai[$i] . "</td>
                    <td>" . round($prediksi_array[$i], 4) . "</td>
                    <td>" . $perhitungan . "</td>
                    <td>" . $hasil_t_plus_1 . "</td>
                </tr>";
            }

            // Tambahkan prediksi untuk periode berikutnya dengan format rumus yang sama
            $ft_next = round($prediksi_array[$jumlah_data-1], 4);
            $yt_next = $nilai[$jumlah_data-1];
            $perhitungan_next = "F<sub>t-1</sub> + α(Y<sub>t-1</sub> - F<sub>t-1</sub>) = $ft_next + $alpha × ($yt_next - $ft_next)";

            $proses_perhitungan .= "<tr>
                <td>" . ($jumlah_data + 1) . "</td>
                <td>Prediksi Bulan Berikutnya</td>
                <td>-</td>
                <td>-</td>
                <td>" . $perhitungan_next . "</td>
                <td><strong>" . $hasil_prediksi . "</strong></td>
            </tr>";
            
            $proses_perhitungan .= "</table>";
            
            // === BAGIAN TAMPILKAN PERHITUNGAN ERROR (MSE) ===
            $error_detail .= "<h4>📉 Perhitungan MSE</h4>";
            $error_detail .= "<p>MSE dihitung dari selisih kuadrat antara nilai aktual dan nilai prediksi mulai bulan ke-" . ($periode + 1) . "</p>";
            $error_detail .= "<table border='1' cellpadding='5' cellspacing='0'>";
            $error_detail .= "<tr>
                <th>Periode</th>
                <th>Bulan</th>
                <th>Data Aktual</th>
                <th>Prediksi SES</th>
                <th>Error</th>
                <th>Error²</th>
            </tr>";
            
            // Detail perhitungan error hanya mulai dari bulan ke-4 (indeks 3)
            for ($i = $periode; $i < $jumlah_data; $i++) {
                $aktual = $nilai[$i];
                $prediksi_nilai = round($prediksi_array[$i], 4);
                $error_nilai = $error_array[$i];
                $error_kuadrat = round($error_kuadrat_array[$i], 4);
                
                $error_detail .= "<tr>
                    <td>" . ($i + 1) . "</td>
                    <td>" . (isset($bulan_tampil[$i]) ? $bulan_tampil[$i] : 'Bulan ' . ($i + 1)) . "</td>
                    <td>" . $aktual . "</td>
                    <td>" . $prediksi_nilai . "</td>
                    <td>" . $error_nilai . "</td>
                    <td>" . $error_kuadrat . "</td>
                </tr>";
            }
            
            $error_detail .= "</table>";
            $error_detail .= "<p><strong>MSE = Σ(Error²) ÷ n = " . round($error_total, 4) . " ÷ $n = $mse</strong></p>";
            
            // Simpan ke database
            $waktu = date('Y-m-d H:i:s');
            mysqli_query($conn, "INSERT INTO prediksi (id_barang, metode, waktu_prediksi, hasil, mse) VALUES ('$id_barang', 'SES', '$waktu', '$hasil_prediksi', '$mse')");
        } else {
            $hasil_prediksi = "Data penjualan kurang dari $periode bulan.";
        }
    }
}
// Ambil data riwayat prediksi
$query_riwayat ="SELECT p.*, b.nama_barang, b.stok, DATE_FORMAT(p.waktu_prediksi, '%d %M %Y %H:%i') as waktu_eng, 
                DATE_FORMAT(p.waktu_prediksi, '%Y-%m-%d %H:%i:%s') as waktu_raw
                FROM prediksi p
                JOIN barang b ON p.id_barang = b.id_barang
                ORDER BY p.waktu_prediksi DESC
                LIMIT 50"; // Batasi 50 data terakhir untuk performa
$riwayat = mysqli_query($conn, $query_riwayat);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Prediksi Penjualan</title>
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/prediksi.css">
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
                <button class="tab-button active" onclick="openTab('prediksi-tab')">Prediksi Baru</button>
                <button class="tab-button" onclick="openTab('riwayat-tab')">Riwayat Prediksi</button>
            </div>

            <div id="prediksi-tab" class="tab-content active">
                <h2>Prediksi Penjualan (Metode WMA Dan SES)</h2>
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

                    <label for="metode">Pilih Metode:</label>
                    <select name="metode" id="metode" required>
                        <option value="wma" <?= isset($_POST['metode']) && $_POST['metode'] == 'wma' ? 'selected' : '' ?>>Weighted Moving Average (WMA)</option>
                        <option value="ses" <?= isset($_POST['metode']) && $_POST['metode'] == 'ses' ? 'selected' : '' ?>>Single Exponential Smoothing (SES)</option>
                    </select>

                    <div id="wma-options" style="<?= (!isset($_POST['metode']) || $_POST['metode'] == 'wma') ? '' : 'display:none;' ?>">
                        <label for="bobot_manual">Bobot WMA:</label>
                        <input type="text" name="bobot_manual" id="bobot_manual" value="3,2,1" readonly class="readonly-input">
                        <small>*Bobot 3,2,1 (data terbaru diberi bobot terbesar)</small>
                    </div>

                    <div id="ses-options" style="<?= (isset($_POST['metode']) && $_POST['metode'] == 'ses') ? '' : 'display:none;' ?>">
                        <label for="alpha">Alpha SES:</label>
                        <input type="text" name="alpha" id="alpha" value="0.2" readonly class="readonly-input">
                        <small>*Nilai alpha: 0.2</small>
                    </div>

                <button type="submit" name="prediksi">PREDIKSI</button>
            </form>

            <?php
                    if (!empty($hasil_prediksi)) {
                        echo "<div class='hasil-prediksi'>";
                        echo "<h3>Hasil Prediksi</h3>";
                        echo "<p><strong>Prediksi Penjualan Bulan Berikutnya: $hasil_prediksi unit</strong></p>";
                        echo "<p><strong>Stok Saat Ini: $stok_barang unit</strong></p>";
                        
                        if (!empty($mse)) {
                            echo "<p><strong>Nilai MSE: $mse</strong></p>";
                        }
                        
                        // Tampilkan rekomendasi dengan styling
                        $rekomendasi_class = '';
                        if (strpos($rekomendasi, 'lebih dari') !== false) {
                            $rekomendasi_class = 'rekomendasi-lebih';
                        } elseif (strpos($rekomendasi, 'kurang dari') !== false) {
                            $rekomendasi_class = 'rekomendasi-kurang';
                        } else {
                            $rekomendasi_class = 'rekomendasi-sesuai';
                        }
                        
                        echo "<div class='rekomendasi $rekomendasi_class'>";
                        echo "<h4>📊 Rekomendasi Stok</h4>";
                        echo "<p><strong>$rekomendasi</strong></p>";
                        echo "</div>";
                        
                        echo $proses_perhitungan;
                        echo $error_detail;
                        echo "</div>";
                    }
                ?>
        </div>
        <div id="riwayat-tab" class="tab-content">
            <h2>Riwayat Prediksi</h2>
                
                <?php if (mysqli_num_rows($riwayat) > 0): ?>
                <table class="riwayat-table">
                    <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Barang</th>
                        <th>Metode</th>
                        <th>Hasil Prediksi</th>
                        <th>Stok</th>
                        <th>Rekomendasi</th>
                        <th>MSE</th>
                        <th>Waktu Prediksi</th>
                        <th>Tindakan</th>
                    </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($riwayat)):
                            $badge_class = strtolower($row['metode']) == 'wma' ? 'badge-wma' : 'badge-ses';
                            
                            // Konversi waktu ke format Indonesia
                            $timestamp = strtotime($row['waktu_raw']);
                            $waktu_indo = date('d F Y H:i', $timestamp);
                            $waktu_indo = translateMonthName($waktu_indo);
                            
                            // Generate rekomendasi untuk riwayat
                            $rekomendasi_riwayat = generateRekomendasi($row['stok'], $row['hasil']);
                            $rekomendasi_class_riwayat = '';
                            
                            if (strpos($rekomendasi_riwayat, 'lebih dari') !== false) {
                                $rekomendasi_class_riwayat = 'rekomendasi-lebih';
                            } elseif (strpos($rekomendasi_riwayat, 'kurang dari') !== false) {
                                $rekomendasi_class_riwayat = 'rekomendasi-kurang';
                            } else {
                                $rekomendasi_class_riwayat = 'rekomendasi-sesuai';
                            }
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                            <td><span class="badge <?= $badge_class ?>"><?= htmlspecialchars($row['metode']) ?></span></td>
                            <td><?= htmlspecialchars($row['hasil']) ?> unit</td>
                            <td><?= htmlspecialchars($row['stok']) ?> unit</td>
                            <td><span class="<?= $rekomendasi_class_riwayat ?>"><?= htmlspecialchars($rekomendasi_riwayat) ?></span></td>
                            <td><?= htmlspecialchars($row['mse']) ?></td>
                            <td><?= htmlspecialchars($waktu_indo) ?></td>
                            <td class="tindakan">
                                <button class="btn-detail" onclick="lihatDetail(<?= $row['id_prediksi'] ?>)">Detail</button>
                                <button class="btn-hapus" onclick="hapusPrediksi(<?= $row['id_prediksi'] ?>)">Hapus</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>Belum ada data riwayat prediksi.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Prediksi -->
<div id="detailModal" class="modal" >
    <div class="modal-content" >
        <span class="close" onclick="tutupModal()" >&times;</span>
        <div id="detailContent">
            <!-- Konten detail akan diisi melalui AJAX -->
        </div>
    </div>
</div>

<script src="../js/prediksi.js"></script>
<script src="../js/dashboard.js"></script>
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>