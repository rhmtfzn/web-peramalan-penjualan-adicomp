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

// Proses tambah barang baru
if (isset($_POST['tambah'])) {
    $nama_barang = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    
    // Cek apakah nama barang sudah ada
    $check_query = "SELECT * FROM barang WHERE nama_barang = '$nama_barang'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $pesan_error = "Nama barang sudah ada!";
    } else {
        $insert_query = "INSERT INTO barang (nama_barang) VALUES ('$nama_barang')";
        
        if (mysqli_query($conn, $insert_query)) {
            $pesan_sukses = "Barang berhasil ditambahkan!";
        } else {
            $pesan_error = "Error: " . mysqli_error($conn);
        }
    }
}

// Proses edit barang
if (isset($_POST['edit'])) {
    $id_barang = mysqli_real_escape_string($conn, $_POST['id_barang']);
    $nama_barang = mysqli_real_escape_string($conn, $_POST['nama_barang']);
    
    // Cek apakah nama barang sudah ada (kecuali untuk barang yang sedang diedit)
    $check_query = "SELECT * FROM barang WHERE nama_barang = '$nama_barang' AND id_barang != '$id_barang'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $pesan_error = "Nama barang sudah ada!";
    } else {
        $update_query = "UPDATE barang SET nama_barang = '$nama_barang' WHERE id_barang = '$id_barang'";
        
        if (mysqli_query($conn, $update_query)) {
            $pesan_sukses = "Barang berhasil diupdate!";
        } else {
            $pesan_error = "Error: " . mysqli_error($conn);
        }
    }
}

// Proses hapus barang
if (isset($_GET['hapus'])) {
    $id_barang = mysqli_real_escape_string($conn, $_GET['hapus']);
    
    $delete_query = "DELETE FROM barang WHERE id_barang = '$id_barang'";
    
    if (mysqli_query($conn, $delete_query)) {
        $pesan_sukses = "Barang berhasil dihapus!";
    } else {
        $pesan_error = "Error: " . mysqli_error($conn);
    }
}

// Query untuk mengambil data barang
$query = "SELECT * FROM barang ORDER BY id_barang ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Barang</title>
    <!-- Bootstrap CSS sebelum CSS kustom untuk memungkinkan penimpaan -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- CSS Dashboard original -->
    <link rel="stylesheet" href="../css/dashboard.css">
    <!-- CSS perbaikan untuk sidebar -->
    <link rel="stylesheet" href="../css/sidebar-fix.css">
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
            <h2>Kelola Barang</h2>
            <p>Halaman ini digunakan untuk mengelola data barang sistem.</p>
            
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
                <button class="btn btn-primary" onclick="showTambahModal()">+ Tambah Barang</button>
            </div>
            
            <!-- Tabel Barang -->
            <h3>Daftar Barang</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Barang</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while($row = mysqli_fetch_assoc($result)): 
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($row['nama_barang']); ?></td>
                            <td>
                                <button class="btn btn-warning btn-sm" onclick="editBarang(<?php echo $row['id_barang']; ?>, '<?php echo htmlspecialchars($row['nama_barang']); ?>')">Edit</button>
                                <a href="?hapus=<?php echo $row['id_barang']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus barang ini?')">Hapus</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Modal Tambah Barang -->
            <div id="tambahModal" class="modal">
                <div class="modal-content">
                    <h3>Tambah Barang Baru</h3>
                    <form action="" method="POST">
                        <div class="form-group">
                            <label for="nama_barang">Nama Barang:</label>
                            <input type="text" class="form-control" id="nama_barang" name="nama_barang" required>
                        </div>
                        <div class="mt-3">
                            <button type="submit" name="tambah" class="btn btn-primary">Tambah Barang</button>
                            <button type="button" class="btn btn-danger" onclick="closeTambahModal()">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Modal Edit Barang -->
            <div id="editModal" class="modal">
                <div class="modal-content">
                    <h3>Edit Barang</h3>
                    <form action="" method="POST">
                        <input type="hidden" id="edit_id_barang" name="id_barang">
                        <div class="form-group">
                            <label for="edit_nama_barang">Nama Barang:</label>
                            <input type="text" class="form-control" id="edit_nama_barang" name="nama_barang" required>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/dashboard.js"></script>
    <script src="../js/modal.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>