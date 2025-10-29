<?php
session_start();
// Perbaikan autentikasi session
if (!isset($_SESSION['username']) || $_SESSION['level'] != 1) {
    header("Location: ../login.php");
    exit();
}

// Koneksi ke database
include '../config/koneksi.php';

// Set zona waktu ke Indonesia (WIB)
date_default_timezone_set('Asia/Jakarta');

// Proses tambah user baru
if (isset($_POST['tambah'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $level = mysqli_real_escape_string($conn, $_POST['level']);
    
    // Cek apakah username sudah ada
    $check_query = "SELECT * FROM user WHERE username = '$username'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $pesan_error = "Username sudah digunakan!";
    } else {
        
        
        $insert_query = "INSERT INTO user (username, password, level) VALUES ('$username', '$password', '$level')";
        
        if (mysqli_query($conn, $insert_query)) {
            $pesan_sukses = "User berhasil ditambahkan!";
        } else {
            $pesan_error = "Error: " . mysqli_error($conn);
        }
    }
}

// Proses edit user
if (isset($_POST['edit'])) {
    $id_user = mysqli_real_escape_string($conn, $_POST['id_user']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $level = mysqli_real_escape_string($conn, $_POST['level']);
    
    // Cek apakah ada perubahan password
    if (!empty($_POST['password'])) {
        $password = mysqli_real_escape_string($conn, $_POST['password']);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update_query = "UPDATE user SET username = '$username', password = '$hashed_password', level = '$level' WHERE id_user = '$id_user'";
    } else {
        $update_query = "UPDATE user SET username = '$username', level = '$level' WHERE id_user = '$id_user'";
    }
    
    if (mysqli_query($conn, $update_query)) {
        $pesan_sukses = "User berhasil diupdate!";
    } else {
        $pesan_error = "Error: " . mysqli_error($conn);
    }
}

// Proses hapus user
if (isset($_GET['hapus'])) {
    $id_user = mysqli_real_escape_string($conn, $_GET['hapus']);
    
    // Pastikan admin tidak bisa menghapus dirinya sendiri
    // Perbaikan: Periksa apakah id_user tersedia di session sebelum membandingkan
    if (isset($_SESSION['id_user']) && $id_user == $_SESSION['id_user']) {
        $pesan_error = "Anda tidak dapat menghapus akun yang sedang digunakan!";
    } else {
        $delete_query = "DELETE FROM user WHERE id_user = '$id_user'";
        
        if (mysqli_query($conn, $delete_query)) {
            $pesan_sukses = "User berhasil dihapus!";
        } else {
            $pesan_error = "Error: " . mysqli_error($conn);
        }
    }
}

// Query untuk mengambil data user
$query = "SELECT * FROM user ORDER BY id_user ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User</title>
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

    <!-- Main -->
    <div class="main">
        <div class="topbar">
            <div class="toggle"><ion-icon name="menu-outline"></ion-icon></div>
        </div>

        <div class="content">
            <h2>Kelola User</h2>
            <p>Halaman ini digunakan untuk mengelola data pengguna sistem.</p>
            
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
                <button class="btn btn-primary" onclick="showTambahModal()">+ Tambah User</button>
            </div>
            
            <!-- Tabel User -->
            <h3>Daftar User</h3>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Username</th>
                            <th>Status</th>
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
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td>
                                <?php 
                                if($row['level'] == 1) {
                                    echo "<span class='badge bg-primary'>Admin</span>";
                                } else {
                                    echo "<span class='badge bg-success'>Karyawan</span>";
                                }
                                ?>
                            </td>
                            <td>
                                <button class="btn btn-warning btn-sm" onclick="editUser(<?php echo $row['id_user']; ?>, '<?php echo htmlspecialchars($row['username']); ?>', <?php echo $row['level']; ?>)">Edit</button>
                                <a href="?hapus=<?php echo $row['id_user']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus user ini?')">Hapus</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Modal Tambah User -->
            <div id="tambahModal" class="modal">
                <div class="modal-content">
                    <h3>Tambah User Baru</h3>
                    <form action="" method="POST">
                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label for="level">Level:</label>
                            <select class="form-control" id="level" name="level" required>
                                <option value="1">Admin</option>
                                <option value="2">Karyawan</option>
                            </select>
                        </div>
                        <div class="mt-3">
                            <button type="submit" name="tambah" class="btn btn-primary">Tambah User</button>
                            <button type="button" class="btn btn-danger" onclick="closeTambahModal()">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Modal Edit User -->
            <div id="editModal" class="modal">
                <div class="modal-content">
                    <h3>Edit User</h3>
                    <form action="" method="POST">
                        <input type="hidden" id="edit_id_user" name="id_user">
                        <div class="form-group">
                            <label for="edit_username">Username:</label>
                            <input type="text" class="form-control" id="edit_username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_password">Password Baru (Kosongkan jika tidak ingin mengubah):</label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                        </div>
                        <div class="form-group">
                            <label for="edit_level">Level:</label>
                            <select class="form-control" id="edit_level" name="level" required>
                                <option value="1">Admin</option>
                                <option value="2">Karyawan</option>
                            </select>
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