<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['level'] != 2) {
    header("Location: ../login.php");
    exit();
}

include '../config/koneksi.php';

// Set zona waktu ke Indonesia (WIB)
date_default_timezone_set('Asia/Jakarta');

// Proses tambah data baru
if (isset($_POST['tambah'])) {
    $id_barang = mysqli_real_escape_string($conn, $_POST['id_barang']);
    $bulan_nama = mysqli_real_escape_string($conn, $_POST['bulan_nama']);
    $tahun = mysqli_real_escape_string($conn, $_POST['tahun']);
    $jumlah = mysqli_real_escape_string($conn, $_POST['jumlah']);

    // Ambil id_user dari session, bukan dari form
    $id_user = $_SESSION['id_user'];
    
    // Format tanggal: YYYY-MM-01
    $bulan_format = "$tahun-$bulan_nama-01";
    
    // Cek apakah data sudah ada (pencegahan duplikasi)
    $check_query = "SELECT * FROM penjualan WHERE id_barang = '$id_barang' AND bulan = '$bulan_format'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $pesan_error = "Data penjualan untuk barang dan bulan tersebut sudah ada!";
    } else {
        $insert_query = "INSERT INTO penjualan (id_barang, bulan, jumlah, id_user) VALUES ('$id_barang', '$bulan_format', '$jumlah', '$id_user')";
        
        if (mysqli_query($conn, $insert_query)) {
            $pesan_sukses = "Data penjualan berhasil ditambahkan!";
        } else {
            $pesan_error = "Error: " . mysqli_error($conn);
        }
    }
}

// Proses edit data
if (isset($_POST['edit'])) {
    $id_penjualan = mysqli_real_escape_string($conn, $_POST['id_penjualan']);
    $jumlah = mysqli_real_escape_string($conn, $_POST['jumlah']);
    
    $update_query = "UPDATE penjualan SET jumlah = '$jumlah' WHERE id_penjualan = '$id_penjualan'";
    
    if (mysqli_query($conn, $update_query)) {
        $pesan_sukses = "Data penjualan berhasil diupdate!";
    } else {
        $pesan_error = "Error: " . mysqli_error($conn);
    }
}

// Proses hapus data
if (isset($_GET['hapus'])) {
    $id_penjualan = mysqli_real_escape_string($conn, $_GET['hapus']);
    $delete_query = "DELETE FROM penjualan WHERE id_penjualan = '$id_penjualan'";
    
    if (mysqli_query($conn, $delete_query)) {
        $pesan_sukses = "Data penjualan berhasil dihapus!";
    } else {
        $pesan_error = "Error: " . mysqli_error($conn);
    }
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search and filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter_bulan = isset($_GET['bulan']) ? mysqli_real_escape_string($conn, $_GET['bulan']) : '';
$filter_tahun = isset($_GET['tahun']) ? mysqli_real_escape_string($conn, $_GET['tahun']) : date('Y');

// Query utama
$query = "SELECT penjualan.id_penjualan, barang.nama_barang, barang.id_barang, penjualan.bulan, penjualan.jumlah, penjualan.id_user, user.username
          FROM penjualan 
          JOIN barang ON penjualan.id_barang = barang.id_barang 
          LEFT JOIN user ON penjualan.id_user = user.id_user
          WHERE barang.nama_barang LIKE '%$search%'";

if (!empty($filter_bulan)) {
    $query .= " AND MONTH(penjualan.bulan) = '$filter_bulan'";
    
    if (!empty($filter_tahun)) {
        $query .= " AND YEAR(penjualan.bulan) = '$filter_tahun'";
    }
}

// Urutkan berdasarkan nama barang dan bulan (dari awal tahun)
$query .= " ORDER BY barang.nama_barang ASC, YEAR(penjualan.bulan) ASC, MONTH(penjualan.bulan) ASC LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $query);

// Hitung total data
$countQuery = "SELECT COUNT(*) as total FROM penjualan 
               JOIN barang ON penjualan.id_barang = barang.id_barang 
               LEFT JOIN user ON penjualan.id_user = user.id_user
               WHERE barang.nama_barang LIKE '%$search%'";
if (!empty($filter_bulan)) {
    $countQuery .= " AND MONTH(penjualan.bulan) = '$filter_bulan'";
    
    if (!empty($filter_tahun)) {
        $countQuery .= " AND YEAR(penjualan.bulan) = '$filter_tahun'";
    }
}
$countResult = mysqli_query($conn, $countQuery);
$totalRows = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalRows / $limit);

// Dapatkan daftar tahun yang tersedia dalam database
$years_query = "SELECT DISTINCT YEAR(bulan) as tahun FROM penjualan ORDER BY tahun ASC";
$years_result = mysqli_query($conn, $years_query);
$available_years = [];
while ($year_row = mysqli_fetch_assoc($years_result)) {
    $available_years[] = $year_row['tahun'];
}

