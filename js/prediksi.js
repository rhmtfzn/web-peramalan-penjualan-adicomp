/**
 * Inisialisasi event listeners ketika dokumen selesai dimuat
 */
 document.addEventListener('DOMContentLoaded', function() {
    // Mengatur tampilan opsi metode prediksi
    setupMetodeOptions();
});

/**
 * Mengatur event listener untuk elemen select metode
 */
function setupMetodeOptions() {
    const metodeSelect = document.getElementById('metode');
    const wmaOptions = document.getElementById('wma-options');
    const sesOptions = document.getElementById('ses-options');
    
    metodeSelect.addEventListener('change', function() {
        if (this.value === 'wma') {
            wmaOptions.style.display = 'block';
            sesOptions.style.display = 'none';
        } else if (this.value === 'ses') {
            wmaOptions.style.display = 'none';
            sesOptions.style.display = 'block';
        }
    });
}

/**
 * Menampilkan tab yang dipilih
 * @param {string} tabName - Nama ID tab yang akan ditampilkan
 */
function openTab(tabName) {
    // Sembunyikan semua tab content
    const tabs = document.getElementsByClassName("tab-content");
    for (let i = 0; i < tabs.length; i++) {
        tabs[i].classList.remove("active");
    }
    
    // Nonaktifkan semua tab button
    const buttons = document.getElementsByClassName("tab-button");
    for (let i = 0; i < buttons.length; i++) {
        buttons[i].classList.remove("active");
    }
    
    // Tampilkan tab yang dipilih
    document.getElementById(tabName).classList.add("active");
    
    // Aktifkan button yang dipilih
    const activeButtons = document.querySelectorAll(`button[onclick="openTab('${tabName}')"]`);
    if (activeButtons.length > 0) {
        activeButtons[0].classList.add("active");
    }
}

/**
 * Menampilkan detail prediksi dalam modal
 * @param {number} id - ID prediksi yang akan ditampilkan
 */
function lihatDetail(id) {
    // Buat AJAX request untuk mengambil detail prediksi
    const xhr = new XMLHttpRequest();
    xhr.open("GET", "get_prediksi_detail.php?id=" + id, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            document.getElementById("detailContent").innerHTML = xhr.responseText;
            document.getElementById("detailModal").style.display = "block";
        }
    };
    xhr.send();
}

/**
 * Menutup modal detail
 */
function tutupModal() {
    document.getElementById("detailModal").style.display = "none";
}

/**
 * Konfirmasi dan menghapus data prediksi
 * @param {number} id - ID prediksi yang akan dihapus
 */
function hapusPrediksi(id) {
    if (confirm("Apakah Anda yakin ingin menghapus data prediksi ini?")) {
        window.location.href = "hapus_prediksi.php?id=" + id;
    }
}

// Tutup modal jika user klik di luar modal
window.onclick = function(event) {
    const modal = document.getElementById("detailModal");
    if (event.target == modal) {
        modal.style.display = "none";
    }
}