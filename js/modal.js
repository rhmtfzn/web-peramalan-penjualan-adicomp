/**
 * CRUD Operations JavaScript
 * File untuk menangani operasi CRUD di halaman kelola_data.php, kelola_user.php dan kelola_barang.php
 */

// Event listener saat DOM selesai dimuat
document.addEventListener('DOMContentLoaded', function() {
    // PENTING: Hapus bagian toggle sidebar yang duplikat dari file ini
    // karena sudah ditangani oleh dashboard.js
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    if (alerts.length > 0) {
        setTimeout(function() {
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 500);
            });
        }, 5000);
    }
});

/**
 * Modal functions for both pages
 */

// Tambah modal functions
function showTambahModal() {
    document.getElementById('tambahModal').style.display = 'block';
}

function closeTambahModal() {
    document.getElementById('tambahModal').style.display = 'none';
}

// Edit modal functions
function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Kelola User specific function
function editUser(id, username, level) {
    document.getElementById('edit_id_user').value = id;
    document.getElementById('edit_username').value = username;
    document.getElementById('edit_level').value = level;
    document.getElementById('editModal').style.display = 'block';
}

// PERBAIKAN: Kelola Data specific function - tambahkan parameter username
function editData(id, nama_barang, bulan_display, bulan_value, jumlah, id_user, username) {
    console.log('editData called with:', {id, nama_barang, bulan_display, bulan_value, jumlah, id_user, username}); // Debug
    
    document.getElementById('edit_id_penjualan').value = id;
    document.getElementById('edit_nama_barang').value = nama_barang;
    document.getElementById('edit_bulan').value = bulan_display;
    document.getElementById('edit_bulan_hidden').value = bulan_value;
    document.getElementById('edit_jumlah').value = jumlah;
    
    // Set petugas field sebagai read-only
    const petugasField = document.getElementById('edit_petugas');
    if (petugasField) {
        petugasField.value = username || 'Tidak ada';
    }
    
    document.getElementById('editModal').style.display = 'block';
}

// BARU: Kelola Barang specific function
function editBarang(id, nama_barang, stok) { // Tambahkan parameter stok
    console.log('editBarang called with:', {id, nama_barang, stok}); // Update debug log
    
    document.getElementById('edit_id_barang').value = id;
    document.getElementById('edit_nama_barang').value = nama_barang;
    document.getElementById('edit_stok').value = stok; // Tambahkan baris ini
    document.getElementById('editModal').style.display = 'block';
}

// Global modal close when clicking outside
window.addEventListener('click', function(event) {
    const tambahModal = document.getElementById('tambahModal');
    const editModal = document.getElementById('editModal');
    
    if (event.target == tambahModal) {
        tambahModal.style.display = 'none';
    }
    
    if (event.target == editModal) {
        editModal.style.display = 'none';
    }
});

// Form validation (dapat ditambahkan untuk validasi form sebelum submit)
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        // Basic validation example
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        return isValid;
    }
    return false;
}

// Confirm delete with custom message
function confirmDelete(message) {
    return confirm(message || 'Yakin ingin menghapus data ini?');
}