// Ambil data barang untuk form tambah
$barang_query = "SELECT id_barang, nama_barang FROM barang ORDER BY nama_barang ASC";
$barang_result = mysqli_query($conn, $barang_query);
$user_query = "SELECT id_user, username FROM user ORDER BY username ASC";
$user_result = mysqli_query($conn, $user_query);
$user_list = [];
while ($user_row = mysqli_fetch_assoc($user_result)) {
    $user_list[] = $user_row;
}
$barang_list = [];
while ($barang_row = mysqli_fetch_assoc($barang_result)) {
    $barang_list[] = $barang_row;
}

// Ambil data user yang sedang login
$current_user_query = "SELECT username FROM user WHERE id_user = '{$_SESSION['id_user']}'";
$current_user_result = mysqli_query($conn, $current_user_query);
$current_user = mysqli_fetch_assoc($current_user_result);
$current_username = $current_user['username'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Data Penjualan</title>
    <!-- Bootstrap CSS sebelum CSS kustom untuk memungkinkan penimpaan -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- CSS Dashboard original -->
    <link rel="stylesheet" href="../css/dashboard.css">
    <!-- CSS perbaikan untuk sidebar -->
    <link rel="stylesheet" href="../css/sidebar-fix.css">
    <!-- CSS untuk modal -->
    <link rel="stylesheet" href="../css/modal.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    
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
                <li><a href="../logout.php"><span class="icon"><ion-icon name="log-out-outline"></ion-icon></span>
                        <span class="title">Logout</span></a></li>
            </ul>
    </div>

    <!-- Main -->
    <div class="main">
        <div class="topbar">
            <div class="toggle"><ion-icon name="menu-outline"></ion-icon></div>
        </div>

        <div class="content">
            <h2>Kelola Data Penjualan</h2>
            
            <?php if(isset($pesan_sukses)): ?>
            <div class="alert alert-success">
                <?php echo $pesan_sukses; ?>
            </div>
            <?php endif; ?>
            
            <?php if(isset($pesan_error)): ?>
            <div class="alert alert-danger">
                <?php echo $pesan_error; ?>
            </div>
            <?php endif; ?>
            
            <div class="d-flex justify-content-end mb-3">
                <button class="btn btn-primary" onclick="showTambahModal()">+ Tambah Data</button>
            </div>

            <!-- Search & Filter -->
            <form method="GET" class="mb-3">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <input type="text" name="search" class="form-control" placeholder="Cari Nama Barang" value="<?php echo $search; ?>">
                    </div>
                    <div class="col-md-3 mb-2">
                        <select name="bulan" class="form-control">
                            <option value="">Pilih Bulan</option>
                            <?php
                            $bulan_arr = [
                                '1' => 'Januari', '2' => 'Februari', '3' => 'Maret',
                                '4' => 'April', '5' => 'Mei', '6' => 'Juni',
                                '7' => 'Juli', '8' => 'Agustus', '9' => 'September',
                                '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                            ];
                            
                            foreach ($bulan_arr as $num => $nama) {
                                $selected = ($filter_bulan == $num) ? 'selected' : '';
                                echo "<option value='$num' $selected>$nama</option>";
                            }                                
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select name="tahun" class="form-control">
                            <option value="">Pilih Tahun</option>
                            <?php
                            // Jika belum ada data tahun, tampilkan tahun saat ini
                            if (empty($available_years)) {
                                $current_year = date('Y');
                                echo "<option value='$current_year' selected>$current_year</option>";
                            } else {
                                foreach ($available_years as $year) {
                                    $selected = ($filter_tahun == $year) ? 'selected' : '';
                                    echo "<option value='$year' $selected>$year</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-2">
                        <button type="submit" class="btn btn-primary">Cari</button>
                        <a href="kelola_data.php" class="btn btn-warning">Reset</a>
                    </div>
                </div>
            </form>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Barang</th>
                            <th>Bulan</th>
                            <th>Jumlah</th>
                            <th>Petugas</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                        $no = $offset + 1;
                        $current_barang = '';
                        
                        while ($row = mysqli_fetch_assoc($result)) {
                            // Konversi tanggal ke format nama bulan Indonesia
                            $bulanIndo = [
                                'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
                                'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
                                'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
                                'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
                            ];
                            
                            $bulanInggris = date('F', strtotime($row['bulan']));
                            $tahun = date('Y', strtotime($row['bulan']));
                            $bulan_angka = date('m', strtotime($row['bulan']));
                            $namaBulan = $bulanIndo[$bulanInggris] . " " . $tahun;
                            
                            // Tambahkan baris pemisah jika barang berubah
                            if ($current_barang != $row['nama_barang'] && $current_barang != '') {
                                echo "<tr><td colspan='6' class='bg-light'></td></tr>";
                            }
                            $current_barang = $row['nama_barang'];
                            $username = $row['username'] ? $row['username'] : 'Tidak ada';
                            echo "<tr>
                                <td>{$no}</td>
                                <td>{$row['nama_barang']}</td>
                                <td>{$namaBulan}</td>
                                <td>{$row['jumlah']}</td>
                                <td>{$username}</td>
                                <td>
                                    <button class='btn btn-warning btn-sm' onclick=\"editData('{$row['id_penjualan']}', '" . addslashes($row['nama_barang']) . "', '" . addslashes($namaBulan) . "', '{$row['bulan']}', '{$row['jumlah']}', '{$row['id_user']}', '" . addslashes($username) . "')\">Edit</button>
                                    <a href='?hapus={$row['id_penjualan']}' class='btn btn-danger btn-sm' onclick=\"return confirm('Yakin ingin menghapus data ini?')\">Hapus</a>
                                </td>
                            </tr>";
                        $no++;
                    }
                    ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>&bulan=<?php echo $filter_bulan; ?>&tahun=<?php echo $filter_tahun; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            
            <!-- Modal Tambah Data -->
            <div id="tambahModal" class="modal">
                <div class="modal-content">
                    <h3>Tambah Data Penjualan</h3>
                    <form action="" method="POST">
                        <div class="form-group">
                            <label for="id_barang">Nama Barang:</label>
                            <select class="form-control" id="id_barang" name="id_barang" required>
                                <option value="">-- Pilih Barang --</option>
                                <?php foreach ($barang_list as $barang): ?>
                                <option value="<?php echo $barang['id_barang']; ?>"><?php echo htmlspecialchars($barang['nama_barang']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="petugas_display">Petugas:</label>
                            <input type="text" class="form-control" id="petugas_display" value="<?php echo htmlspecialchars($current_username); ?>" readonly>
                            <!-- Hidden field tidak diperlukan karena kita ambil dari session -->
                        </div>
                        <div class="form-group">
                            <label for="bulan_nama">Bulan:</label>
                            <select class="form-control" id="bulan_nama" name="bulan_nama" required>
                                <option value="">-- Pilih Bulan --</option>
                                <option value="01">Januari</option>
                                <option value="02">Februari</option>
                                <option value="03">Maret</option>
                                <option value="04">April</option>
                                <option value="05">Mei</option>
                                <option value="06">Juni</option>
                                <option value="07">Juli</option>
                                <option value="08">Agustus</option>
                                <option value="09">September</option>
                                <option value="10">Oktober</option>
                                <option value="11">November</option>
                                <option value="12">Desember</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="tahun">Tahun:</label>
                            <select class="form-control" id="tahun" name="tahun" required>
                                <option value="">-- Pilih Tahun --</option>
                                <?php
                                $tahun_sekarang = date('Y');
                                $tahun_awal = $tahun_sekarang - 5; // 5 tahun ke belakang
                                $tahun_akhir = $tahun_sekarang + 2; // 2 tahun ke depan
                                
                                for ($tahun = $tahun_awal; $tahun <= $tahun_akhir; $tahun++) {
                                    $selected = ($tahun == $tahun_sekarang) ? 'selected' : '';
                                    echo "<option value='$tahun' $selected>$tahun</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="jumlah">Jumlah:</label>
                            <input type="number" class="form-control" id="jumlah" name="jumlah" required>
                        </div>
                        <div class="mt-3">
                            <button type="submit" name="tambah" class="btn btn-primary">Tambah Data</button>
                            <button type="button" class="btn btn-danger" onclick="closeTambahModal()">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Modal Edit Data -->
            <div id="editModal" class="modal">
                <div class="modal-content">
                    <h3>Edit Data Penjualan</h3>
                    <form action="" method="POST">
                        <input type="hidden" id="edit_id_penjualan" name="id_penjualan">
                        <div class="form-group">
                            <label for="edit_nama_barang">Nama Barang:</label>
                            <input type="text" class="form-control" id="edit_nama_barang" readonly>
                        </div>
                        <div class="form-group">
                            <label for="edit_bulan">Bulan:</label>
                            <input type="hidden" id="edit_bulan_hidden" name="bulan">
                            <input type="text" class="form-control" id="edit_bulan" readonly>
                        </div>
                        <div class="form-group">
                            <label for="edit_petugas">Petugas:</label>
                            <input type="text" class="form-control" id="edit_petugas" readonly>
                            <!-- Hapus select dropdown untuk petugas -->
                        </div>
                        <div class="form-group">
                            <label for="edit_jumlah">Jumlah:</label>
                            <input type="number" class="form-control" id="edit_jumlah" name="jumlah" required>
                        </div>
                        <div class="mt-3">
                            <button type="submit" name="edit" class="btn btn-primary">Update</button>
                            <button type="button" class="btn btn-danger" onclick="closeEditModal()">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../js/modal.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/dashboard.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>